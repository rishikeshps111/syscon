<?php

namespace Tests\Integration;

use App\Models\UserDeviceToken;
use App\Models\Vehicle;
use App\Services\FirebaseMessaging;
use App\Services\TripCancellationNotifier;
use App\Services\TripSheetStatus;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TripAssignmentTestCase;

class TripCancellationApiTest extends TripAssignmentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(TripCancellationNotifier::class)->shouldReceive('send')->andReturn(['sent' => 0, 'failed' => 0]);
    }

    public function test_cancels_with_history_and_releases_a_single_trip_roster(): void
    {
        $this->patchJson($this->url('/cancel'), ['reason' => 'Operational issue'])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Operational issue')
            ->assertJsonPath('data.cancelled_by', $this->supervisor->id);
        $entry = $this->entry->fresh();
        $this->assertNotNull($entry->cancelled_at);
        $this->assertSame('cancelled', $entry->sheet->status);
        $this->assertSame('cancelled', $this->roster->fresh()->status);
        $this->assertSame($this->oldDriver->id, (int) $entry->driver_profile_id);
        $this->assertTrue($entry->is_driver_verified);
        $this->assertSame(1, $entry->rosters()->count());
        $this->assertTrue($entry->sheet->trip->is_active);
    }

    public function test_retries_preserve_original_history_and_notify_once(): void
    {
        $this->mock(TripCancellationNotifier::class)->shouldReceive('send')->once()->andReturn(['sent' => 2, 'failed' => 0]);
        $first = $this->patchJson($this->url('/cancel'), ['reason' => 'Original reason'])->assertOk()->json('data');
        $this->travel(10)->minutes();
        $this->patchJson($this->url('/cancel'), ['reason' => 'Different reason'])->assertOk()
            ->assertJsonPath('data', $first)->assertJsonPath('notifications.sent', 0);
    }

    public function test_cancellation_keeps_shared_roster_resources_reserved_until_all_entries_cancelled(): void
    {
        $other = $this->entry->replicate();
        $other->save();
        $this->roster->tripSheetEntries()->attach($other);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Cancel first'])->assertOk();
        $this->assertSame('pending', $other->fresh()->status);
        $this->assertSame('pending', $this->entry->fresh()->sheet->status);
        $this->assertSame('assigned', $this->roster->fresh()->status);
        $this->assertSame(2, $this->roster->tripSheetEntries()->count());
        $this->patchJson('/api/v1/trips/'.$other->id.'/cancel', ['reason' => 'Cancel second'])->assertOk();
        $this->assertSame('cancelled', $this->roster->fresh()->status);
        $this->assertSame('cancelled', $this->entry->fresh()->sheet->status);
    }

    public function test_sheet_progress_ignores_cancelled_entries_even_when_verification_sync_runs_later(): void
    {
        $other = $this->entry->replicate();
        $other->fill(['status' => 'initial_verification_completed'])->save();
        $this->patchJson($this->url('/cancel'), ['reason' => 'Not running'])->assertOk();
        $sheet = $this->entry->fresh()->sheet;
        $this->assertSame('initial_verification_completed', $sheet->status);
        $other->update(['status' => 'verification_completed']);
        app(TripSheetStatus::class)->sync($sheet);
        $this->assertSame('verification_completed', $sheet->fresh()->status);
    }

    public function test_started_completed_and_inconsistent_verified_trips_are_rejected(): void
    {
        foreach ([['status' => 'initial_verification_completed'], ['status' => 'verification_completed'],
            ['actual_start_time' => '08:00'], ['actual_reach_time' => '10:00'], ['is_initial_verified' => true], ['is_final_verified' => true]] as $changes) {
            $this->entry->update(array_merge(['status' => 'pending', 'actual_start_time' => null,
                'actual_reach_time' => null, 'is_initial_verified' => false, 'is_final_verified' => false], $changes));
            $this->patchJson($this->url('/cancel'), ['reason' => 'Too late'])->assertUnprocessable()->assertJsonValidationErrors('trip_id');
            $this->assertNull($this->entry->fresh()->cancelled_at);
        }
    }

    public function test_only_active_operating_depot_supervisor_can_cancel_or_retry(): void
    {
        $this->supervisor->syncRoles(['Controller']);
        $this->patchJson($this->url('/cancel'), ['reason' => 'No role'])->assertForbidden();
        $this->supervisor->syncRoles(['Supervisor']);
        $this->supervisor->update(['is_active' => false]);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Inactive'])->assertForbidden();
        $this->supervisor->update(['is_active' => true]);
        $this->entry->sheet->trip->update(['trip_side' => 'up']);
        $this->supervisor->supervisorProfile->update(['depot_id' => 2]);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Destination only'])->assertForbidden();
        $this->supervisor->supervisorProfile->update(['depot_id' => 1]);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Departure depot'])->assertOk();
        $this->supervisor->supervisorProfile->update(['depot_id' => 2]);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Unauthorized retry'])->assertForbidden();
    }

    public function test_reason_validation_and_unknown_trip(): void
    {
        foreach ([[], ['reason' => '   '], ['reason' => str_repeat('x', 2001)], ['reason' => []]] as $payload) {
            $this->patchJson($this->url('/cancel'), $payload)->assertUnprocessable()->assertJsonValidationErrors('reason');
        }
        $this->patchJson('/api/v1/trips/99999/cancel', ['reason' => 'Missing'])->assertNotFound();
    }

    public function test_unrostered_trip_can_be_cancelled_and_cross_depot_roster_cannot(): void
    {
        $this->roster->update(['depot_id' => 2]);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Wrong roster'])->assertForbidden();
        $this->assertSame('pending', $this->entry->fresh()->status);
        $this->entry->rosters()->detach();
        $this->patchJson($this->url('/cancel'), ['reason' => 'No roster'])->assertOk();
    }

    public function test_cancelled_resources_become_available_for_another_trip(): void
    {
        $vehicle = Vehicle::create(['depot_id' => 1, 'status' => 'Active', 'vehicle_no' => 'TEST-01', 'vehicle_code' => 'V01']);
        $this->roster->update(['vehicle_id' => $vehicle->id]);
        $this->entry->update(['vehicle_id' => $vehicle->id]);
        $other = $this->entry->replicate();
        $other->fill(['driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => null])->save();
        $this->rosterFor($this->freeDriver)->tripSheetEntries()->attach($other);
        $url = '/api/v1/trips/'.$other->id;
        $this->getJson($url.'/available-drivers')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson($url.'/available-vehicles')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson($this->url('/cancel'), ['reason' => 'Release resources'])->assertOk();
        $this->getJson($url.'/available-drivers')->assertOk()->assertJsonPath('data.0.driver_profile_id', $this->oldDriver->id);
        $this->getJson($url.'/available-vehicles')->assertOk()->assertJsonPath('data.0.vehicle_id', $vehicle->id);
    }

    public function test_cancelled_trip_rejects_assignment_and_verification_apis(): void
    {
        $this->notificationSchema();
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('depots')->insert([['id' => 1, 'name' => 'Departure'], ['id' => 2, 'name' => 'Destination']]);
        Schema::create('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_sheet_entry_id');
        });
        $this->patchJson($this->url('/cancel'), ['reason' => 'Cancelled'])->assertOk();
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => 1])->assertUnprocessable();
        $this->postJson('/api/v1/trips/verify-driver', ['trip_id' => $this->entry->id,
            'driver_code' => $this->oldDriver->user->code, 'is_driver_verified' => true])->assertUnprocessable();
        $this->postJson('/api/v1/trips/start-verification', ['trip_id' => $this->entry->id])->assertUnprocessable();
    }

    public function test_notification_failure_keeps_cancellation_committed(): void
    {
        $this->mock(TripCancellationNotifier::class)->shouldReceive('send')->once()->andThrow(new \RuntimeException('Notification unavailable'));
        $this->patchJson($this->url('/cancel'), ['reason' => 'Cancel'])->assertOk()->assertJsonPath('notifications.failed', 1);
        $this->assertSame('cancelled', $this->entry->fresh()->status);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Retry'])->assertOk()->assertJsonPath('notifications.failed', 0);
    }

    public function test_cancelled_entries_are_excluded_from_both_reminder_commands(): void
    {
        $this->notificationSchema();
        $this->patchJson($this->url('/cancel'), ['reason' => 'No reminder'])->assertOk();
        $this->mock(FirebaseMessaging::class)->shouldNotReceive('send');
        $this->artisan('drivers:today-trip-notifications', ['--date' => '2026-09-08'])->assertExitCode(0);
        $this->artisan('controllers:today-trip-notifications', ['--date' => '2026-09-08'])->assertExitCode(0);
        $this->assertSame(0, DB::table('driver_trip_notification_logs')->count());
        $this->assertSame(0, DB::table('today_trip_notification_logs')->count());
    }

    public function test_cancellation_notification_targets_driver_and_depot_once(): void
    {
        $this->notificationSchema();
        UserDeviceToken::create(['user_id' => $this->oldDriver->user_id, 'app_type' => 'driver', 'token' => 'driver-token']);
        UserDeviceToken::create(['user_id' => $this->supervisor->id, 'app_type' => 'operations', 'token' => 'supervisor-token']);
        $firebase = $this->mock(FirebaseMessaging::class);
        $firebase->shouldReceive('send')->twice()->withArgs(fn ($token, $title, $body, $data, $appType) => in_array($token, ['driver-token', 'supervisor-token'], true)
            && $data['type'] === 'trip_cancelled' && $data['trip_sheet_entry_id'] === $this->entry->id)
            ->andReturn(new \Illuminate\Http\Client\Response(new Response(200, [], '{"name":"sent"}')));
        $this->app->instance(TripCancellationNotifier::class, new TripCancellationNotifier($firebase));
        $this->patchJson($this->url('/cancel'), ['reason' => 'Cancel'])->assertOk()->assertJsonPath('notifications.sent', 2);
        $this->patchJson($this->url('/cancel'), ['reason' => 'Retry'])->assertOk()->assertJsonPath('notifications.sent', 0);
    }

    private function notificationSchema(): void
    {
        Schema::create('controller_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('depot_id');
        });
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token');
            $table->string('app_type');
            $table->timestamps();
        });
        foreach (['driver_trip_notification_logs' => 'driver_profile_id', 'today_trip_notification_logs' => 'user_id'] as $name => $owner) {
            Schema::create($name, function (Blueprint $table) use ($owner) {
                $table->id();
                $table->unsignedBigInteger($owner);
                $table->date('trip_date');
                $table->integer('trip_count');
                $table->integer('sent_count')->default(0);
                $table->string('status')->default('pending');
                $table->text('error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
