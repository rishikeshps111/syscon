<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\{Leave, Roster, Trip, TripSheet, TripSheetEntry, User, Vehicle};
use App\Services\{RosterReassignmentNotifier, TripRosterAssignment};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache, Schema};
use PhpOffice\PhpSpreadsheet\{Spreadsheet, IOFactory};
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\Support\TripAssignmentTestCase;

class MonthlyRosterImportTest extends TripAssignmentTestCase
{
    private Trip $trip;
    private Vehicle $vehicle;
    private array $files = [];
    private array $cacheKeys = [];

    protected function createSchema(): void
    {
        parent::createSchema();
        Schema::table('users', fn (Blueprint $table) => $table->string('ref_code')->nullable());
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('vehicle_classification_id')->nullable();
            $table->string('code')->nullable();
        });
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->string('service_code')->nullable();
            $table->string('departure_time')->nullable();
            $table->string('arrival_time')->nullable();
        });
        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('oem_id')->nullable();
            $table->unsignedBigInteger('vehicle_classification_id')->nullable();
        });
        Schema::table('rosters', function (Blueprint $table) {
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('oem_id')->nullable();
            $table->unsignedBigInteger('trip_assignment_id')->nullable();
            $table->string('reporting_time')->nullable();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $this->supervisor->givePermissionTo(Permission::create(['name' => 'rosters.create', 'guard_name' => 'web', 'group_name' => 'Roster']));
        $this->actingAs($this->supervisor, 'web');
        $this->trip = $this->entry->sheet->trip;
        $this->trip->update(['state_id' => 1, 'vehicle_classification_id' => 1]);
        $this->vehicle = Vehicle::create(['vehicle_code' => 'V1', 'vehicle_no' => 'BUS01', 'depot_id' => 1,
            'vehicle_classification_id' => 1, 'oem_id' => 1, 'status' => 'Active']);
        $this->entry->update(['code' => 'TRP2026#0051-0476', 'service_code' => '001', 'departure_time' => '08:00', 'arrival_time' => '16:00', 'vehicle_id' => $this->vehicle->id]);
        $this->roster->update(['vehicle_id' => $this->vehicle->id]);
        $this->oldDriver->user->update(['ref_code' => '0001', 'name' => 'Driver One']);
        $this->freeDriver->user->update(['ref_code' => '0002', 'name' => 'Driver Two']);
    }

    protected function tearDown(): void
    {
        Cache::store('file')->forget('monthly-roster-active.'.$this->supervisor->id.'.'.$this->trip->id);
        foreach ($this->files as $file) if (is_file($file)) unlink($file);
        foreach ($this->cacheKeys as $key) Cache::store('file')->forget($key);
        parent::tearDown();
    }

    private function endpoint(string $suffix = 'monthly-import'): string
    {
        return '/trips/'.$this->trip->id.'/roster/'.$suffix;
    }

    private function workbook(array $rows, string $format = 'xlsx'): UploadedFile
    {
        $file = tempnam(sys_get_temp_dir(), 'monthly-roster-test-');
        $this->files[] = $file;
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(['Trip Code', 'Date', 'Service Code', 'Driver Name', 'Current Vehicle'], null, 'A1');
        foreach ($rows as $index => $row) {
            // Short fixtures describe date/service; resolve their existing test entry code.
            // Five-column fixtures below specify the exact uploaded code directly.
            if (count($row) === 4) {
                try {
                    $date = is_numeric($row[0]) ? Date::excelToDateTimeObject($row[0])->format('Y-m-d')
                        : \Carbon\Carbon::createFromFormat('!d-m-Y', $row[0])->toDateString();
                    $code = TripSheetEntry::where('service_code', $row[1])->whereHas('sheet', fn ($q) => $q->where('trip_id', $this->trip->id)->whereDate('date', $date))->value('code');
                } catch (\Throwable) { $code = null; }
                array_unshift($row, $code ?? 'UNKNOWN-CODE-'.$index);
            }
            foreach ($row as $column => $value) $sheet->setCellValueExplicit([$column + 1, $index + 2], $value,
                is_numeric($value) && !is_string($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }
        $writer = $format === 'xls' ? new \PhpOffice\PhpSpreadsheet\Writer\Xls($book) : new Xlsx($book);
        $writer->save($file);
        $book->disconnectWorksheets();
        return new UploadedFile($file, 'monthly.'.$format, null, null, true);
    }

    private function prepare(array $rows, string $format = 'xlsx'): array
    {
        $result = $this->postJson($this->endpoint(), ['step' => 'prepare', 'roster_file' => $this->workbook($rows, $format)])
            ->assertOk()->json();
        $this->cacheKeys[] = 'monthly-roster.'.$result['token'];
        return $result;
    }

    private function preview(array $state): array
    {
        while ($state['phase'] === 'preview') {
            $result = $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $state['token'], 'offset' => $state['previewed']])
                ->assertOk()->json();
            $state = $result + ['token' => $state['token']];
        }
        return $state;
    }

    private function rows(array $state): array
    {
        $rows = [];
        for ($page = 1; ; $page++) {
            $data = $this->postJson($this->endpoint(), ['step' => 'rows', 'token' => $state['token'], 'page' => $page])->assertOk()->json();
            $rows = array_merge($rows, $data['rows']);
            if ($page >= $data['pages']) return $rows;
        }
    }

    private function process(array $state): array
    {
        $state = $this->preview($state);
        if ($state['phase'] === 'ready' && $state['summary']['eligible'] > 0) {
            $state = $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $state['token'], 'confirmed' => true])
                ->assertOk()->json() + ['token' => $state['token']];
        }
        if ($state['phase'] === 'saving') {
            $state = $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $state['token'], 'offset' => $state['processed']])
                ->assertOk()->json() + ['token' => $state['token']];
        }
        $state['_flashed_message'] = session('success');
        $state['_flashed_summary'] = session('roster_update_summary');
        $state['reasons'] = array_values(array_map(fn ($row) => 'Row '.$row['line'].': '.$row['reason'],
            array_filter($this->rows($state), fn ($row) => $row['reason'] !== '')));
        return $state;
    }

    private function skipped(array $state): int
    {
        return $state['phase'] === 'ready' ? $state['summary']['issue'] + $state['summary']['blank'] : $state['skipped'];
    }

    private function entryOn(string $date, array $attributes = []): TripSheetEntry
    {
        $sheet = TripSheet::create(['trip_id' => $this->trip->id, 'date' => $date, 'status' => 'pending']);
        return TripSheetEntry::create($attributes + ['code' => 'TEST-'.(TripSheetEntry::max('id') + 1), 'trip_sheet_id' => $sheet->id, 'service_code' => '001', 'status' => 'pending',
            'vehicle_id' => $this->vehicle->id, 'departure_time' => '08:00', 'arrival_time' => '16:00']);
    }

    public function test_template_includes_all_dates_and_preserves_assigned_text_identifiers(): void
    {
        $this->entryOn('2026-10-01');
        $response = $this->get($this->endpoint('monthly-template').'?month=2026-09')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'monthly-template-test-');
        $this->files[] = $file;
        file_put_contents($file, $response->streamedContent());
        $book = IOFactory::load($file);
        $sheet = $book->getActiveSheet();
        $this->assertSame(3, $sheet->getHighestDataRow());
        $this->assertSame(['Trip Code', 'Date', 'Service Code', 'Driver Name', 'Current Vehicle'], $sheet->rangeToArray('A1:E1')[0]);
        $this->assertSame(['TRP2026#0051-0476', '08-09-2026', '001', 'Driver One', 'BUS01'], $sheet->rangeToArray('A2:E2')[0]);
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
        $this->assertSame('01-10-2026', $sheet->getCell('B3')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_month_import_batches_25_rows_then_final_batch_and_reimport_is_unchanged(): void
    {
        $rows = [];
        for ($day = 1; $day <= 30; $day++) {
            if ($day !== 8) $this->entryOn(sprintf('2026-09-%02d', $day));
            $rows[] = [sprintf('%02d-09-2026', $day), '001', 'Driver Two', 'IGNORED'];
        }
        $prepared = $this->prepare($rows);
        $this->assertSame(0, $prepared['processed']);
        $first = $this->process($prepared);
        $this->assertSame(25, $first['processed']);
        $this->assertSame('saving', $first['phase']);
        $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $prepared['token'], 'offset' => 0])->assertStatus(409);
        $last = $this->process($first);
        $this->assertSame(['done', 30, 29, 1, 0, 0], [$last['phase'], $last['processed'], $last['created'], $last['updated'], $last['unchanged'], $last['skipped']]);
        $this->assertSame(30, Roster::count());
        $this->assertSame(30, TripSheetEntry::where('driver_profile_id', $this->freeDriver->id)->where('vehicle_id', $this->vehicle->id)->count());
        $repeat = $this->process($this->process($this->prepare($rows)));
        $this->assertSame(30, $repeat['summary']['unchanged']);
        $this->assertSame(0, $repeat['updated']);
        $this->assertSame(30, Roster::count());
    }

    public function test_skips_invalid_rows_but_imports_valid_excel_date_and_vehicle_is_optional(): void
    {
        $this->entryOn('2026-09-09');
        $serial = Date::PHPToExcel(new \DateTime('2026-09-08'));
        $state = $this->process($this->prepare([
            [$serial, '001', '  DRIVER TWO  ', ''], ['09-09-2026', '001', '', ''],
            ['10-09-2026', '001', 'Driver Two', ''], ['01-10-2026', '001', 'Driver Two', ''],
            ['31-09-2026', '001', 'Driver Two', ''], ['09-09-2026', 'OTHER', 'Driver Two', ''],
        ]));
        $this->assertSame(1, $state['updated']);
        $this->assertSame(5, $this->skipped($state));
        $this->assertSame($this->vehicle->id, (int) $this->entry->fresh()->vehicle_id);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertStringContainsString('Row 3: Driver Name is blank', implode(' ', $state['reasons']));
        $this->assertStringContainsString('No trip-sheet entry', implode(' ', $state['reasons']));
        $this->assertStringContainsString('Date must be', implode(' ', $state['reasons']));
        $this->assertStringContainsString('No trip-sheet entry', implode(' ', $state['reasons']));
    }

    public function test_duplicate_keys_across_batches_are_all_skipped_and_names_must_be_unique(): void
    {
        $rows = array_fill(0, 26, ['08-09-2026', '001', 'Driver Two', '']);
        $state = $this->process($this->process($this->prepare($rows)));
        $this->assertSame(26, $this->skipped($state));
        $this->assertSame(0, $state['updated']);
        $this->assertStringContainsString('Row 27: Duplicate', implode(' ', $state['reasons']));
        $unknown = $this->process($this->prepare([['08-09-2026', '001', 'UNKNOWN', '']]));
        $this->assertStringContainsString('Driver Name was not found', $unknown['reasons'][0]);
        $duplicate = $this->driver();
        $duplicate->user->update(['name' => 'Driver Two']);
        $ambiguous = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertStringContainsString('matches multiple drivers', $ambiguous['reasons'][0]);
    }

    public function test_entry_must_match_trip_uniquely_and_vehicle_is_optional(): void
    {
        $otherTrip = $this->trip->replicate();
        $otherTrip->save();
        $otherSheet = TripSheet::create(['trip_id' => $otherTrip->id, 'date' => '2026-09-09', 'status' => 'pending']);
        TripSheetEntry::create(['trip_sheet_id' => $otherSheet->id, 'service_code' => 'OTHER', 'status' => 'pending']);
        $other = $this->process($this->prepare([['09-09-2026', 'OTHER', 'Driver Two', '']]));
        $this->assertStringContainsString('No trip-sheet entry', $other['reasons'][0]);
        $replica = $this->entry->replicate();
        $replica->save();
        $ambiguous = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertStringContainsString('multiple trip entries', $ambiguous['reasons'][0]);
        $replica->delete();
        $this->entry->update(['vehicle_id' => null]);
        $this->roster->update(['vehicle_id' => null]);
        $missing = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'BUS01']]));
        $this->assertSame(1, $missing['updated']);
        $this->assertNull($this->entry->fresh()->vehicle_id);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_shared_roster_change_isolates_entry_and_resets_only_driver_verification(): void
    {
        $other = $this->entry->replicate();
        $other->fill(['code' => 'OTHER-ENTRY', 'service_code' => 'OTHER'])->save();
        $this->roster->tripSheetEntries()->attach($other);
        $this->entry->update(['is_vehicle_verified' => true]);
        $this->roster->update(['attendance_status' => 'present']);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $state['updated']);
        $this->assertSame($this->oldDriver->id, (int) $this->roster->fresh()->driver_profile_id);
        $this->assertSame($this->oldDriver->id, (int) $other->fresh()->driver_profile_id);
        $this->assertSame([$other->id], $this->roster->tripSheetEntries()->pluck('trip_sheet_entries.id')->all());
        $this->assertFalse($this->entry->fresh()->is_driver_verified);
        $this->assertTrue($this->entry->fresh()->is_vehicle_verified);
        $replacement = $this->entry->fresh()->rosters->sole();
        $this->assertSame($this->freeDriver->id, (int) $replacement->driver_profile_id);
        $this->assertNull($replacement->attendance_status);
    }

    public function test_eligibility_is_rechecked_after_upload_including_leave_and_overnight_expiry(): void
    {
        $state = $this->prepare([['08-09-2026', '001', 'Driver Two', '']]);
        $this->freeDriver->user->update(['is_active' => false]);
        $this->assertSame(1, $this->skipped($this->process($state)));
        $this->freeDriver->user->update(['is_active' => true]);
        $leave = Leave::create(['user_id' => $this->freeDriver->user_id, 'leave_for' => 'driver', 'status' => 'Approved', 'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'shift' => 'Morning']);
        $this->assertSame(1, $this->skipped($this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))));
        $leave->update(['status' => 'Cancelled']);
        $this->entry->update(['departure_time' => '22:00', 'arrival_time' => '06:00']);
        $this->freeDriver->update(['expiry_date' => '2026-09-08']);
        $expired = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $this->skipped($expired));
        $this->freeDriver->update(['expiry_date' => '2026-09-09']);
        $this->assertSame(1, $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))['updated']);
    }

    public function test_conflicts_within_file_are_skipped_and_touching_shifts_are_allowed(): void
    {
        $second = $this->entryOn('2026-09-08', ['service_code' => '002']);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', ''], ['08-09-2026', '002', 'Driver Two', '']]));
        $this->assertSame(0, $state['updated']);
        $this->assertSame(2, $this->skipped($state));
        $this->assertNull($second->fresh()->driver_profile_id);
        $second->update(['departure_time' => '16:00', 'arrival_time' => '18:00']);
        $touching = $this->process($this->prepare([['08-09-2026', '002', 'Driver Two', '']]));
        $this->assertSame(1, $touching['created']);
    }

    public function test_locked_states_and_wrong_depot_vehicle_are_skipped(): void
    {
        foreach ([['actual_start_time' => '08:00'], ['actual_reach_time' => '16:00'], ['is_initial_verified' => true], ['is_final_verified' => true], ['status' => 'verification_completed'], ['status' => 'cancelled']] as $attributes) {
            // Cancelled entries are deliberately immutable, so use an independent entry per case.
            $entry = $this->entryOn('2026-09-09', ['service_code' => 'LOCK'.count($this->cacheKeys)] + $attributes);
            $state = $this->process($this->prepare([['09-09-2026', $entry->service_code, 'Driver Two', '']]));
            $this->assertSame(1, $this->skipped($state));
            $this->assertNull($entry->fresh()->driver_profile_id);
        }
        foreach (['in_progress', 'completed', 'cancelled'] as $status) {
            $this->roster->update(['status' => $status]);
            $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
            $this->assertSame(1, $this->skipped($state));
        }
        $this->roster->update(['status' => 'assigned']);
        foreach ([['depot_id' => 2], ['status' => 'Inactive'], ['vehicle_classification_id' => 2]] as $change) {
            $this->vehicle->update($change);
            $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
            $this->assertSame(1, $this->skipped($state));
            $this->vehicle->update(['depot_id' => 1, 'status' => 'Active', 'vehicle_classification_id' => 1]);
        }
    }

    public function test_permissions_depot_and_import_ownership_are_enforced(): void
    {
        $state = $this->prepare([['08-09-2026', '001', 'Driver Two', '']]);
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->getJson($this->endpoint('monthly-template').'?month=2026-09')->assertForbidden();
        $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $state['token'], 'offset' => 0])->assertForbidden();
        $other->givePermissionTo('rosters.create');
        $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $state['token'], 'offset' => 0])->assertForbidden();
        $this->actingAs($this->supervisor);
        $this->supervisor->supervisorProfile->update(['depot_id' => 2]);
        $this->getJson($this->endpoint('monthly-template').'?month=2026-09')->assertForbidden();
        $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $state['token'], 'offset' => 0])->assertForbidden();
    }

    public function test_xls_upload_and_manual_assignment_use_same_service_and_notifications(): void
    {
        $this->mock(RosterReassignmentNotifier::class)->shouldReceive('sendTripDriverChange')->once()->andReturn(['sent' => 1, 'failed' => 0]);
        $response = $this->post('/trips/'.$this->trip->id.'/update-roster', ['selected_ids' => [$this->entry->id],
            'assignments' => [$this->entry->id => ['driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => $this->vehicle->id]]]);
        $response->assertRedirect();
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertFalse($this->entry->fresh()->is_driver_verified);
        $unchanged = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']], 'xls'));
        $this->assertSame(1, $unchanged['summary']['unchanged']);
    }

    public function test_existing_roster_vehicle_is_used_when_entry_vehicle_is_blank(): void
    {
        $this->entry->update(['vehicle_id' => null]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $state['updated']);
        $this->assertSame($this->vehicle->id, (int) $this->entry->fresh()->vehicle_id);
        $this->assertSame($this->vehicle->id, (int) $this->roster->fresh()->vehicle_id);
    }

    public function test_valid_vehicle_in_excel_is_imported_and_assigned(): void
    {
        $otherVehicle = Vehicle::create([
            'vehicle_code' => 'V2', 'vehicle_no' => 'BUS02', 'depot_id' => 1,
            'vehicle_classification_id' => 1, 'oem_id' => 1, 'status' => 'Active',
        ]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'BUS02']]));
        $this->assertSame(1, $state['updated']);
        $this->assertSame($otherVehicle->id, (int) $this->entry->fresh()->vehicle_id);
        $this->assertSame($otherVehicle->id, (int) $this->roster->fresh()->vehicle_id);
    }

    public function test_invalid_or_unknown_vehicle_reports_not_found_reason(): void
    {
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'UNKNOWN-BUS']]));
        $this->assertSame(1, $this->skipped($state));
        $this->assertSame(0, $state['updated']);
        $this->assertStringContainsString("Vehicle 'UNKNOWN-BUS' was not found.", $state['reasons'][0]);
    }

    public function test_inactive_vehicle_reports_not_active_reason(): void
    {
        Vehicle::create([
            'vehicle_code' => 'V_INACT', 'vehicle_no' => 'BUS-INACT', 'depot_id' => 1,
            'vehicle_classification_id' => 1, 'oem_id' => 1, 'status' => 'Inactive',
        ]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'BUS-INACT']]));
        $this->assertSame(1, $this->skipped($state));
        $this->assertStringContainsString("Vehicle 'BUS-INACT' is not active", $state['reasons'][0]);
    }

    public function test_wrong_depot_vehicle_reports_depot_reason(): void
    {
        Vehicle::create([
            'vehicle_code' => 'V_DEP2', 'vehicle_no' => 'BUS-DEP2', 'depot_id' => 2,
            'vehicle_classification_id' => 1, 'oem_id' => 1, 'status' => 'Active',
        ]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'BUS-DEP2']]));
        $this->assertSame(1, $this->skipped($state));
        $this->assertStringContainsString("Vehicle 'BUS-DEP2' must belong to this trip depot.", $state['reasons'][0]);
    }

    public function test_wrong_classification_vehicle_reports_classification_reason(): void
    {
        Vehicle::create([
            'vehicle_code' => 'V_CLS2', 'vehicle_no' => 'BUS-CLS2', 'depot_id' => 1,
            'vehicle_classification_id' => 2, 'oem_id' => 1, 'status' => 'Active',
        ]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', 'BUS-CLS2']]));
        $this->assertSame(1, $this->skipped($state));
        $this->assertStringContainsString("Vehicle 'BUS-CLS2' does not match the trip vehicle classification.", $state['reasons'][0]);
    }

    public function test_vehicle_conflict_is_independent_of_driver_availability(): void
    {
        $busyDriver = $this->driver();
        $busy = $this->rosterFor($busyDriver, ['vehicle_id' => $this->vehicle->id]);
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $this->skipped($state));
        $this->assertStringContainsString('vehicle has an overlapping duty', $state['reasons'][0]);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $busy->update(['status' => 'completed']);
        $this->assertSame(1, $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))['updated']);
    }

    public function test_wrong_depot_driver_and_auto_marked_leave_are_skipped(): void
    {
        $this->freeDriver->update(['depot_id' => 2]);
        $wrongDepot = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $this->skipped($wrongDepot));
        $this->assertStringContainsString('driver must belong to this trip depot', $wrongDepot['reasons'][0]);
        $this->freeDriver->update(['depot_id' => 1]);
        Leave::create(['user_id' => $this->freeDriver->user_id, 'leave_for' => 'driver', 'status' => 'Auto Marked', 'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'shift' => 'Morning']);
        $leave = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $this->skipped($leave));
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_sheet_cancellation_trip_inactivity_and_missing_times_block_changes(): void
    {
        $this->entry->sheet->update(['status' => 'cancelled']);
        $this->assertSame(1, $this->skipped($this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))));
        $this->entry->sheet->update(['status' => 'verification_completed']);
        $this->assertSame(1, $this->skipped($this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))));
        $this->entry->sheet->update(['status' => 'pending']);
        $this->trip->update(['status' => 'Inactive']);
        $this->assertSame(1, $this->skipped($this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]))));
        $this->trip->update(['status' => 'Active']);
        $this->entry->update(['arrival_time' => null]);
        $missing = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $this->skipped($missing));
        $this->assertStringContainsString('arrival time', $missing['reasons'][0]);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_invalid_workbook_empty_upload_and_expired_or_wrong_trip_token_are_rejected(): void
    {
        $this->postJson($this->endpoint(), ['step' => 'prepare'])->assertUnprocessable()->assertJsonValidationErrors('roster_file');
        $this->postJson($this->endpoint(), ['step' => 'prepare', 'roster_file' => $this->workbook([])])
            ->assertUnprocessable()->assertJsonValidationErrors('roster_file');
        $file = $this->workbook([['08-09-2026', '001', 'Driver Two', '']]);
        $book = IOFactory::load($file->getRealPath());
        $book->getActiveSheet()->setCellValue('A1', 'Wrong Header');
        (new Xlsx($book))->save($file->getRealPath());
        $book->disconnectWorksheets();
        $this->postJson($this->endpoint(), ['step' => 'prepare', 'roster_file' => $file])
            ->assertUnprocessable()->assertJsonValidationErrors('roster_file');
        $state = $this->prepare([['08-09-2026', '001', 'Driver Two', '']]);
        $other = $this->trip->replicate();
        $other->save();
        $this->postJson('/trips/'.$other->id.'/roster/monthly-import', ['step' => 'preview', 'token' => $state['token'], 'offset' => 0])->assertForbidden();
        Cache::store('file')->forget('monthly-roster.'.$state['token']);
        $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $state['token'], 'offset' => 0])->assertStatus(410);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_notification_failure_keeps_committed_import_and_unchanged_does_not_notify(): void
    {
        $this->mock(RosterReassignmentNotifier::class)->shouldReceive('sendTripDriverChange')->once()->andThrow(new \RuntimeException('Push unavailable'));
        $state = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $state['updated']);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $repeat = $this->process($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $repeat['summary']['unchanged']);
    }

    public function test_preview_is_read_only_and_reports_every_status_before_confirmation(): void
    {
        $this->mock(RosterReassignmentNotifier::class)->shouldNotReceive('sendTripDriverChange', 'sendTripVehicleChange');
        $new = $this->entryOn('2026-09-09');
        $unchanged = $this->entryOn('2026-09-10', ['driver_profile_id' => $this->oldDriver->id]);
        $this->rosterFor($this->oldDriver, ['duty_date' => '2026-09-10', 'vehicle_id' => $this->vehicle->id])->tripSheetEntries()->attach($unchanged);
        $blank = $this->entryOn('2026-09-11');
        $this->entryOn('2026-09-12');
        $rosters = Roster::get()->toArray();
        $entries = TripSheetEntry::get()->toArray();
        $prepared = $this->prepare([
            ['08-09-2026', '001', 'Driver Two', ''], ['09-09-2026', '001', 'Driver Two', ''],
            ['10-09-2026', '001', 'Driver One', ''], ['11-09-2026', '001', '', ''], ['12-09-2026', '001', 'UNKNOWN', ''],
        ]);
        $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $prepared['token'], 'offset' => 0])->assertStatus(409);
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $prepared['token'], 'confirmed' => true])->assertStatus(409);
        \Illuminate\Support\Facades\DB::enableQueryLog();
        \Illuminate\Support\Facades\DB::flushQueryLog();
        $ready = $this->preview($prepared);
        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();
        foreach ($queries as $query) $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace)\b/i', $query['query']);
        $this->assertSame(['ready_update', 'ready_create', 'unchanged', 'blank', 'issue'], array_column($this->rows($ready), 'status'));
        $this->assertSame(2, $ready['summary']['eligible']);
        $this->assertSame(0, $ready['processed']);
        $this->assertSame($rosters, Roster::get()->toArray());
        $this->assertSame($entries, TripSheetEntry::get()->toArray());
        $this->assertNull($new->fresh()->driver_profile_id);
        $this->assertNull($blank->fresh()->driver_profile_id);
        $this->assertTrue($this->entry->fresh()->is_driver_verified);
        $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $ready['token'], 'offset' => 0])->assertStatus(409);
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $ready['token']])->assertUnprocessable();
        $row = $this->rows($ready)[0];
        $this->assertStringContainsString('Driver One', $row['current_driver']);
        $this->assertStringContainsString('Driver Two', $row['proposed_driver']);
        $this->assertSame('BUS01', $row['vehicle']);
        $this->assertArrayNotHasKey('_snapshot', $row);
        $this->assertArrayNotHasKey('_driver_id', $row);
    }

    public function test_confirmation_saves_only_ready_rows_and_skips_changed_assignments(): void
    {
        $new = $this->entryOn('2026-09-09');
        $this->entryOn('2026-09-10');
        $ready = $this->preview($this->prepare([
            ['08-09-2026', '001', 'Driver Two', ''], ['09-09-2026', '001', 'Driver Two', ''], ['10-09-2026', '001', 'UNKNOWN', ''],
        ]));
        $other = $this->driver();
        $this->entry->update(['driver_profile_id' => $other->id]);
        $this->roster->update(['driver_profile_id' => $other->id]);
        $saved = $this->process($ready);
        $this->assertSame('done', $saved['phase']);
        $this->assertSame(1, $saved['created']);
        $this->assertSame(0, $saved['updated']);
        $this->assertSame(2, $saved['skipped']);
        $this->assertSame($other->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame($this->freeDriver->id, (int) $new->fresh()->driver_profile_id);
        $rows = $this->rows($saved);
        $this->assertSame(['skipped', 'created', 'skipped'], array_column($rows, 'outcome'));
        $this->assertStringContainsString('changed since preview', $rows[0]['reason']);
        $this->assertSame('Roster import completed: 1 created, 0 updated, 0 unchanged, 2 skipped.', $saved['_flashed_message']);
        $this->assertSame(1, $saved['_flashed_summary']['created']);
        $this->assertCount(2, $saved['_flashed_summary']['reasons']);
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $saved['token'], 'confirmed' => true])->assertStatus(409);
    }

    public function test_uploaded_overlap_flags_both_rows_across_preview_chunks_and_pages(): void
    {
        $rows = [['08-09-2026', '001', 'Driver Two', '']];
        for ($day = 9; $day <= 32; $day++) {
            $date = \Carbon\Carbon::parse('2026-09-01')->addDays($day - 1);
            $this->entryOn($date->toDateString());
            $rows[] = [$date->format('d-m-Y'), '001', 'Driver Two', ''];
        }
        $last = $this->entryOn('2026-09-08', ['service_code' => 'LAST']);
        $rows[] = ['08-09-2026', 'LAST', 'Driver Two', ''];
        $prepared = $this->prepare($rows);
        $first = $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $prepared['token'], 'offset' => 0])->assertOk()->json();
        $this->assertSame(25, $first['previewed']);
        $this->assertSame('preview', $first['phase']);
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $prepared['token'], 'confirmed' => true])->assertStatus(409);
        $ready = $this->preview($first + ['token' => $prepared['token']]);
        $this->assertSame(2, $ready['summary']['issue']);
        $this->assertSame(24, $ready['summary']['eligible']);
        $pageOne = $this->postJson($this->endpoint(), ['step' => 'rows', 'token' => $ready['token'], 'page' => 1])->assertOk()->json();
        $pageTwo = $this->postJson($this->endpoint(), ['step' => 'rows', 'token' => $ready['token'], 'page' => 2])->assertOk()->json();
        $this->assertCount(25, $pageOne['rows']);
        $this->assertCount(1, $pageTwo['rows']);
        $this->assertSame('issue', $pageOne['rows'][0]['status']);
        $this->assertStringContainsString('row 27', $pageOne['rows'][0]['reason']);
        $this->assertStringContainsString('service LAST on 08-09-2026', $pageOne['rows'][0]['reason']);
        $this->assertStringContainsString('row 2', $pageTwo['rows'][0]['reason']);
        $this->assertSame($last->code, $pageOne['rows'][0]['conflicts'][0]['trip_code']);
        $this->assertSame('upload', $pageOne['rows'][0]['conflicts'][0]['source']);
        $saved = $this->process($this->process($ready));
        $this->assertSame(24, $saved['created']);
        $this->assertSame(2, $saved['skipped']);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertNull($last->fresh()->driver_profile_id);
    }

    public function test_discard_invalidates_preview_and_new_upload_requires_its_own_confirmation(): void
    {
        $ready = $this->preview($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->postJson($this->endpoint(), ['step' => 'discard', 'token' => $ready['token']])->assertOk();
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $ready['token'], 'confirmed' => true])->assertStatus(410);
        $new = $this->prepare([['08-09-2026', '001', 'Driver Two', '']]);
        $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $new['token'], 'offset' => 0])->assertStatus(409);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame(1, Roster::count());
        $noEligible = $this->preview($this->prepare([['08-09-2026', '001', '', '']]));
        $this->postJson($this->endpoint(), ['step' => 'preview', 'token' => $new['token'], 'offset' => 0])->assertStatus(410);
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $noEligible['token'], 'confirmed' => true])->assertStatus(409);
    }

    public function test_new_conflict_after_preview_is_rechecked_and_identifies_existing_duty(): void
    {
        $ready = $this->preview($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $busy = $this->entryOn('2026-09-08', ['service_code' => 'BUSY']);
        $this->rosterFor($this->freeDriver)->tripSheetEntries()->attach($busy);
        $saved = $this->process($ready);
        $this->assertSame(1, $saved['skipped']);
        $this->assertSame(0, $saved['updated']);
        $this->assertStringContainsString('service BUSY (Trip Code '.$busy->code.') on 08-09-2026', $saved['reasons'][0]);
        $this->assertSame($busy->code, $this->rows($saved)[0]['conflicts'][0]['trip_code']);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_driver_names_match_ignoring_case_and_spaces_without_code_fallback(): void
    {
        $ready = $this->preview($this->prepare([['08-09-2026', '001', '  dRiVeR tWo  ', '']]));
        $row = $this->rows($ready)[0];
        $this->assertSame('ready_update', $row['status']);
        $this->assertSame('Driver One', $row['current_driver']);
        $this->assertSame('Driver Two', $row['proposed_driver']);
        $code = $this->preview($this->prepare([['08-09-2026', '001', '0002', '']]));
        $this->assertSame('Driver Name was not found.', $this->rows($code)[0]['reason']);
        $duplicate = $this->driver();
        $duplicate->user->update(['name' => '  DRIVER TWO  ']);
        $ambiguous = $this->preview($this->prepare([['08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame('issue', $this->rows($ambiguous)[0]['status']);
        $this->assertSame('Driver Name matches multiple drivers.', $this->rows($ambiguous)[0]['reason']);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_same_date_and_service_with_distinct_trip_codes_match_and_update_correct_entries(): void
    {
        $this->entry->update(['service_code' => 'ED01']);
        $second = $this->entryOn('2026-09-08', ['code' => 'TRP2026#0051-0477', 'service_code' => 'ED01', 'departure_time' => '16:00', 'arrival_time' => '18:00']);
        $ready = $this->preview($this->prepare([
            ['TRP2026#0051-0476', '08-09-2026', 'ED01', 'Driver Two', ''],
            ['TRP2026#0051-0477', '08-09-2026', 'ED01', 'Driver One', ''],
        ]));
        $this->assertSame(2, $ready['summary']['eligible']);
        $rows = $this->rows($ready);
        $this->assertSame(['TRP2026#0051-0476', 'TRP2026#0051-0477'], array_column($rows, 'trip_code'));
        $this->assertSame(['ready_update', 'ready_create'], array_column($rows, 'status'));
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertNull($second->fresh()->driver_profile_id);
        $saved = $this->process($ready);
        $this->assertSame(1, $saved['updated']);
        $this->assertSame(1, $saved['created']);
        $this->assertSame(0, $saved['skipped']);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame($this->oldDriver->id, (int) $second->fresh()->driver_profile_id);
        $this->assertSame('TRP2026#0051-0476', $this->entry->fresh()->code);
        $this->assertSame('TRP2026#0051-0477', $second->fresh()->code);
    }

    public function test_trip_code_validation_rejects_blank_unknown_wrong_trip_and_mismatched_details(): void
    {
        $otherTrip = $this->trip->replicate();
        $otherTrip->save();
        $otherSheet = TripSheet::create(['trip_id' => $otherTrip->id, 'date' => '2026-09-08', 'status' => 'pending']);
        TripSheetEntry::create(['code' => 'WRONG-TRIP', 'trip_sheet_id' => $otherSheet->id, 'service_code' => '001', 'status' => 'pending']);
        $cases = [
            [['', '08-09-2026', '001', 'Driver Two', ''], 'Trip Code is required.'],
            [['UNKNOWN', '08-09-2026', '001', 'Driver Two', ''], 'No trip-sheet entry'],
            [['WRONG-TRIP', '08-09-2026', '001', 'Driver Two', ''], 'No trip-sheet entry'],
            [['TRP2026#0051-0476', '09-09-2026', '001', 'Driver Two', ''], 'Date or Service Code does not match'],
            [['TRP2026#0051-0476', '08-09-2026', 'OTHER', 'Driver Two', ''], 'Date or Service Code does not match'],
        ];
        foreach ($cases as [$input, $message]) {
            $ready = $this->preview($this->prepare([$input]));
            $row = $this->rows($ready)[0];
            $this->assertSame('issue', $row['status']);
            $this->assertStringContainsString($message, $row['reason']);
            $this->assertSame(0, $ready['summary']['eligible']);
            $this->assertSame(0, $ready['processed']);
        }
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame(1, Roster::count());
    }

    public function test_repeated_and_ambiguous_trip_codes_are_flagged_even_with_different_row_details(): void
    {
        $ready = $this->preview($this->prepare([
            ['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', ''],
            [' TRP2026#0051-0476 ', '09-09-2026', 'OTHER', 'Driver One', ''],
        ]));
        $rows = $this->rows($ready);
        $this->assertSame(['issue', 'issue'], array_column($rows, 'status'));
        foreach ($rows as $row) $this->assertStringContainsString('Duplicate Trip Code', $row['reason']);
        $duplicate = $this->entry->replicate();
        $duplicate->save();
        $ambiguous = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame('Trip Code matches multiple trip entries.', $this->rows($ambiguous)[0]['reason']);
        $this->assertSame(0, $ambiguous['summary']['eligible']);
    }

    public function test_legacy_four_column_file_requires_fresh_sample_and_changed_code_is_not_saved(): void
    {
        $file = $this->workbook([]);
        $book = IOFactory::load($file->getRealPath());
        $sheet = $book->getActiveSheet();
        $sheet->removeColumn('A');
        $sheet->fromArray(['08-09-2026', '001', 'Driver Two', 'BUS01'], null, 'A2');
        (new Xlsx($book))->save($file->getRealPath());
        $book->disconnectWorksheets();
        $response = $this->postJson($this->endpoint(), ['step' => 'prepare', 'roster_file' => $file])->assertUnprocessable();
        $this->assertStringContainsString('Download a fresh sample', $response->json('errors.roster_file.0'));
        $this->assertStringContainsString('previous four-column format', $response->json('errors.roster_file.0'));
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->entry->update(['code' => 'RENAMED-ENTRY']);
        $saved = $this->process($ready);
        $this->assertSame(1, $saved['skipped']);
        $this->assertSame(0, $saved['updated']);
        $this->assertStringContainsString('Trip Code changed since preview', $this->rows($saved)[0]['reason']);
        $this->assertSame('RENAMED-ENTRY', $this->entry->fresh()->code);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_creates_driver_only_roster_without_vehicle_or_oem_after_confirmation(): void
    {
        $entry = $this->entryOn('2026-09-09', ['vehicle_id' => null]);
        $ready = $this->preview($this->prepare([[$entry->code, '09-09-2026', '001', 'Driver Two', 'BUS01']]));
        $row = $this->rows($ready)[0];
        $this->assertSame('ready_create', $row['status']);
        $this->assertSame('Not assigned', $row['vehicle']);
        $this->assertNull($entry->fresh()->driver_profile_id);
        $this->assertSame(1, Roster::count());
        $saved = $this->process($ready);
        $this->assertSame(1, $saved['created']);
        $roster = $entry->fresh()->rosters->sole();
        $this->assertNull($roster->vehicle_id);
        $this->assertNull($roster->oem_id);
        $this->assertSame($this->freeDriver->id, (int) $roster->driver_profile_id);
        $this->assertSame('2026-09-09', $roster->duty_date->toDateString());
        $this->assertSame('08:00', substr($roster->shift_start_time, 0, 5));
        $this->assertSame('16:00', substr($roster->shift_end_time, 0, 5));
        $repeat = $this->preview($this->prepare([[$entry->code, '09-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame('unchanged', $this->rows($repeat)[0]['status']);
        $this->assertSame(0, $repeat['summary']['eligible']);
    }

    public function test_vehicle_free_shared_roster_preserves_oem_and_other_service(): void
    {
        $this->entry->update(['vehicle_id' => null]);
        $this->roster->update(['vehicle_id' => null, 'oem_id' => 42]);
        $other = $this->entry->replicate();
        $other->fill(['code' => 'SHARED-OTHER', 'service_code' => 'OTHER'])->save();
        $this->roster->tripSheetEntries()->attach($other);
        $saved = $this->process($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(1, $saved['updated']);
        $replacement = $this->entry->fresh()->rosters->sole();
        $this->assertNull($replacement->vehicle_id);
        $this->assertSame(42, (int) $replacement->oem_id);
        $this->assertSame(42, (int) $this->roster->fresh()->oem_id);
        $this->assertSame($this->oldDriver->id, (int) $other->fresh()->driver_profile_id);
        $this->assertSame($this->freeDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_vehicle_free_import_still_checks_driver_licence_leave_and_conflicts(): void
    {
        $this->entry->update(['vehicle_id' => null]);
        $this->roster->update(['vehicle_id' => null]);
        $input = [['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']];
        $this->freeDriver->update(['expiry_date' => '2026-09-07']);
        $expired = $this->preview($this->prepare($input));
        $this->assertSame('issue', $this->rows($expired)[0]['status']);
        $this->assertStringContainsString('licence', $this->rows($expired)[0]['reason']);
        $this->freeDriver->update(['expiry_date' => '2027-01-01']);
        $leave = Leave::create(['user_id' => $this->freeDriver->user_id, 'leave_for' => 'driver', 'status' => 'Approved', 'from_date' => '2026-09-08', 'to_date' => '2026-09-08', 'shift' => 'Morning']);
        $onLeave = $this->preview($this->prepare($input));
        $this->assertSame('issue', $this->rows($onLeave)[0]['status']);
        $this->assertStringContainsString('leave', $this->rows($onLeave)[0]['reason']);
        $leave->update(['status' => 'Cancelled']);
        $busy = $this->rosterFor($this->freeDriver);
        $conflict = $this->preview($this->prepare($input));
        $this->assertSame('issue', $this->rows($conflict)[0]['status']);
        $this->assertStringContainsString('overlapping duty', $this->rows($conflict)[0]['reason']);
        $busy->update(['status' => 'completed']);
        $ready = $this->preview($this->prepare($input));
        $this->assertSame('ready_update', $this->rows($ready)[0]['status']);
    }

    public function test_missing_schedule_errors_identify_trip_code_and_exact_field_without_vehicle(): void
    {
        $this->entry->update(['vehicle_id' => null]);
        $this->roster->update(['vehicle_id' => null]);
        $input = [['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']];
        foreach (['departure_time' => 'departure time', 'arrival_time' => 'arrival time'] as $field => $label) {
            $original = $this->entry->{$field};
            $this->entry->update([$field => null]);
            $ready = $this->preview($this->prepare($input));
            $this->assertSame('Trip-sheet entry TRP2026#0051-0476 has no '.$label.'. Complete the trip sheet before importing.', $this->rows($ready)[0]['reason']);
            $this->entry->update([$field => $original]);
        }
        Schema::table('trip_sheets', fn (Blueprint $table) => $table->date('date')->nullable()->change());
        $this->entry->sheet->update(['date' => null]);
        $ready = $this->preview($this->prepare($input));
        $this->assertSame('Trip-sheet entry TRP2026#0051-0476 has no duty date. Complete the trip sheet before importing.', $this->rows($ready)[0]['reason']);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_manual_roster_update_still_requires_vehicle_selection(): void
    {
        $this->entry->update(['vehicle_id' => null]);
        $this->roster->update(['vehicle_id' => null]);
        $this->post('/trips/'.$this->trip->id.'/update-roster', ['selected_ids' => [$this->entry->id],
            'assignments' => [$this->entry->id => ['driver_profile_id' => $this->freeDriver->id, 'vehicle_id' => null]]])->assertRedirect();
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
        $this->assertSame(1, session('roster_update_summary.skipped'));
    }

    public function test_vehicle_change_notification_handles_unassigned_vehicle(): void
    {
        Schema::create('controller_profiles', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('depot_id'); });
        Schema::create('user_device_tokens', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->string('app_type'); });
        $this->roster->update(['vehicle_id' => null]);
        $firebase = \Mockery::mock(\App\Services\FirebaseMessaging::class);
        $firebase->shouldNotReceive('send');
        $notifier = new RosterReassignmentNotifier($firebase);
        $result = $notifier->sendTripVehicleChange($this->roster->fresh(), $this->entry, $this->vehicle->id);
        $this->assertSame(0, $result['sent']);
        $this->assertSame(2, $result['failed']);
    }

    public function test_conflict_preview_lists_other_trip_identity_service_date_and_duty_times(): void
    {
        $otherTrip = $this->trip->replicate();
        $otherTrip->fill(['code' => 'TRP2026#0099'])->save();
        $sheet = TripSheet::create(['trip_id' => $otherTrip->id, 'date' => '2026-09-08', 'status' => 'pending']);
        $otherEntry = TripSheetEntry::create(['trip_sheet_id' => $sheet->id, 'code' => 'TRP2026#0099-0007', 'service_code' => 'ED77',
            'status' => 'pending', 'driver_profile_id' => $this->freeDriver->id, 'departure_time' => '09:00', 'arrival_time' => '11:00']);
        $this->rosterFor($this->freeDriver, ['shift_start_time' => '09:00', 'shift_end_time' => '11:00'])->tripSheetEntries()->attach($otherEntry);
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $row = $this->rows($ready)[0];
        $this->assertSame('issue', $row['status']);
        $this->assertSame(0, $ready['summary']['eligible']);
        $this->assertCount(1, $row['conflicts']);
        $this->assertSame(['trip_code' => 'TRP2026#0099-0007', 'parent_trip_code' => 'TRP2026#0099', 'service_code' => 'ED77',
            'date' => '08-09-2026', 'start_time' => '09:00', 'end_time' => '11:00', 'status' => 'assigned', 'source' => 'roster', 'resource' => 'driver'], $row['conflicts'][0]);
        $this->assertSame($this->oldDriver->id, (int) $this->entry->fresh()->driver_profile_id);
    }

    public function test_running_trip_without_roster_is_identified_and_completed_trip_is_excluded(): void
    {
        $running = $this->entryOn('2026-09-07', ['code' => 'RUNNING-OTHER', 'service_code' => 'RUN',
            'driver_profile_id' => $this->freeDriver->id, 'actual_start_time' => '23:00', 'departure_time' => '22:00', 'arrival_time' => '07:00']);
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $row = $this->rows($ready)[0];
        $this->assertSame('issue', $row['status']);
        $this->assertSame('RUNNING-OTHER', $row['conflicts'][0]['trip_code']);
        $this->assertSame('running_trip', $row['conflicts'][0]['source']);
        $this->assertSame('in_progress', $row['conflicts'][0]['status']);
        $this->assertSame('07-09-2026', $row['conflicts'][0]['date']);
        $this->assertSame('23:00', $row['conflicts'][0]['start_time']);
        $running->update(['actual_reach_time' => '07:00', 'status' => 'verification_completed', 'is_final_verified' => true]);
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame('ready_update', $this->rows($ready)[0]['status']);
        $this->assertSame([], $this->rows($ready)[0]['conflicts']);
    }

    public function test_all_shared_conflicting_trip_entries_are_listed_without_duplicates(): void
    {
        $one = $this->entryOn('2026-09-08', ['code' => 'BUSY-ONE', 'service_code' => 'B1', 'driver_profile_id' => $this->freeDriver->id, 'actual_start_time' => '08:00']);
        $two = $this->entryOn('2026-09-08', ['code' => 'BUSY-TWO', 'service_code' => 'B2']);
        $nonoverlapping = $this->entryOn('2026-09-08', ['code' => 'FREE-LATER', 'service_code' => 'B3', 'departure_time' => '18:00', 'arrival_time' => '20:00']);
        $this->rosterFor($this->freeDriver)->tripSheetEntries()->attach([$one->id, $two->id]);
        $this->rosterFor($this->freeDriver, ['shift_start_time' => '18:00', 'shift_end_time' => '20:00'])->tripSheetEntries()->attach($nonoverlapping);
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->assertSame(['BUSY-ONE', 'BUSY-TWO'], array_column($this->rows($ready)[0]['conflicts'], 'trip_code'));
    }

    public function test_completed_import_summary_survives_until_next_page_load(): void
    {
        $ready = $this->preview($this->prepare([['TRP2026#0051-0476', '08-09-2026', '001', 'Driver Two', '']]));
        $this->postJson($this->endpoint(), ['step' => 'confirm', 'token' => $ready['token'], 'confirmed' => true])->assertOk();
        $this->postJson($this->endpoint(), ['step' => 'save', 'token' => $ready['token'], 'offset' => 0])->assertOk()
            ->assertJsonPath('phase', 'done')->assertSessionHas('roster_update_summary.updated', 1);
        app('router')->get('/_test/roster-import-summary', fn () => response()->json([
            'summary' => session('roster_update_summary'), 'message' => session('success'),
        ]))->middleware('web');
        $this->getJson('/_test/roster-import-summary')->assertOk()->assertJsonPath('summary.updated', 1)
            ->assertJsonPath('message', 'Roster import completed: 0 created, 1 updated, 0 unchanged, 0 skipped.');
    }
}
