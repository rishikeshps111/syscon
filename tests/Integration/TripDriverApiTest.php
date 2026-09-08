<?php

namespace Tests\Integration;

use App\Http\Controllers\Api\V1\TripController;
use App\Http\Middleware\TrackStaffUserLog;
use App\Models\Leave;
use App\Models\Roster;
use App\Services\RosterReassignmentNotifier;
use Spatie\Permission\Models\Permission;
use Tests\Support\TripAssignmentTestCase;

class TripDriverApiTest extends TripAssignmentTestCase
{
    public function test_lists_only_other_eligible_depot_drivers(): void
    {
        $this->driver(['depot_id' => 2]);
        $this->driver(['expiry_date' => '2026-09-07']);
        $inactive = $this->driver();
        $inactive->user->update(['is_active' => false]);
        $busy = $this->driver();
        $this->rosterFor($busy);
        $leave = $this->driver();
        Leave::create(['user_id' => $leave->user_id, 'leave_for' => 'driver', 'status' => 'Approved',
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'shift' => 'Morning']);

        $this->getJson($this->url('/available-drivers'))->assertOk()->assertExactJson([
            'success' => true, 'data' => [[
                'driver_profile_id' => $this->freeDriver->id, 'name' => $this->freeDriver->user->name,
                'code' => $this->freeDriver->user->code, 'depot_id' => 1,
            ]],
        ]);
    }

    public function test_changes_driver_and_clears_verification(): void
    {
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertOk()
            ->assertJsonPath('data.driver_profile_id', $this->freeDriver->id);
        $this->assertSame($this->freeDriver->id, (int) $this->roster->fresh()->driver_profile_id);
        $entry = $this->entry->fresh();
        $this->assertFalse($entry->is_driver_verified);
        $this->assertNull($entry->driver_verified_by);
        $this->assertNull($entry->driver_verified_at);
        $this->assertSame(1, Roster::count());
    }

    public function test_shared_roster_changes_only_selected_trip_even_when_another_trip_has_started(): void
    {
        $other = $this->entry->replicate();
        $other->fill(['status' => 'initial_verification_completed', 'actual_start_time' => '07:00'])->save();
        $this->roster->tripSheetEntries()->attach($other);
        $this->roster->update(['status' => 'in_progress']);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertOk();

        $this->assertSame($this->oldDriver->id, (int) $other->fresh()->driver_profile_id);
        $this->assertSame([$other->id], $this->roster->tripSheetEntries()->pluck('trip_sheet_entries.id')->all());
        $newRoster = $this->entry->fresh()->rosters->sole();
        $this->assertSame($this->freeDriver->id, (int) $newRoster->driver_profile_id);
        $this->assertSame('assigned', $newRoster->status);
        $this->assertSame($this->oldDriver->id, (int) $this->roster->fresh()->driver_profile_id);
    }

    public function test_rechecks_availability_after_listing(): void
    {
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(1, 'data');
        $this->rosterFor($this->freeDriver);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_rejects_started_or_completed_trip_even_if_status_is_stale(): void
    {
        foreach ([['status' => 'initial_verification_completed'], ['actual_start_time' => '08:00'],
            ['is_initial_verified' => true], ['is_final_verified' => true], ['actual_reach_time' => '10:00'], ['status' => 'cancelled']] as $changes) {
            $this->entry->update(array_merge(['status' => 'pending', 'actual_start_time' => null, 'actual_reach_time' => null,
                'is_initial_verified' => false, 'is_final_verified' => false], $changes));
            $this->getJson($this->url('/available-drivers'))->assertUnprocessable();
            $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        }
    }

    public function test_rejects_wrong_role_and_destination_depot(): void
    {
        $this->supervisor->syncRoles(['Controller']);
        $this->getJson($this->url('/available-drivers'))->assertForbidden();
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertForbidden();
        $this->supervisor->syncRoles(['Supervisor']);
        $this->entry->sheet->trip->update(['trip_side' => 'up']);
        $this->supervisor->supervisorProfile->update(['depot_id' => 2]);
        $this->getJson($this->url('/available-drivers'))->assertForbidden();
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertForbidden();
    }

    public function test_rejects_same_driver_wrong_depot_and_invalid_payload(): void
    {
        foreach ([$this->oldDriver, $this->driver(['depot_id' => 2])] as $driver) {
            $this->patchJson($this->url('/driver'), ['driver_profile_id' => $driver->id])->assertUnprocessable();
        }
        $this->patchJson($this->url('/driver'), [])->assertUnprocessable();
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => 99999])->assertUnprocessable();
    }

    public function test_overnight_overlap_blocks_but_touching_shifts_are_free(): void
    {
        $this->roster->update(['shift_start_time' => '01:00', 'shift_end_time' => '08:00', 'shift_type' => 'night']);
        $busy = $this->rosterFor($this->freeDriver, ['duty_date' => '2026-09-07', 'shift_start_time' => '22:00', 'shift_end_time' => '02:00']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(0, 'data');
        $busy->update(['shift_end_time' => '01:00']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_completed_roster_is_free_but_overdue_in_progress_roster_is_not(): void
    {
        $busy = $this->rosterFor($this->freeDriver, ['status' => 'completed']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(1, 'data');
        $busy->update(['status' => 'in_progress', 'duty_date' => '2026-09-05']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_missing_or_ambiguous_roster_is_rejected(): void
    {
        $this->roster->tripSheetEntries()->detach($this->entry);
        $this->getJson($this->url('/available-drivers'))->assertUnprocessable();
        $this->roster->tripSheetEntries()->attach($this->entry);
        $this->rosterFor($this->oldDriver)->tripSheetEntries()->attach($this->entry);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
    }

    public function test_expiry_is_checked_through_overnight_duty_end(): void
    {
        $this->roster->update(['shift_start_time' => '22:00', 'shift_end_time' => '06:00']);
        $this->freeDriver->update(['expiry_date' => '2026-09-08']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_old_driver_cannot_fall_back_to_original_assignment_for_verification(): void
    {
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertOk();
        $method = new \ReflectionMethod(TripController::class, 'driverBelongsToTrip');
        $controller = app(TripController::class);
        $this->assertFalse($method->invoke($controller, $this->entry->fresh(), $this->oldDriver->id));
        $this->assertTrue($method->invoke($controller, $this->entry->fresh(), $this->freeDriver->id));
    }

    public function test_running_trip_blocks_driver_without_an_active_roster(): void
    {
        $running = $this->entry->replicate();
        $running->fill(['driver_profile_id' => $this->freeDriver->id, 'actual_start_time' => '07:00'])->save();
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
    }

    public function test_leave_on_another_shift_or_cancelled_leave_does_not_block(): void
    {
        $leave = Leave::create(['user_id' => $this->freeDriver->user_id, 'leave_for' => 'driver', 'status' => 'Approved',
            'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'shift' => 'Night']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(1, 'data');
        $leave->update(['shift' => 'Morning', 'status' => 'Cancelled']);
        $this->getJson($this->url('/available-drivers'))->assertOk()->assertJsonCount(1, 'data');
        $leave->update(['status' => 'Auto Marked']);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
    }

    public function test_roster_web_reassignment_rejects_started_trip_and_rolls_back(): void
    {
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $permission = Permission::create(['name' => 'rosters.edit', 'guard_name' => 'web', 'group_name' => 'Roster']);
        $this->supervisor->givePermissionTo($permission);
        $this->actingAs($this->supervisor, 'web');
        $this->entry->update(['actual_start_time' => '08:00']);
        $this->postJson('/rosters/'.$this->roster->id.'/reassign-driver', ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        $this->assertSame($this->oldDriver->id, (int) $this->roster->fresh()->driver_profile_id);
    }

    public function test_invalid_licence_inactive_driver_and_missing_window_are_rejected(): void
    {
        $this->freeDriver->update(['expiry_date' => null]);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        $this->freeDriver->update(['expiry_date' => '2027-01-01']);
        $this->freeDriver->user->update(['is_active' => false]);
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertUnprocessable();
        $this->roster->update(['shift_start_time' => '']);
        $this->getJson($this->url('/available-drivers'))->assertUnprocessable();
    }

    public function test_notification_failure_does_not_undo_committed_assignment(): void
    {
        $this->mock(RosterReassignmentNotifier::class)->shouldReceive('sendTripDriverChange')->andThrow(new \RuntimeException('Notification unavailable'));
        $this->patchJson($this->url('/driver'), ['driver_profile_id' => $this->freeDriver->id])->assertOk()->assertJsonPath('notifications.failed', 1);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }
}
