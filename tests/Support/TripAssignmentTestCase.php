<?php

namespace Tests\Support;

use App\Models\DriverProfile;
use App\Models\Roster;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Services\RosterReassignmentNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class TripAssignmentTestCase extends TestCase
{
    protected User $supervisor;

    protected DriverProfile $oldDriver;

    protected DriverProfile $freeDriver;

    protected TripSheetEntry $entry;

    protected Roster $roster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 8)->startOfDay());
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        // The historical migration chain contains MySQL-only queries. Use a focused SQLite schema
        // to exercise real routes, authorization, queries, transactions and roster relationships.
        $this->createSchema();
        foreach (['Supervisor', 'Driver', 'Controller'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
        $this->supervisor = User::factory()->create(['is_active' => true]);
        $this->supervisor->assignRole('Supervisor');
        SupervisorProfile::create(['user_id' => $this->supervisor->id, 'depot_id' => 1]);
        $this->oldDriver = $this->driver();
        $this->freeDriver = $this->driver();
        $trip = Trip::create(['depot_id' => 1, 'from_depot_id' => 1, 'to_depot_id' => 2, 'trip_side' => 'both', 'is_active' => true]);
        $sheet = TripSheet::create(['trip_id' => $trip->id, 'date' => '2026-09-08', 'status' => 'pending']);
        $this->entry = TripSheetEntry::create([
            'trip_sheet_id' => $sheet->id, 'status' => 'pending', 'driver_profile_id' => $this->oldDriver->id,
            'is_driver_verified' => true, 'driver_verified_by' => 'Supervisor', 'driver_verified_at' => now(),
        ]);
        $this->roster = $this->rosterFor($this->oldDriver);
        $this->roster->tripSheetEntries()->attach($this->entry);
        $this->mock(RosterReassignmentNotifier::class)->shouldReceive('sendTripDriverChange', 'sendTripVehicleChange')->andReturn(['sent' => 0, 'failed' => 0]);
        Sanctum::actingAs($this->supervisor);
    }

    protected function url(string $suffix): string
    {
        return '/api/v1/trips/'.$this->entry->id.$suffix;
    }

    protected function driver(array $attributes = []): DriverProfile
    {
        $user = User::factory()->create(['is_active' => true, 'code' => fake()->unique()->uuid()]);
        $user->assignRole('Driver');

        return DriverProfile::create(array_merge(['user_id' => $user->id, 'depot_id' => 1, 'expiry_date' => '2027-01-01'], $attributes));
    }

    protected function rosterFor(DriverProfile $driver, array $attributes = []): Roster
    {
        return Roster::create(array_merge(['depot_id' => 1, 'driver_profile_id' => $driver->id, 'duty_date' => '2026-09-08',
            'shift_type' => 'morning', 'shift_start_time' => '08:00', 'shift_end_time' => '16:00', 'status' => 'assigned'], $attributes));
    }

    protected function createSchema(): void
    {
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        foreach (['driver_profiles', 'supervisor_profiles'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->unsignedBigInteger('depot_id');
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('depot_id');
            $table->unsignedBigInteger('from_depot_id');
            $table->unsignedBigInteger('to_depot_id');
            $table->string('trip_side');
            $table->boolean('is_active');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('trip_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained();
            $table->date('date');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('trip_sheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_sheet_id')->constrained();
            $table->foreignId('driver_profile_id')->nullable()->constrained();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_vehicle_verified')->default(false);
            $table->string('vehicle_verified_by')->nullable();
            $table->timestamp('vehicle_verified_at')->nullable();
            $table->string('status');
            $table->time('actual_start_time')->nullable();
            $table->time('actual_reach_time')->nullable();
            $table->boolean('is_initial_verified')->default(false);
            $table->boolean('is_final_verified')->default(false);
            $table->boolean('is_driver_verified')->default(false);
            $table->string('driver_verified_by')->nullable();
            $table->timestamp('driver_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->unsignedBigInteger('depot_id');
            $table->foreignId('driver_profile_id')->nullable()->constrained();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->date('duty_date');
            $table->string('shift_type');
            $table->time('shift_start_time');
            $table->time('shift_end_time');
            $table->string('status');
            $table->string('attendance_status')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        (require database_path('migrations/2026_06_08_000001_create_roster_trip_sheet_entries_table.php'))->up();
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('leave_for');
            $table->string('status');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->date('leave_date')->nullable();
            $table->string('shift')->nullable();
            $table->timestamps();
        });
        Schema::create('prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('prefix');
            $table->boolean('is_active');
        });
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('financial_year')->nullable();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('depot_id');
            $table->string('vehicle_code')->unique();
            $table->string('vehicle_no');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained();
            $table->foreignId('vehicle_id')->nullable()->constrained();
            $table->date('from_date');
            $table->date('to_date');
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_08_000001_add_cancellation_details_to_trip_sheet_entries.php'))->up();
    }
}
