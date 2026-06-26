<?php

namespace App\Http\Controllers;

use App\Exports\DorReportExport;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Trip;
use App\Models\TripSheetEntryDor;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;

class DorReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('dor-reports.view'), ['index', 'export']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $selectedColumns = $this->selectedColumns($filters['columns'] ?? []);

        if ($request->ajax() && $request->boolean('generate')) {
            $report = $this->reportData($filters, $selectedColumns);

            return response()->json([
                'success' => $report['rows']->isNotEmpty(),
                'html' => view('reports.partials.dor-report-table', $report)->render(),
                'message' => $report['rows']->isNotEmpty()
                    ? 'DOR report generated successfully.'
                    : 'No DOR records found for the selected filters.',
                'download_excel_url' => route('reports.dor.export', $filters + ['columns' => $selectedColumns]),
                'filters' => $filters,
            ]);
        }

        return view('reports.dor', [
            'filters' => $filters,
            'dorColumns' => $this->dorColumns(),
            'selectedColumns' => $selectedColumns,
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'trips' => Trip::orderBy('code')->orderBy('title')->get(['id', 'code', 'title', 'route_id']),
            'vehicles' => Vehicle::orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $selectedColumns = $this->selectedColumns($filters['columns'] ?? []);
        $from = $filters['date_from'] ?? 'all';
        $to = $filters['date_to'] ?? 'all';

        return Excel::download(
            new DorReportExport($this->query($filters), $this->reportColumns($selectedColumns)),
            "dor-report-{$from}-{$to}.xlsx"
        );
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'depot_id' => ['nullable', 'integer', 'exists:depots,id'],
            'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
        ]);
    }

    private function query(array $filters): Builder
    {
        $query = TripSheetEntryDor::query()
            ->with([
                'tripSheetEntry.sheet.trip.depot',
                'tripSheetEntry.sheet.trip.route',
                'tripSheetEntry.sheet.trip.assignments.driverProfile.user',
                'tripSheetEntry.sheet.trip.assignments.vehicle',
                'tripSheetEntry.driverProfile.user',
                'tripSheetEntry.vehicle',
            ])
            ->whereHas('tripSheetEntry.sheet')
            ->join('trip_sheet_entries', 'trip_sheet_entry_dors.trip_sheet_entry_id', '=', 'trip_sheet_entries.id')
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->join('trips', 'trip_sheets.trip_id', '=', 'trips.id')
            ->select('trip_sheet_entry_dors.*')
            ->orderByDesc('trip_sheets.date')
            ->orderByDesc('trip_sheet_entry_dors.id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('trip_sheets.date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('trip_sheets.date', '<=', $filters['date_to']);
        }

        if (! empty($filters['depot_id'])) {
            $query->where('trips.depot_id', $filters['depot_id']);
        }

        if (! empty($filters['trip_id'])) {
            $query->where('trip_sheets.trip_id', $filters['trip_id']);
        }

        if (! empty($filters['vehicle_id'])) {
            $vehicleNo = Vehicle::whereKey($filters['vehicle_id'])->value('vehicle_no');

            $query->where(function (Builder $subQuery) use ($filters, $vehicleNo) {
                $subQuery->where('trip_sheet_entries.vehicle_id', $filters['vehicle_id']);

                if ($vehicleNo) {
                    $subQuery->orWhere('trip_sheet_entry_dors.bus_no', $vehicleNo);
                }
            });
        }

        if (! empty($filters['driver_profile_id'])) {
            $driver = DriverProfile::with('user')->find($filters['driver_profile_id']);

            $query->where(function (Builder $subQuery) use ($filters, $driver) {
                $subQuery->where('trip_sheet_entries.driver_profile_id', $filters['driver_profile_id']);

                foreach (array_filter([$driver?->badge_number, $driver?->user?->code]) as $driverCode) {
                    $subQuery->orWhere('trip_sheet_entry_dors.driver_badge_no', $driverCode);
                }
            });
        }

        return $query;
    }

    private function vehicleNo(TripSheetEntryDor $dor): string
    {
        $entry = $dor->tripSheetEntry;
        $assignment = $entry ? TripController::assignmentForCompletedEntry($entry) : null;

        return $dor->bus_no
            ?: ($entry?->vehicle?->vehicle_no ?: ($assignment?->vehicle?->vehicle_no ?: '-'));
    }

    private function driverName(TripSheetEntryDor $dor): string
    {
        $entry = $dor->tripSheetEntry;
        $assignment = $entry ? TripController::assignmentForCompletedEntry($entry) : null;
        $driver = $entry?->driverProfile ?: $assignment?->driverProfile;

        return $driver?->user?->name ?: ($dor->driver_badge_no ?: '-');
    }

    private function reportData(array $filters, array $selectedColumns): array
    {
        $columns = $this->reportColumns($selectedColumns);
        $rows = $this->query($filters)
            ->get()
            ->values()
            ->map(fn (TripSheetEntryDor $dor, int $index) => $this->rowData($dor, $index, $columns));

        return [
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public function rowData(TripSheetEntryDor $dor, int $index, array $columns): array
    {
        $data = [
            'sl_no' => $index + 1,
            'trip_sheet_code' => $dor->tripSheetEntry?->sheet?->code ?: '-',
            'sheet_date' => $dor->tripSheetEntry?->sheet?->date?->format('d-m-Y') ?: ($dor->dor_date?->format('d-m-Y') ?: '-'),
            'side' => ucfirst((string) ($dor->tripSheetEntry?->side ?: $dor->shift)) ?: '-',
            'report_depot_name' => $dor->depot_name ?: ($dor->tripSheetEntry?->sheet?->trip?->depot?->name ?: '-'),
            'vehicle_no' => $this->vehicleNo($dor),
            'driver' => $this->driverName($dor),
        ];

        foreach ($this->dorColumns() as $key => $label) {
            $value = $dor->{$key};

            if ($value instanceof \Carbon\CarbonInterface) {
                $value = $value->format(str_contains($key, '_at') ? 'd-m-Y H:i:s' : 'd-m-Y');
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_string($value) && str_contains($key, '_time')) {
                $value = substr($value, 0, 5);
            }

            $data[$key] = filled($value) ? $value : '-';
        }

        return collect($columns)
            ->mapWithKeys(fn (string $label, string $key) => [$key => $data[$key] ?? '-'])
            ->all();
    }

    private function reportColumns(array $selectedColumns): array
    {
        return $this->baseColumns() + collect($this->dorColumns())
            ->only($selectedColumns)
            ->all();
    }

    private function selectedColumns(array $columns): array
    {
        return collect($columns)
            ->filter(fn ($column) => is_string($column) && array_key_exists($column, $this->dorColumns()))
            ->unique()
            ->values()
            ->all();
    }

    private function baseColumns(): array
    {
        return [
            'sl_no' => 'SL No',
            'trip_sheet_code' => 'Trip Sheet Code',
            'sheet_date' => 'Date',
            'side' => 'Side',
            'report_depot_name' => 'Depot Name',
            'vehicle_no' => 'Vehicle No',
            'driver' => 'Driver',
        ];
    }

    private function dorColumns(): array
    {
        return [
            'id' => 'DOR ID',
            'trip_sheet_entry_id' => 'Trip Sheet Entry ID',
            'depot_name' => 'DOR Depot Name',
            'dor_date' => 'DOR Date',
            'bus_no' => 'Bus No',
            'route_no' => 'Route No',
            'duty' => 'Duty',
            'shift' => 'Shift',
            'driver_badge_no' => 'Driver Badge No',
            'schedule_start_time' => 'Schedule Start Time',
            'schedule_end_time' => 'Schedule End Time',
            'actual_start_time' => 'Actual Start Time',
            'actual_end_time' => 'Actual End Time',
            'start_punc' => 'Start Punc',
            'route_completion_time' => 'Route Completion Time',
            'schedule_km' => 'Schedule Km',
            'route_km_loss' => 'Route Km Loss',
            'actual_route_km' => 'Actual Route Km',
            'schedule_trip' => 'Schedule Trip',
            'actual_trip' => 'Actual Trip',
            'miss_trip' => 'Miss Trip',
            'odometer_start_reading' => 'Odometer Start Reading',
            'odometer_start_image_path' => 'Odometer Start Image Path',
            'odometer_end_reading' => 'Odometer End Reading',
            'odometer_end_image_path' => 'Odometer End Image Path',
            'odometer_diff_km' => 'Odometer Diff Km',
            'difference' => 'Difference',
            'dor_account_responsible_id' => 'DOR Account Responsible ID',
            'account_responsible' => 'Account Responsible',
            'dor_kilometer_loss_reason_id' => 'DOR Kilometer Loss Reason ID',
            'reason_for_kilometer_loss' => 'Reason For Kilometer Loss',
            'after_sales_reason' => 'After Sales Reason',
            'penalty_infraction' => 'Penalty Infraction',
            'remarks' => 'Remarks',
            'route_start_soc_percent' => 'Route Start SOC Percent',
            'route_end_soc_percent' => 'Route End SOC Percent',
            'soc_consumption_on_route_percent' => 'SOC Consumption On Route Percent',
            'soc_per_km' => 'SOC Per KM',
            'run_kilometer_per_soc' => 'Run Kilometer Per SOC',
            'dor_kwh_per_km_odo' => 'DOR KWh Per KM Odo',
            'dor_kwh_per_km_act' => 'DOR KWh Per KM Act',
            'dor_kwh' => 'DOR KWh',
            'dcr_kwh_per_km_odo' => 'DCR KWh Per KM Odo',
            'dcr_kwh_per_km_act' => 'DCR KWh Per KM Act',
            'dcr_kwh' => 'DCR KWh',
            'dcr_charged_soc' => 'DCR Charged SOC',
            'energy_absorption' => 'Energy Absorption',
            'battery_size_kwh' => 'Battery Size KWh',
            'vp1' => 'VP1',
            'vp2' => 'VP2',
            'dp' => 'DP',
            'penalty' => 'Penalty',
            'model_9m_12m' => 'Model 9M/12M',
            'is_completed' => 'Is Completed',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
