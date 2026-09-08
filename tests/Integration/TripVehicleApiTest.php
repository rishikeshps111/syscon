<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\Roster;
use App\Models\TripAssignment;
use App\Models\TripSheetEntry;
use App\Models\Vehicle;
use App\Services\RosterReassignmentNotifier;
use Spatie\Permission\Models\Permission;
use Tests\Support\TripAssignmentTestCase;

class TripVehicleApiTest extends TripAssignmentTestCase
{
    private Vehicle $oldVehicle;

    private Vehicle $freeVehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oldVehicle = $this->vehicle();
        $this->freeVehicle = $this->vehicle();
        $this->entry->update([
            'vehicle_id' => $this->oldVehicle->id,
            'is_vehicle_verified' => true,
            'vehicle_verified_by' => 'Supervisor',
            'vehicle_verified_at' => now(),
        ]);
        $this->roster->update(['vehicle_id' => $this->oldVehicle->id, 'attendance_status' => 'present']);
    }

    public function test_lists_only_other_active_free_vehicles_from_the_depot(): void
    {
        $this->vehicle(['depot_id' => 2]);
        foreach (['Inactive', 'Under Maintenance', 'Scrap'] as $status) {
            $this->vehicle(['status' => $status]);
        }
        $busy = $this->vehicle();
        $this->rosterFor($this->freeDriver, ['vehicle_id' => $busy->id]);
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertExactJson([
            'success' => true,
            'data' => [[
                'vehicle_id' => $this->freeVehicle->id,
                'vehicle_no' => $this->freeVehicle->vehicle_no,
                'vehicle_code' => $this->freeVehicle->vehicle_code,
                'depot_id' => 1,
            ]],
        ]);
    }

    public function test_changes_vehicle_and_clears_only_vehicle_verification(): void
    {
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertOk()
            ->assertJsonPath('data.vehicle_id', $this->freeVehicle->id);
        $entry = $this->entry->fresh();
        $this->assertSame($this->freeVehicle->id, (int) $entry->vehicle_id);
        $this->assertSame($this->freeVehicle->id, (int) $this->roster->fresh()->vehicle_id);
        $this->assertFalse($entry->is_vehicle_verified);
        $this->assertNull($entry->vehicle_verified_by);
        $this->assertNull($entry->vehicle_verified_at);
        $this->assertSame($this->oldDriver->id, (int) $entry->driver_profile_id);
        $this->assertTrue($entry->is_driver_verified);
        $this->assertSame('present', $this->roster->fresh()->attendance_status);
        $this->assertSame(1, Roster::count());
    }

    public function test_shared_roster_preserves_other_trips_and_driver_attendance(): void
    {
        $other = $this->entry->replicate();
        $other->fill(['status' => 'initial_verification_completed', 'actual_start_time' => '07:00'])->save();
        $this->roster->tripSheetEntries()->attach($other);
        $this->roster->update(['status' => 'in_progress']);
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertOk();

        $this->assertSame($this->oldVehicle->id, (int) $other->fresh()->vehicle_id);
        $this->assertSame([$other->id], $this->roster->tripSheetEntries()->pluck('trip_sheet_entries.id')->all());
        $replacement = $this->entry->fresh()->rosters->sole();
        $this->assertSame($this->freeVehicle->id, (int) $replacement->vehicle_id);
        $this->assertSame($this->oldDriver->id, (int) $replacement->driver_profile_id);
        $this->assertSame('present', $replacement->attendance_status);
        $this->assertSame('assigned', $replacement->status);
        $this->assertSame($this->roster->shift_start_time, $replacement->shift_start_time);
        $this->assertSame($this->oldVehicle->id, (int) $this->roster->fresh()->vehicle_id);
    }

    public function test_rechecks_conflicts_after_vehicle_list_was_loaded(): void
    {
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertJsonCount(1, 'data');
        $this->rosterFor($this->freeDriver, ['vehicle_id' => $this->freeVehicle->id]);
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable()
            ->assertJsonValidationErrors('vehicle_id');
        $this->assertSame($this->oldVehicle->id, (int) $this->entry->fresh()->vehicle_id);
    }

    public function test_rejects_same_wrong_depot_inactive_and_nonexistent_vehicles(): void
    {
        $wrongDepot = $this->vehicle(['depot_id' => 2]);
        $inactive = $this->vehicle(['status' => 'Under Maintenance']);
        foreach ([$this->oldVehicle->id, $wrongDepot->id, $inactive->id, 99999] as $id) {
            $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        }
        $this->patchJson($this->url('/vehicle'), [])->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
    }

    public function test_rejects_wrong_role_inactive_supervisor_and_destination_only_access(): void
    {
        $this->supervisor->syncRoles(['Controller']);
        $this->getJson($this->url('/available-vehicles'))->assertForbidden();
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertForbidden();
        $this->supervisor->syncRoles(['Supervisor']);
        $this->supervisor->update(['is_active' => false]);
        $this->getJson($this->url('/available-vehicles'))->assertForbidden();
        $this->supervisor->update(['is_active' => true]);
        $this->entry->sheet->trip->update(['trip_side' => 'up']);
        $this->supervisor->supervisorProfile->update(['depot_id' => 2]);
        $this->getJson($this->url('/available-vehicles'))->assertForbidden();
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertForbidden();
    }

    public function test_started_completed_and_cancelled_trips_cannot_change_vehicle(): void
    {
        foreach ([['status' => 'initial_verification_completed'], ['actual_start_time' => '08:00'],
            ['is_initial_verified' => true], ['is_final_verified' => true], ['actual_reach_time' => '10:00'], ['status' => 'cancelled']] as $changes) {
            $this->entry->update(array_merge(['status' => 'pending', 'actual_start_time' => null, 'actual_reach_time' => null,
                'is_initial_verified' => false, 'is_final_verified' => false], $changes));
            $this->getJson($this->url('/available-vehicles'))->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
            $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable();
        }
    }

    public function test_overnight_conflicts_and_boundary_availability(): void
    {
        $this->roster->update(['shift_start_time' => '01:00', 'shift_end_time' => '08:00']);
        $busy = $this->rosterFor($this->freeDriver, ['vehicle_id' => $this->freeVehicle->id, 'duty_date' => '2026-09-07',
            'shift_start_time' => '22:00', 'shift_end_time' => '02:00']);
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertJsonCount(0, 'data');
        $busy->update(['shift_end_time' => '01:00']);
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_completed_rosters_release_vehicle_but_overdue_rosters_and_running_trips_block(): void
    {
        $busy = $this->rosterFor($this->freeDriver, ['vehicle_id' => $this->freeVehicle->id, 'status' => 'completed']);
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertJsonCount(1, 'data');
        $busy->update(['status' => 'in_progress', 'duty_date' => '2026-09-05']);
        $this->getJson($this->url('/available-vehicles'))->assertOk()->assertJsonCount(0, 'data');
        $busy->delete();
        $running = $this->entry->replicate();
        $running->fill(['vehicle_id' => $this->freeVehicle->id, 'actual_start_time' => '07:00'])->save();
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable();
    }

    public function test_rejects_missing_ambiguous_inactive_or_incomplete_roster(): void
    {
        $this->roster->tripSheetEntries()->detach($this->entry);
        $this->getJson($this->url('/available-vehicles'))->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
        $this->roster->tripSheetEntries()->attach($this->entry);
        $other = $this->rosterFor($this->freeDriver);
        $other->tripSheetEntries()->attach($this->entry);
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable();
        $other->delete();
        $this->roster->update(['status' => 'completed']);
        $this->getJson($this->url('/available-vehicles'))->assertUnprocessable();
        $this->roster->update(['status' => 'assigned', 'shift_start_time' => '']);
        $this->getJson($this->url('/available-vehicles'))->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
    }

    public function test_vehicle_code_search_uses_replacement_over_old_recurring_assignment(): void
    {
        TripAssignment::create(['trip_id' => $this->entry->sheet->trip_id, 'vehicle_id' => $this->oldVehicle->id,
            'from_date' => '2026-09-01', 'to_date' => '2026-09-30']);
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertOk();
        $this->assertFalse(TripSheetEntry::forVehicleCode($this->oldVehicle->vehicle_code)->whereKey($this->entry->id)->exists());
        $this->assertTrue(TripSheetEntry::forVehicleCode($this->freeVehicle->vehicle_code)->whereKey($this->entry->id)->exists());
        $this->entry->update(['vehicle_id' => null]);
        $this->assertFalse(TripSheetEntry::forVehicleCode($this->oldVehicle->vehicle_code)->whereKey($this->entry->id)->exists());
        $this->assertTrue(TripSheetEntry::forVehicleCode($this->freeVehicle->vehicle_code)->whereKey($this->entry->id)->exists());
        $this->entry->rosters()->detach();
        $this->assertTrue(TripSheetEntry::forVehicleCode($this->oldVehicle->vehicle_code)->whereKey($this->entry->id)->exists());
    }

    public function test_web_vehicle_reassignment_rolls_back_started_trip_and_shares_eligibility_rules(): void
    {
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $permission = Permission::create(['name' => 'rosters.edit', 'guard_name' => 'web', 'group_name' => 'Roster']);
        $this->supervisor->givePermissionTo($permission);
        $this->actingAs($this->supervisor, 'web');
        $this->entry->update(['actual_start_time' => '08:00']);
        $this->postJson('/rosters/'.$this->roster->id.'/reassign-vehicle', ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable();
        $this->assertSame($this->oldVehicle->id, (int) $this->roster->fresh()->vehicle_id);
        $this->entry->update(['actual_start_time' => null]);
        $this->freeVehicle->update(['depot_id' => 2]);
        $this->postJson('/rosters/'.$this->roster->id.'/reassign-vehicle', ['vehicle_id' => $this->freeVehicle->id])->assertUnprocessable();
    }

    public function test_notification_failure_does_not_undo_vehicle_change(): void
    {
        $this->mock(RosterReassignmentNotifier::class)->shouldReceive('sendTripVehicleChange')->andThrow(new \RuntimeException('Notification unavailable'));
        $this->patchJson($this->url('/vehicle'), ['vehicle_id' => $this->freeVehicle->id])->assertOk()->assertJsonPath('notifications.failed', 1);
        $this->assertSame($this->freeVehicle->id, (int) $this->entry->fresh()->vehicle_id);
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::create(array_merge(['depot_id' => 1, 'vehicle_no' => fake()->unique()->bothify('TEST-####'),
            'vehicle_code' => fake()->unique()->uuid(), 'status' => 'Active'], $attributes));
    }
}
