<?php

namespace Tests\Integration;

use App\Exports\TripReportExport;
use App\Models\{Depot, TripAssignment, TripSheetEntry, TripSheetEntryDor, Vehicle};
use App\Models\Route as RouteModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\TripAssignmentTestCase;

class TripReportExportTest extends TripAssignmentTestCase
{
    protected function createSchema(): void
    {
        parent::createSchema();
        Schema::table('users', fn (Blueprint $table) => $table->string('ref_code')->nullable());
    }

    private function reportEntry(): TripSheetEntry
    {
        $this->oldDriver->user->update(['ref_code' => '000123', 'code' => 'USER-CODE']);
        $this->freeDriver->user->update(['ref_code' => 'ROSTER-REF']);
        $entry = $this->entry->fresh()->load(['sheet.trip', 'driverProfile.user', 'rosters.driverProfile.user']);
        $entry->forceFill(['service_code' => 'ES01', 'departure_time' => '08:00', 'arrival_time' => '16:00']);
        $entry->driverProfile->forceFill(['badge_number' => 'PROFILE-BADGE']);
        $entry->sheet->trip->forceFill(['title' => 'HNK TO UPPAL', 'schedule_km' => 100]);
        $entry->sheet->trip->setRelation('route', new RouteModel(['route_code' => 'ROUTE-99', 'route_name' => 'HNK TO UPPAL', 'distance' => 100]));
        $entry->sheet->trip->setRelation('depot', new Depot(['name' => 'Depot A']));
        $entry->sheet->trip->setRelation('assignments', collect());
        $entry->setRelation('vehicle', new Vehicle(['vehicle_no' => 'TS01AB1234']));
        $entry->setRelation('dor', new TripSheetEntryDor([
            'depot_name' => 'Recorded Depot', 'dor_date' => '2026-09-08', 'bus_no' => 'TS02CD5678',
            'route_no' => 'OLD-ROUTE-NUMBER', 'duty' => 'HNK TO UPPAL (RECORDED)', 'driver_badge_no' => 'DOR-BADGE',
            'schedule_km' => 100, 'schedule_trip' => 2, 'actual_trip' => 1, 'miss_trip' => 1,
        ]));
        return $entry;
    }

    private function exportFor(TripSheetEntry $entry): TripReportExport
    {
        $query = \Mockery::mock();
        $query->shouldReceive('get')->andReturn(collect([$entry]));
        return new TripReportExport($query);
    }

    private function workbook(TripReportExport $export, callable $assertions): void
    {
        $path = tempnam(sys_get_temp_dir(), 'trip-report-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $book = IOFactory::load($path);
            try {
                $assertions($book->getActiveSheet());
            } finally {
                $book->disconnectWorksheets();
            }
        } finally {
            unlink($path);
        }
    }

    public function test_excel_columns_show_route_ser_and_driver_reference_and_preserve_other_columns(): void
    {
        $entry = $this->reportEntry();
        $this->workbook($this->exportFor($entry), function ($sheet) {
            $this->assertSame('Route', $sheet->getCell('E1')->getValue());
            $this->assertSame('Duty', $sheet->getCell('F1')->getValue());
            $this->assertSame('Driver ID', $sheet->getCell('H1')->getValue());
            $this->assertSame('HNK TO UPPAL (RECORDED)', $sheet->getCell('E2')->getValue());
            $this->assertSame('ES01', $sheet->getCell('F2')->getValue());
            $this->assertSame('000123', $sheet->getCell('H2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H2')->getDataType());
            $this->assertSame('AV', $sheet->getHighestDataColumn());
            $this->assertSame(1, $sheet->getCell('A2')->getValue());
            $this->assertSame('Recorded Depot', $sheet->getCell('B2')->getValue());
            $this->assertSame('08-09-2026', $sheet->getCell('C2')->getValue());
            $this->assertSame('TS02CD5678', $sheet->getCell('D2')->getValue());
            $this->assertSame('Morning', $sheet->getCell('G2')->getValue());
            $this->assertSame('08:00', $sheet->getCell('I2')->getValue());
            $this->assertSame('16:00', $sheet->getCell('J2')->getValue());
            $this->assertSame(100.0, $sheet->getCell('O2')->getValue());
            $this->assertSame(2, $sheet->getCell('R2')->getValue());
            $this->assertSame(1, $sheet->getCell('S2')->getValue());
            $this->assertSame(1, $sheet->getCell('T2')->getValue());
            $this->assertSame('H2', $sheet->getFreezePane());
            $this->assertSame('A1:AV2', $sheet->getAutoFilter()->getRange());
        });
    }

    public function test_route_falls_back_to_trip_title_and_duty_never_uses_dor_text(): void
    {
        $entry = $this->reportEntry();
        $entry->dor->duty = null;
        $row = $this->exportFor($entry)->collection()->sole();
        $this->assertSame('HNK TO UPPAL', $row[4]);
        $this->assertSame('ES01', $row[5]);
        $entry->service_code = null;
        $entry->dor->duty = 'A SAVED ROUTE';
        $row = $this->exportFor($entry)->collection()->sole();
        $this->assertSame('A SAVED ROUTE', $row[4]);
        $this->assertSame('', $row[5]);
    }

    public function test_driver_reference_uses_entry_then_roster_then_permitted_recurring_assignment(): void
    {
        $entry = $this->reportEntry();
        $entry->roster->setRelation('driverProfile', $this->freeDriver->load('user'));
        $this->assertSame('000123', $this->exportFor($entry)->collection()->sole()[7]);
        $entry->setRelation('driverProfile', null);
        $this->assertSame('ROSTER-REF', $this->exportFor($entry)->collection()->sole()[7]);
        $entry->setRelation('rosters', collect());
        $this->roster->tripSheetEntries()->detach($entry->id);
        $assignment = new TripAssignment(['from_date' => '2026-09-01', 'to_date' => '2026-09-30']);
        $assignment->setRelation('driverProfile', $this->oldDriver->load('user'));
        $entry->sheet->trip->setRelation('assignments', collect([$assignment]));
        $this->assertSame('000123', $this->exportFor($entry)->collection()->sole()[7]);
        $this->roster->update(['driver_profile_id' => null, 'status' => 'unassigned']);
        $this->roster->tripSheetEntries()->attach($entry->id);
        $entry->setRelation('rosters', collect([$this->roster->fresh()->load('driverProfile')]));
        $this->assertSame('', $this->exportFor($entry)->collection()->sole()[7]);
    }

    public function test_missing_reference_stays_blank_instead_of_using_badge_or_user_code_or_other_driver(): void
    {
        $entry = $this->reportEntry();
        $entry->driverProfile->user->ref_code = null;
        $entry->roster->setRelation('driverProfile', $this->freeDriver->load('user'));
        $this->workbook($this->exportFor($entry), function ($sheet) {
            $this->assertSame('', (string) $sheet->getCell('H2')->getValue());
        });
    }

    public function test_numeric_and_formula_like_driver_references_are_written_as_literal_text(): void
    {
        $entry = $this->reportEntry();
        foreach (['12345', '=123+456', '12345678901234567890'] as $reference) {
            $entry->driverProfile->user->ref_code = $reference;
            $this->workbook($this->exportFor($entry), function ($sheet) use ($reference) {
                $this->assertSame($reference, $sheet->getCell('H2')->getValue());
                $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H2')->getDataType());
            });
        }
    }
}
