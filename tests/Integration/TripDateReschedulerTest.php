<?php

namespace Tests\Integration;

use App\Models\{Depot, Roster, ServiceCode, Trip, TripAssignment, TripNature, TripSheet, TripSheetEntry, Vehicle};
use App\Models\Route as RouteModel;
use App\Http\Middleware\TrackStaffUserLog;
use App\Http\Controllers\TripController;
use App\Http\Requests\StoreRosterRequest;
use App\Services\{MonthlyRosterImport, TripDateRescheduler, TripDriverAssignment, TripRosterAssignment, TripServiceCodes, TripVehicleAssignment};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Validator};
use Illuminate\Validation\ValidationException;
use Tests\Support\TripAssignmentTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TripDateReschedulerTest extends TripAssignmentTestCase
{
    private Trip $trip;

    public static function servicePrefixes(): array
    {
        return [['ES'], ['ED'], ['EX'], ['EH']];
    }

    private function selectServicePrefix(string $prefix): ServiceCode
    {
        $service = ServiceCode::create(['title' => $prefix, 'is_active' => true]);
        $this->trip->update(['service_code_id' => $service->id]);
        $this->trip->unsetRelation('serviceCode');
        return $service;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('servicePrefixes')]
    public function test_configured_template_and_import_use_selected_ser_prefix(string $prefix): void
    {
        $this->selectServicePrefix(' '.$prefix.' ');
        $start = new Depot(['name' => 'Start', 'short_name' => 'ST']);
        $end = new Depot(['name' => 'End', 'short_name' => 'EN']);
        $route = new RouteModel;
        $route->setRelation('startPoint', $start)->setRelation('endPoint', $end);
        $route->setRelation('stops', collect());
        $this->trip->setRelation('route', $route);
        $this->trip->setRelation('tripNature', new TripNature(['title' => 'Express']));
        $this->trip->forceFill(['title' => 'Test Route', 'total_trips' => 2, 'rounds_per_trip' => 2, 'schedule_km' => 40]);
        $controller = new TripController;
        $templateMethod = new \ReflectionMethod($controller, 'configuredTripSheetTemplate');
        $readMethod = new \ReflectionMethod($controller, 'readConfiguredTripSheet');
        $book = $templateMethod->invoke($controller, $this->trip);
        $sheet = $book->getActiveSheet();
        $this->assertSame($prefix.'01', $sheet->getCell('B5')->getValue());
        $this->assertSame($prefix.'02', $sheet->getCell('C5')->getValue());
        foreach (['B', 'C'] as $column) {
            foreach (['08:00', '09:00', '09:15', '10:00', '10:30', '11:00', '11:15', '12:00'] as $offset => $time) $sheet->setCellValue($column.(8 + $offset), $time);
        }
        $file = tempnam(sys_get_temp_dir(), 'ser-template-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($file);
            $rows = $readMethod->invoke($controller, $this->trip, $file);
            $this->assertSame([$prefix.'01', $prefix.'01', $prefix.'02', $prefix.'02'], array_column($rows, 'service_code'));
            $this->assertSame([1, 2, 3, 4], array_column($rows, 'sequence'));
            $this->assertSame([1, 2, 1, 2], array_column($rows, 'round_no'));
            $this->assertSame('08:00', $rows[0]['departure_time']);
            $this->assertSame('12:00', $rows[3]['arrival_time']);
            $sheet->setCellValue('B5', 'WRONG01');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($file);
            try {
                $readMethod->invoke($controller, $this->trip, $file);
                $this->fail('Configured import accepted a stale SER prefix.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('B5 must contain '.$prefix.'01', implode(' ', $exception->errors()['sheet_file']));
            }
        } finally {
            $book->disconnectWorksheets();
            unlink($file);
        }
    }

    public function test_ser_prefix_is_required_and_formatter_does_not_truncate_large_numbers(): void
    {
        $this->selectServicePrefix(' ES ');
        $service = app(TripServiceCodes::class);
        $this->assertSame('ES', $service->prefix($this->trip));
        $this->assertSame('ES01', $service->format($this->trip, 1));
        $this->assertSame('ES123', $service->format($this->trip, 123));
        $this->trip->serviceCode->update(['title' => '   ']);
        try {
            $service->prefix($this->trip);
            $this->fail('A blank selected prefix was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_code_id', $exception->errors());
        }
        $this->trip->update(['service_code_id' => null]);
        $this->trip->unsetRelation('serviceCode');
        try {
            $service->format($this->trip, 1);
            $this->fail('A missing service code selection was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_code_id', $exception->errors());
        }
    }

    public function test_trip_service_selection_change_updates_pending_ser_without_changing_roster_or_dates(): void
    {
        $this->entry->update(['service_code' => 'ED007']);
        $newService = ServiceCode::create(['title' => 'ES', 'is_active' => true]);
        $beforeRoster = $this->roster->fresh()->getAttributes();
        app(TripDateRescheduler::class)->update($this->trip, ['service_code_id' => $newService->id]);
        $this->assertSame($newService->id, (int) $this->trip->fresh()->service_code_id);
        $this->assertSame('ES007', $this->entry->fresh()->service_code);
        $this->assertSame($beforeRoster, $this->roster->fresh()->getAttributes());
        $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $preview = app(MonthlyRosterImport::class)->preview($this->supervisor, $this->trip->fresh(), [
            'line' => 2, 'trip_code' => $this->entry->code, 'date' => '2026-11-14',
            'display_date' => '14-11-2026', 'service' => 'ED007', 'driver_name' => '', 'vehicle' => '', 'error' => null,
        ]);
        $this->assertSame('issue', $preview['status']);
        $this->assertStringContainsString('Date or Service Code does not match', $preview['reason']);
    }

    public function test_ser_selection_and_correction_roll_back_when_date_move_has_conflicts(): void
    {
        $originalServiceId = $this->trip->service_code_id;
        $selected = ServiceCode::create(['title' => 'ES', 'is_active' => true]);
        $beforeRoster = $this->roster->fresh()->getAttributes();
        $this->rosterFor($this->oldDriver, ['duty_date' => '2026-12-14']);
        try {
            $this->move(['service_code_id' => $selected->id]);
            $this->fail('Conflicting date move was accepted.');
        } catch (ValidationException) {
            $this->assertSame((int) $originalServiceId, (int) $this->trip->fresh()->service_code_id);
            $this->assertSame('ED01', $this->entry->fresh()->service_code);
            $this->assertSame('2026-11-14', $this->trip->fresh()->from_date->toDateString());
            $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
            $this->assertSame($beforeRoster, $this->roster->fresh()->getAttributes());
        }
    }

    public function test_controller_update_reports_ser_changes_and_retained_history_in_json(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable();
            $table->string('title')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->unsignedBigInteger('from_depot_id')->nullable()->change();
            $table->unsignedBigInteger('to_depot_id')->nullable()->change();
        });
        $routeId = DB::table('routes')->insertGetId(['route_name' => 'Test route']);
        $selected = ServiceCode::create(['title' => 'ES', 'is_active' => true]);
        $historical = $this->entry->replicate();
        $historical->fill(['code' => 'HISTORICAL', 'service_code' => 'ED02', 'status' => 'verification_completed'])->save();
        $custom = $this->entry->replicate();
        $custom->fill(['code' => 'CUSTOM', 'service_code' => 'SPECIAL', 'status' => 'verification_completed'])->save();
        $this->actingAs($this->supervisor, 'web');
        $request = \Mockery::mock(\App\Http\Requests\UpdateTripRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['route_id' => $routeId, 'service_code_id' => $selected->id]);
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $response = (new TripController)->update($request, $this->trip);
        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertSame(['updated' => 1, 'protected' => 1, 'unrecognized' => 1], $json['service_summary']);
        $this->assertStringContainsString('SER codes updated: 1.', $json['message']);
        $this->assertStringContainsString('Historical SER codes retained: 1; custom or unrecognized codes retained: 1.', $json['message']);
        $this->assertSame('ES01', $this->entry->fresh()->service_code);
        $this->assertSame('ED02', $historical->fresh()->service_code);
        $this->assertSame('SPECIAL', $custom->fresh()->service_code);
        $this->assertSame($this->roster->id, $this->entry->rosters()->sole()->id);
    }

    public function test_stale_confirmed_assignment_is_rejected_after_ser_selection_changes(): void
    {
        $this->supervisor->givePermissionTo(Permission::create(['name' => 'rosters.create', 'guard_name' => 'web', 'group_name' => 'Roster']));
        $expected = ['code' => $this->entry->code, 'date' => '2026-11-14', 'service' => 'ED01'];
        $selected = ServiceCode::create(['title' => 'ES', 'is_active' => true]);
        app(TripDateRescheduler::class)->update($this->trip, ['service_code_id' => $selected->id]);
        $beforeRoster = $this->roster->fresh()->getAttributes();
        try {
            app(TripRosterAssignment::class)->assign($this->supervisor, $this->trip->fresh(),
                $this->entry->id, $this->freeDriver->id, null, $expected);
            $this->fail('A confirmed assignment from before the SER change was saved.');
        } catch (ValidationException $exception) {
            $messages = implode(' ', \Illuminate\Support\Arr::flatten($exception->errors()));
            $this->assertStringContainsString('changed after upload', $messages);
            $this->assertStringContainsString('Download a fresh sample', $messages);
            $this->assertSame('ES01', $this->entry->fresh()->service_code);
            $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
            $this->assertSame($beforeRoster, $this->roster->fresh()->getAttributes());
            $this->assertSame($this->roster->id, $this->entry->rosters()->sole()->id);
        }
    }

    public function test_ser_synchronization_previews_and_preserves_custom_codes_and_numeric_suffixes(): void
    {
        $this->selectServicePrefix('ES');
        $this->entry->update(['service_code' => 'ED007']);
        $previous = $this->entry->replicate();
        $previous->fill(['code' => 'OTHER', 'service_code' => 'EX12'])->save();
        $custom = $this->entry->replicate();
        $custom->fill(['code' => 'CUSTOM', 'service_code' => 'SPECIAL'])->save();
        $service = app(TripServiceCodes::class);
        $expected = ['updated' => 2, 'protected' => 0, 'unrecognized' => 1];
        $this->assertSame($expected, $service->synchronize($this->trip, 'EX', false));
        $this->assertSame('ED007', $this->entry->fresh()->service_code);
        $this->assertSame('EX12', $previous->fresh()->service_code);
        $this->assertSame($expected, $service->synchronize($this->trip, 'EX'));
        $this->assertSame('ES007', $this->entry->fresh()->service_code);
        $this->assertSame('ES12', $previous->fresh()->service_code);
        $this->assertSame('SPECIAL', $custom->fresh()->service_code);
        $this->assertSame(['updated' => 0, 'protected' => 0, 'unrecognized' => 1], $service->synchronize($this->trip, 'EX'));
    }

    public function test_ser_synchronization_skips_verified_actual_dor_sheet_and_roster_history(): void
    {
        $this->selectServicePrefix('ES');
        foreach ([['is_driver_verified' => true], ['is_vehicle_verified' => true], ['is_initial_verified' => true],
            ['is_final_verified' => true], ['actual_start_time' => '08:00'], ['actual_reach_time' => '16:00']] as $attributes) {
            $this->entry->update($attributes);
            $this->assertSame(['updated' => 0, 'protected' => 1, 'unrecognized' => 0], app(TripServiceCodes::class)->synchronize($this->trip));
            $this->assertSame('ED01', $this->entry->fresh()->service_code);
            $this->entry->update(['is_driver_verified' => false, 'is_vehicle_verified' => false, 'is_initial_verified' => false,
                'is_final_verified' => false, 'actual_start_time' => null, 'actual_reach_time' => null]);
        }
        DB::table('trip_sheet_entry_dors')->insert(['trip_sheet_entry_id' => $this->entry->id]);
        $this->assertSame(1, app(TripServiceCodes::class)->synchronize($this->trip)['protected']);
        DB::table('trip_sheet_entry_dors')->delete();
        $this->entry->sheet->update(['status' => 'verification_completed']);
        $this->assertSame(1, app(TripServiceCodes::class)->synchronize($this->trip)['protected']);
        $this->entry->sheet->update(['status' => 'pending']);
        $this->roster->update(['attendance_status' => 'present']);
        $this->assertSame(1, app(TripServiceCodes::class)->synchronize($this->trip)['protected']);
        $this->roster->update(['attendance_status' => null, 'status' => 'in_progress']);
        $this->assertSame(1, app(TripServiceCodes::class)->synchronize($this->trip)['protected']);
        $this->assertSame('ED01', $this->entry->fresh()->service_code);
    }

    public function test_new_dates_use_selected_prefix_when_source_duty_is_completed_legacy_ed(): void
    {
        $this->selectServicePrefix('ES');
        $this->entry->update(['status' => 'verification_completed', 'is_final_verified' => true, 'service_code' => 'ED03']);
        $this->entry->sheet->update(['status' => 'verification_completed']);
        $this->roster->update(['status' => 'completed']);
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $this->assertSame('ES03', $generated->service_code);
        $this->assertSame('ED03', $this->entry->fresh()->service_code);
        $this->assertNull($generated->driver_profile_id);
        $this->assertSame('unassigned', $generated->rosters()->sole()->status);
    }

    public function test_generated_ser_derives_service_index_from_sequence_and_round_count_for_custom_source(): void
    {
        $this->selectServicePrefix('EH');
        $this->trip->forceFill(['rounds_per_trip' => 2]);
        $source = new TripSheetEntry(['service_code' => 'CUSTOM', 'trip_order_sequence_no' => 5]);
        $this->assertSame('EH03', app(TripServiceCodes::class)->copiedCode($this->trip, $source));
    }

    public function test_ser_maintenance_command_previews_then_requires_active_administrator_to_apply(): void
    {
        $this->selectServicePrefix('ES');
        $this->artisan('trips:sync-service-codes', ['trip' => $this->trip->code])->assertSuccessful();
        $this->assertSame('ED01', $this->entry->fresh()->service_code);
        $this->artisan('trips:sync-service-codes', ['trip' => $this->trip->code, '--apply' => true])->assertFailed();
        $this->assertSame('ED01', $this->entry->fresh()->service_code);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->supervisor->assignRole('Super Admin');
        $this->artisan('trips:sync-service-codes', ['trip' => $this->trip->code, '--apply' => true, '--actor' => $this->supervisor->id])->assertSuccessful();
        $this->assertSame('ES01', $this->entry->fresh()->service_code);
        $this->assertSame($this->supervisor->id, (int) $this->trip->fresh()->updated_by);
        $this->entry->refresh()->update(['service_code' => 'ED01']);
        $this->supervisor->update(['is_active' => false]);
        $this->artisan('trips:sync-service-codes', ['trip' => $this->trip->code, '--apply' => true, '--actor' => $this->supervisor->id])->assertFailed();
        $this->assertSame('ED01', $this->entry->fresh()->service_code);
    }

    protected function createSchema(): void
    {
        parent::createSchema();
        Schema::table('trips', function (Blueprint $table) {
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('vehicle_classification_id')->nullable();
            $table->unsignedBigInteger('service_code_id')->nullable();
        });
        Schema::create('service_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('trip_sheets', fn (Blueprint $table) => $table->string('code')->nullable());
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->string('service_code')->nullable();
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->string('side')->nullable();
            $table->integer('trip_order_sequence_no')->nullable();
            $table->integer('round_no')->nullable();
            $table->string('trip_nature')->nullable();
            $table->decimal('schedule_km', 10, 2)->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('initial_verification_by')->nullable();
            $table->timestamp('initial_verification_at')->nullable();
            $table->unsignedBigInteger('final_verification_by')->nullable();
            $table->timestamp('final_verification_at')->nullable();
        });
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_profile_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
        Schema::table('rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('trip_assignment_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('oem_id')->nullable();
            $table->string('reporting_time')->nullable();
        });
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('oem_id')->nullable();
        });
        Schema::create('trip_sheet_entry_stop_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_sheet_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('route_stop_id')->nullable();
            $table->integer('sequence_no');
            $table->string('location_name');
            $table->string('event');
            $table->boolean('show_location')->default(true);
            $table->time('scheduled_time');
            $table->timestamps();
        });
        Schema::create('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_sheet_entry_id');
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $this->trip = $this->entry->sheet->trip;
        $serviceCode = ServiceCode::create(['title' => 'ED', 'is_active' => true]);
        $this->trip->update(['service_code_id' => $serviceCode->id]);
        $this->trip->update(['code' => 'TRP2026#0056', 'from_date' => '2026-11-14', 'to_date' => '2026-11-14', 'state_id' => 1]);
        $this->entry->sheet->update(['date' => '2026-11-14']);
        $this->entry->update(['code' => 'TRP2026#0056-0001', 'service_code' => 'ED01',
            'departure_time' => '08:00', 'arrival_time' => '16:00', 'is_driver_verified' => false,
            'driver_verified_by' => null, 'driver_verified_at' => null]);
        $this->roster->update(['duty_date' => '2026-11-14']);
    }

    private function move(array $data = []): Trip
    {
        return app(TripDateRescheduler::class)->update($this->trip, $data + [
            'from_date' => '2026-12-14', 'to_date' => '2026-12-14', 'updated_by' => $this->supervisor->id,
        ]);
    }

    private function assertMoveRejected(): void
    {
        try {
            $this->move();
            $this->fail('An unsafe reschedule was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertSame('2026-11-14', $this->trip->fresh()->from_date->toDateString());
        $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertSame('2026-11-14', $this->roster->fresh()->duty_date->toDateString());
    }

    public function test_moves_pending_dates_and_preserves_assignment_identity_and_times(): void
    {
        $assignment = TripAssignment::create(['trip_id' => $this->trip->id,
            'from_date' => '2026-11-14', 'to_date' => '2026-11-14', 'driver_profile_id' => $this->oldDriver->id]);
        $this->roster->update(['trip_assignment_id' => $assignment->id]);

        $this->move();

        $this->assertSame('2026-12-14', $this->trip->fresh()->from_date->toDateString());
        $this->assertSame('2026-12-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertSame('2026-12-14', $this->roster->fresh()->duty_date->toDateString());
        $this->assertSame('2026-12-14', $assignment->fresh()->from_date->toDateString());
        $this->assertSame('2026-12-14', $assignment->fresh()->to_date->toDateString());
        $this->assertSame($assignment->id, (int) $this->roster->fresh()->trip_assignment_id);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame('TRP2026#0056-0001', $this->entry->fresh()->code);
        $this->assertSame('08:00', substr($this->entry->fresh()->departure_time, 0, 5));
        $this->assertSame('16:00', substr($this->roster->fresh()->shift_end_time, 0, 5));
        $this->assertSame([$this->entry->id], $this->roster->tripSheetEntries()->pluck('trip_sheet_entries.id')->all());
    }

    public function test_shifts_multiday_range_backwards_without_changing_duration(): void
    {
        $this->trip->update(['to_date' => '2026-11-15']);
        $second = TripSheet::create(['trip_id' => $this->trip->id, 'date' => '2026-11-15', 'status' => 'pending']);
        $this->move(['from_date' => '2026-10-14', 'to_date' => '2026-10-15']);
        $this->assertSame('2026-10-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertSame('2026-10-15', $second->fresh()->date->toDateString());
    }

    public function test_rejects_started_completed_cancelled_and_verified_duties(): void
    {
        foreach ([['status' => 'verification_completed'], ['status' => 'cancelled'],
            ['actual_start_time' => '08:00'], ['is_initial_verified' => true], ['is_final_verified' => true],
            ['is_driver_verified' => true], ['is_vehicle_verified' => true]] as $attributes) {
            // Database update avoids the cancelled-entry model guard while testing independent historical states.
            $this->entry->newQuery()->whereKey($this->entry->id)->update($attributes);
            $this->assertMoveRejected();
            $this->entry->newQuery()->whereKey($this->entry->id)->update([
                'status' => 'pending', 'actual_start_time' => null, 'is_initial_verified' => false, 'is_final_verified' => false,
                'is_driver_verified' => false, 'is_vehicle_verified' => false,
            ]);
        }
    }

    public function test_rejects_recorded_attendance_and_in_progress_rosters(): void
    {
        $this->roster->update(['attendance_status' => 'present']);
        $this->assertMoveRejected();
        $this->roster->update(['attendance_status' => null, 'status' => 'in_progress']);
        $this->assertMoveRejected();
    }

    public function test_pending_duty_with_dor_history_cannot_move(): void
    {
        DB::table('trip_sheet_entry_dors')->insert(['trip_sheet_entry_id' => $this->entry->id]);
        $this->assertMoveRejected();
        $this->assertSame(1, $this->entry->dor()->count());
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_inheritance_migration_preserves_existing_rosters_and_generated_opt_out(): void
    {
        $migration = require database_path('migrations/2026_11_17_000001_add_inherit_trip_assignment_to_rosters.php');
        $before = $this->roster->fresh()->getAttributes();
        unset($before['inherit_trip_assignment']);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('rosters', 'inherit_trip_assignment'));
        $migration->up();
        $migrated = $this->roster->fresh();
        $this->assertTrue($migrated->inherit_trip_assignment);
        $after = $migrated->getAttributes();
        unset($after['inherit_trip_assignment']);
        $this->assertSame($before, $after);
        $this->assertSame([$this->entry->id], $migrated->tripSheetEntries()->pluck('trip_sheet_entries.id')->all());
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $generatedRoster = $generated->rosters()->sole();
        $this->assertFalse($generatedRoster->fresh()->inherit_trip_assignment);
        $this->assertTrue($migrated->fresh()->inherit_trip_assignment);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('rosters', 'inherit_trip_assignment'));
        $this->assertSame(2, Roster::count());
        $this->assertSame(2, TripSheetEntry::count());
        $this->assertSame($this->oldDriver->id, (int) $this->roster->fresh()->driver_profile_id);
        $this->assertSame('assigned', $this->roster->fresh()->status);
        $this->assertSame('unassigned', $generatedRoster->fresh()->status);
        $this->assertNull($generatedRoster->fresh()->driver_profile_id);
        $this->assertNull($generatedRoster->fresh()->vehicle_id);
    }

    public function test_rejects_driver_and_vehicle_conflicts_on_target_date(): void
    {
        $assignment = TripAssignment::create(['trip_id' => $this->trip->id,
            'from_date' => '2026-11-14', 'to_date' => '2026-11-14', 'driver_profile_id' => $this->oldDriver->id]);
        $conflict = $this->rosterFor($this->oldDriver, ['duty_date' => '2026-12-14']);
        $this->assertMoveRejected();
        $this->assertSame('2026-11-14', $assignment->fresh()->from_date->toDateString());
        $vehicle = Vehicle::create(['depot_id' => 1, 'status' => 'Active', 'vehicle_no' => 'BUS', 'vehicle_code' => 'V']);
        $this->entry->update(['vehicle_id' => $vehicle->id]);
        $this->roster->update(['vehicle_id' => $vehicle->id]);
        $conflict->update(['driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => $vehicle->id]);
        $this->assertMoveRejected();
    }

    public function test_overnight_duty_requires_licence_valid_through_following_day(): void
    {
        $this->entry->update(['departure_time' => '23:00', 'arrival_time' => '02:00']);
        $this->roster->update(['shift_start_time' => '23:00', 'shift_end_time' => '02:00']);
        $this->oldDriver->update(['expiry_date' => '2026-12-14']);
        $this->assertMoveRejected();
        $this->oldDriver->update(['expiry_date' => '2026-12-15']);
        $this->move();
        $this->assertSame('2026-12-14', $this->roster->fresh()->duty_date->toDateString());
        $this->assertSame('02:00', substr($this->entry->fresh()->arrival_time, 0, 5));
    }

    public function test_rejects_roster_shared_with_another_trip(): void
    {
        $otherTrip = $this->trip->replicate();
        $otherTrip->code = 'OTHER';
        $otherTrip->save();
        $sheet = TripSheet::create(['trip_id' => $otherTrip->id, 'date' => '2026-11-14', 'status' => 'pending']);
        $entry = TripSheetEntry::create(['trip_sheet_id' => $sheet->id, 'status' => 'pending']);
        $this->roster->tripSheetEntries()->attach($entry);
        $this->assertMoveRejected();
        $this->assertSame('2026-11-14', $sheet->fresh()->date->toDateString());
    }

    public function test_stale_roster_excel_date_is_rejected_after_reschedule(): void
    {
        $this->move();
        $preview = app(MonthlyRosterImport::class)->preview($this->supervisor, $this->trip->fresh(), [
            'line' => 2, 'trip_code' => $this->entry->code, 'date' => '2026-11-14',
            'display_date' => '14-11-2026', 'service' => 'ED01', 'driver_name' => '', 'vehicle' => '', 'error' => null,
        ]);
        $this->assertSame('issue', $preview['status']);
        $this->assertStringContainsString('Date or Service Code does not match', $preview['reason']);
    }

    public function test_fresh_excel_template_contains_rescheduled_date(): void
    {
        $this->selectServicePrefix('ES');
        $this->selectServicePrefix('ES');
        $this->supervisor->givePermissionTo(Permission::create(['name' => 'rosters.create', 'guard_name' => 'web', 'group_name' => 'Roster']));
        $this->actingAs($this->supervisor, 'web');
        $this->move();
        $response = $this->get('/trips/'.$this->trip->id.'/roster/monthly-template')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'trip-date-template-');
        try {
            file_put_contents($file, $response->streamedContent());
            $book = IOFactory::load($file);
            $this->assertSame('14-12-2026', $book->getActiveSheet()->getCell('B2')->getValue());
            $this->assertSame('ES01', $book->getActiveSheet()->getCell('C2')->getValue());
            $this->assertSame('TRP2026#0056-0001', $book->getActiveSheet()->getCell('A2')->getValue());
            $this->assertSame('ES01', $book->getActiveSheet()->getCell('C2')->getValue());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
    }

    public function test_same_date_save_automatically_reconciles_legacy_mismatch_and_is_idempotent(): void
    {
        $this->trip->update(['from_date' => '2026-12-14', 'to_date' => '2026-12-14']);
        $service = app(TripDateRescheduler::class);
        $service->update($this->trip, ['from_date' => '2026-12-14', 'to_date' => '2026-12-14']);
        $this->assertSame('2026-12-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertSame('2026-12-14', $this->roster->fresh()->duty_date->toDateString());
        $this->assertSame(1, $service->summary['moved']);
        $service->update($this->trip->fresh(), ['from_date' => '2026-12-14', 'to_date' => '2026-12-14']);
        $this->assertSame(1, TripSheetEntry::count());
        $this->assertSame(1, Roster::count());
        $this->assertSame(['moved' => 0, 'generated' => 0, 'removed' => 0, 'rosters' => 0], $service->summary);
    }

    public function test_extension_copies_schedule_and_stops_without_copying_assignments_or_history(): void
    {
        $assignment = TripAssignment::create(['trip_id' => $this->trip->id,
            'from_date' => '2026-11-14', 'to_date' => '2026-11-14', 'driver_profile_id' => $this->oldDriver->id]);
        $this->roster->update(['trip_assignment_id' => $assignment->id]);
        $this->entry->update(['side' => 'up', 'trip_order_sequence_no' => 1, 'round_no' => 1,
            'trip_nature' => 'Express', 'schedule_km' => '25.50', 'notes' => 'Historical note']);
        $this->entry->scheduleStopTimes()->create(['sequence_no' => 1, 'location_name' => 'Depot',
            'event' => 'departure', 'show_location' => true, 'scheduled_time' => '08:00', 'location_id' => 12]);
        $service = app(TripDateRescheduler::class);
        $service->update($this->trip, ['from_date' => '2026-11-14', 'to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $this->assertSame('2026-11-15', $generated->sheet->date->toDateString());
        $this->assertSame('ED01', $generated->service_code);
        $this->assertSame('up', $generated->side);
        $this->assertSame(1, $generated->round_no);
        $this->assertSame('25.50', $generated->schedule_km);
        $this->assertSame('08:00', substr($generated->departure_time, 0, 5));
        $this->assertNull($generated->driver_profile_id);
        $this->assertNull($generated->vehicle_id);
        $this->assertNull($generated->notes);
        $this->assertFalse($generated->is_initial_verified);
        $this->assertFalse($generated->is_driver_verified);
        $this->assertNull($generated->actual_start_time);
        $this->assertNotSame($this->entry->code, $generated->code);
        $stop = $generated->scheduleStopTimes()->sole();
        $this->assertSame('Depot', $stop->location_name);
        $this->assertSame(12, (int) $stop->location_id);
        $this->assertSame('08:00', substr($stop->scheduled_time, 0, 5));
        $roster = $generated->rosters()->sole();
        $this->assertSame('unassigned', $roster->status);
        $this->assertFalse($roster->inherit_trip_assignment);
        $this->assertSame('2026-11-15', $roster->duty_date->toDateString());
        $this->assertNull($roster->driver_profile_id);
        $this->assertNull($roster->vehicle_id);
        $this->assertNull($roster->trip_assignment_id);
        $this->assertSame($assignment->id, (int) $this->roster->fresh()->trip_assignment_id);
        $this->assertTrue($this->roster->fresh()->inherit_trip_assignment);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame(1, $service->summary['generated']);
        $this->assertSame(1, $service->summary['rosters']);
    }

    public function test_new_unassigned_roster_can_later_be_assigned_through_existing_service(): void
    {
        $this->supervisor->givePermissionTo(Permission::create(['name' => 'rosters.create', 'guard_name' => 'web', 'group_name' => 'Roster']));
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $rosterId = $generated->rosters()->sole()->id;
        app(TripRosterAssignment::class)->assign($this->supervisor, $this->trip->fresh(), $generated->id, $this->freeDriver->id);
        $this->assertSame($rosterId, $generated->rosters()->sole()->id);
        $this->assertSame('assigned', $generated->rosters()->sole()->status);
        $this->assertSame($this->freeDriver->id, (int) $generated->fresh()->driver_profile_id);
        $this->assertNull($generated->fresh()->vehicle_id);
    }

    public function test_generated_unassigned_roster_becomes_assigned_when_driver_is_selected(): void
    {
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $roster = $generated->rosters()->sole();
        $this->assertSame('unassigned', $roster->status);
        app(TripDriverAssignment::class)->change($this->supervisor, $generated->id, $this->freeDriver->id);
        $this->assertSame('assigned', $roster->fresh()->status);
        $this->assertSame($this->freeDriver->id, (int) $generated->fresh()->driver_profile_id);
        $this->assertNull($roster->fresh()->vehicle_id);
    }

    public function test_generated_unassigned_roster_becomes_assigned_when_vehicle_is_selected(): void
    {
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $roster = $generated->rosters()->sole();
        $vehicle = Vehicle::create(['depot_id' => 1, 'status' => 'Active', 'vehicle_no' => 'BUS', 'vehicle_code' => 'V']);
        $this->assertSame('unassigned', $roster->status);
        app(TripVehicleAssignment::class)->change($this->supervisor, $generated->id, $vehicle->id);
        $this->assertSame('assigned', $roster->fresh()->status);
        $this->assertSame($vehicle->id, (int) $generated->fresh()->vehicle_id);
        $this->assertNull($roster->fresh()->driver_profile_id);
    }

    public function test_missing_roster_for_existing_duty_inherits_recurring_assignment(): void
    {
        $vehicle = Vehicle::create(['depot_id' => 1, 'status' => 'Active', 'vehicle_no' => 'BUS', 'vehicle_code' => 'V']);
        $assignment = TripAssignment::create(['trip_id' => $this->trip->id, 'from_date' => '2026-11-14',
            'to_date' => '2026-11-14', 'driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => $vehicle->id]);
        $this->roster->tripSheetEntries()->detach();
        $this->roster->delete();
        $this->entry->update(['driver_profile_id' => null]);
        app(TripDateRescheduler::class)->update($this->trip, []);
        $roster = $this->entry->rosters()->sole();
        $this->assertSame($assignment->id, (int) $roster->trip_assignment_id);
        $this->assertSame($this->freeDriver->id, (int) $roster->driver_profile_id);
        $this->assertSame($vehicle->id, (int) $roster->vehicle_id);
        $this->assertSame('assigned', $roster->status);
        $this->assertTrue($roster->inherit_trip_assignment);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame($vehicle->id, (int) $this->entry->fresh()->vehicle_id);
    }

    public function test_generated_unassigned_duty_suppresses_recurring_vehicle_and_completed_display_fallback(): void
    {
        $vehicle = Vehicle::create(['depot_id' => 1, 'status' => 'Active', 'vehicle_no' => 'BUS', 'vehicle_code' => 'V']);
        $assignment = TripAssignment::create(['trip_id' => $this->trip->id, 'from_date' => '2026-11-14',
            'to_date' => '2026-11-15', 'driver_profile_id' => $this->oldDriver->id, 'vehicle_id' => $vehicle->id]);
        $this->assertTrue(TripSheetEntry::forVehicleCode('V')->whereKey($this->entry->id)->exists());
        $this->assertSame($assignment->id, TripController::assignmentForCompletedEntry($this->entry->fresh())?->id);
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $this->assertNull($generated->rosters()->sole()->trip_assignment_id);
        $this->assertFalse($generated->rosters()->sole()->inherit_trip_assignment);
        $this->assertFalse(TripSheetEntry::forVehicleCode('V')->whereKey($generated->id)->exists());
        $this->assertNull(TripController::assignmentForCompletedEntry($generated));
        app(TripDriverAssignment::class)->change($this->supervisor, $generated->id, $this->freeDriver->id);
        $generated = $generated->fresh();
        $this->assertSame('assigned', $generated->rosters()->sole()->status);
        $this->assertFalse($generated->rosters()->sole()->inherit_trip_assignment);
        $this->assertNull($generated->vehicle_id);
        $this->assertNull($generated->rosters()->sole()->vehicle_id);
        $this->assertFalse(TripSheetEntry::forVehicleCode('V')->whereKey($generated->id)->exists());
        $this->assertNull(TripController::assignmentForCompletedEntry($generated));
    }

    public function test_roster_form_accepts_unassigned_duty_without_oem_driver_or_vehicle(): void
    {
        foreach (['states', 'depots'] as $table) {
            Schema::create($table, fn (Blueprint $schema) => $schema->id());
            DB::table($table)->insert(['id' => 1]);
        }
        $values = ['state_id' => 1, 'depot_id' => 1, 'duty_date' => '2026-11-14', 'shift_type' => 'morning',
            'shift_start_time' => '08:00', 'shift_end_time' => '16:00', 'trip_sheet_entry_ids' => [$this->entry->id],
            'status' => 'unassigned', 'oem_id' => null, 'driver_profile_id' => null, 'vehicle_id' => null];
        $validator = Validator::make($values, (new StoreRosterRequest)->rules());
        $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));
        $this->assertSame('unassigned', $validator->validated()['status']);
        $this->assertNull($validator->validated()['oem_id']);
        $this->assertNull($validator->validated()['driver_profile_id']);
        $this->assertNull($validator->validated()['vehicle_id']);
    }

    public function test_shrink_removes_only_unassigned_pending_dates_and_their_stops_and_rosters(): void
    {
        $service = app(TripDateRescheduler::class);
        $service->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $removedSheet = $generated->trip_sheet_id;
        $removedRoster = $generated->rosters()->sole()->id;
        $generated->scheduleStopTimes()->create(['sequence_no' => 1, 'location_name' => 'End',
            'event' => 'arrival', 'scheduled_time' => '16:00']);
        $service->update($this->trip->fresh(), ['to_date' => '2026-11-14']);
        $this->assertNull($generated->fresh());
        $this->assertNull(TripSheet::find($removedSheet));
        $this->assertNull(Roster::find($removedRoster));
        $this->assertSame(0, $generated->scheduleStopTimes()->count());
        $this->assertSame(1, $service->summary['removed']);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_shrink_rejects_assigned_or_protected_removed_dates_and_rolls_back(): void
    {
        $service = app(TripDateRescheduler::class);
        $service->update($this->trip, ['to_date' => '2026-11-15']);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        foreach ([['driver_profile_id' => $this->freeDriver->id], ['is_initial_verified' => true]] as $attributes) {
            $generated->update($attributes);
            try {
                $service->update($this->trip->fresh(), ['to_date' => '2026-11-14']);
                $this->fail('Shrink removed an assigned or protected duty.');
            } catch (ValidationException) {
                $this->assertSame('2026-11-15', $this->trip->fresh()->to_date->toDateString());
                $this->assertSame(2, TripSheetEntry::count());
                $this->assertSame(2, Roster::count());
            }
            $generated->update(['driver_profile_id' => null, 'is_initial_verified' => false]);
        }
    }

    public function test_same_date_save_fills_missing_dates_and_inherits_existing_entry_resources_once(): void
    {
        $this->trip->update(['to_date' => '2026-11-16']);
        $latestSheet = TripSheet::create(['trip_id' => $this->trip->id, 'date' => '2026-11-16', 'status' => 'pending']);
        TripSheetEntry::create(['trip_sheet_id' => $latestSheet->id, 'code' => 'LAST-0002', 'service_code' => 'ED02',
            'status' => 'pending', 'departure_time' => '17:00', 'arrival_time' => '18:00']);
        $this->roster->tripSheetEntries()->detach();
        $this->roster->delete();
        $service = app(TripDateRescheduler::class);
        $service->update($this->trip, []);
        $middle = TripSheetEntry::whereHas('sheet', fn ($q) => $q->whereDate('date', '2026-11-15'))->sole();
        $this->assertSame('ED02', $middle->service_code);
        $this->assertNull($middle->driver_profile_id);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->rosters()->sole()->driver_profile_id);
        $this->assertSame('assigned', $this->entry->rosters()->sole()->status);
        $this->assertSame(3, Roster::count());
        $ids = TripSheetEntry::orderBy('id')->pluck('id')->all();
        $service->update($this->trip->fresh(), []);
        $this->assertSame($ids, TripSheetEntry::orderBy('id')->pluck('id')->all());
        $this->assertSame(3, Roster::count());
    }

    public function test_changed_dates_without_existing_schedule_are_rejected(): void
    {
        $this->roster->tripSheetEntries()->detach();
        $this->roster->delete();
        $this->entry->delete();
        $this->trip->sheets()->delete();
        try {
            $this->move();
            $this->fail('Changed dates were accepted without a schedule.');
        } catch (ValidationException) {
            $this->assertSame('2026-11-14', $this->trip->fresh()->from_date->toDateString());
            $this->assertSame(0, TripSheet::count());
            $this->assertSame(0, Roster::count());
        }
    }

    public function test_incomplete_legacy_mismatch_is_rejected_without_creating_dates(): void
    {
        $this->trip->update(['from_date' => '2026-12-14', 'to_date' => '2026-12-16']);
        $sheet = TripSheet::create(['trip_id' => $this->trip->id, 'date' => '2026-11-16', 'status' => 'pending']);
        TripSheetEntry::create(['trip_sheet_id' => $sheet->id, 'status' => 'pending', 'code' => 'TRP2026#0056-0002',
            'service_code' => 'ED02', 'departure_time' => '08:00', 'arrival_time' => '16:00']);
        try {
            app(TripDateRescheduler::class)->update($this->trip, []);
            $this->fail('An incomplete historical range was inferred automatically.');
        } catch (ValidationException) {
            $this->assertSame(['2026-11-14', '2026-11-16'], $this->trip->sheets()->orderBy('date')->get()
                ->map(fn ($item) => $item->date->toDateString())->all());
            $this->assertSame(2, TripSheetEntry::count());
            $this->assertSame(1, Roster::count());
        }
    }

    public function test_extension_retains_completed_history_and_generates_blank_new_duties(): void
    {
        $this->entry->update(['status' => 'verification_completed', 'is_final_verified' => true,
            'actual_start_time' => '08:05', 'actual_reach_time' => '16:10']);
        $this->entry->sheet->update(['status' => 'verification_completed']);
        $this->roster->update(['status' => 'completed', 'attendance_status' => 'present']);
        app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
        $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->assertTrue($this->entry->fresh()->is_final_verified);
        $this->assertSame('completed', $this->roster->fresh()->status);
        $this->assertSame('present', $this->roster->fresh()->attendance_status);
        $generated = TripSheetEntry::whereKeyNot($this->entry->id)->sole();
        $this->assertSame('pending', $generated->status);
        $this->assertFalse($generated->is_final_verified);
        $this->assertNull($generated->actual_start_time);
        $this->assertNull($generated->actual_reach_time);
        $this->assertSame('unassigned', $generated->rosters()->sole()->status);
    }

    public function test_incomplete_source_schedule_rolls_back_extension(): void
    {
        $this->entry->update(['arrival_time' => null]);
        try {
            app(TripDateRescheduler::class)->update($this->trip, ['to_date' => '2026-11-15']);
            $this->fail('An incomplete schedule was copied.');
        } catch (ValidationException) {
            $this->assertSame('2026-11-14', $this->trip->fresh()->to_date->toDateString());
            $this->assertSame(1, TripSheet::count());
            $this->assertSame(1, TripSheetEntry::count());
            $this->assertSame(1, Roster::count());
        }
    }

    public function test_repair_preview_is_read_only_and_repeat_apply_is_rejected(): void
    {
        $this->trip->update(['from_date' => '2026-12-14', 'to_date' => '2026-12-14']);
        $service = app(TripDateRescheduler::class);
        $service->repair($this->trip, '2026-11-14', '2026-11-14', $this->supervisor->id);
        $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
        $service->repair($this->trip, '2026-11-14', '2026-11-14', $this->supervisor->id, true);
        $this->assertSame('2026-12-14', $this->entry->fresh()->sheet->date->toDateString());
        try {
            $service->repair($this->trip, '2026-11-14', '2026-11-14', $this->supervisor->id, true);
            $this->fail('Repair shifted the same trip twice.');
        } catch (ValidationException) {
            $this->assertSame('2026-12-14', $this->roster->fresh()->duty_date->toDateString());
        }
    }

    public function test_repair_command_previews_then_applies_only_with_administrator_actor(): void
    {
        $this->trip->update(['from_date' => '2026-12-14', 'to_date' => '2026-12-14']);
        $options = ['trip' => $this->trip->code, '--from' => '2026-11-14', '--to' => '2026-11-14'];
        $this->artisan('trips:repair-dates', $options)->assertSuccessful();
        $this->assertSame('2026-11-14', $this->entry->fresh()->sheet->date->toDateString());
        $this->artisan('trips:repair-dates', $options + ['--apply' => true])->assertFailed();
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->supervisor->assignRole('Super Admin');
        $this->artisan('trips:repair-dates', $options + ['--apply' => true, '--actor' => $this->supervisor->id])->assertSuccessful();
        $this->assertSame('2026-12-14', $this->entry->fresh()->sheet->date->toDateString());
    }

}
