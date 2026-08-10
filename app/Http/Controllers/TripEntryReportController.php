<?php

namespace App\Http\Controllers;

use App\Exports\TripEntryReportExport;
use App\Models\ControllerProfile;
use App\Models\DriverProfile;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripSheetEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TripEntryReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trip-entry-reports.view')),
        ];
    }

    public function index(Request $request)
    {
        return $this->indexFor($request, 'trip');
    }

    public function driverIndex(Request $request) { return $this->indexFor($request, 'driver'); }
    public function supervisorIndex(Request $request) { return $this->indexFor($request, 'supervisor'); }
    public function controllerIndex(Request $request) { return $this->indexFor($request, 'controller'); }

    public function export(Request $request) { return $this->exportFor($request, 'trip'); }
    public function driverExport(Request $request) { return $this->exportFor($request, 'driver'); }
    public function supervisorExport(Request $request) { return $this->exportFor($request, 'supervisor'); }
    public function controllerExport(Request $request) { return $this->exportFor($request, 'controller'); }

    private function indexFor(Request $request, string $type)
    {
        $config = $this->reportConfig($type);
        $filters = $this->validatedFilters($request, $config);
        $selectedColumns = $this->selectedColumns($filters['columns'] ?? []);

        if ($request->ajax() && $request->boolean('generate')) {
            $report = $this->reportData($filters, $selectedColumns, $type);

            return response()->json([
                'success' => $report['rows']->isNotEmpty(),
                'html' => view('reports.partials.trip-entry-report-table', $report)->render(),
                'message' => $report['rows']->isNotEmpty()
                    ? $config['title'] . ' generated successfully.'
                    : 'No trip sheet entries found for the selected ' . strtolower($config['selector_label']) . '.',
                'download_excel_url' => route($config['export_route'], [
                    $config['filter_key'] => $filters[$config['filter_key']],
                    'columns' => $selectedColumns,
                ]),
            ]);
        }

        return view('reports.trip-entries', [
            'filters' => $filters,
            'reportTitle' => $config['title'],
            'indexRoute' => $config['index_route'],
            'filterKey' => $config['filter_key'],
            'selectorLabel' => $config['selector_label'],
            'selectorOptions' => $this->selectorOptions($type),
            'reportColumns' => $this->availableColumns(),
            'selectedColumns' => $selectedColumns,
        ]);
    }

    private function exportFor(Request $request, string $type)
    {
        $config = $this->reportConfig($type);
        $filters = $this->validatedFilters($request, $config);
        $columns = $this->reportColumns($this->selectedColumns($filters['columns'] ?? []));

        return Excel::download(
            new TripEntryReportExport($this->query($filters, $type), $columns),
            $config['filename'] . '.xlsx'
        );
    }

    public function rowData(TripSheetEntry $entry, int $index, array $columns): array
    {
        $data = ['sl_no' => $index + 1];

        foreach ($this->entryColumns() as $field => $label) {
            $data['entry_' . $field] = $this->formatValue($this->entryValue($entry, $field), $field);
        }

        foreach ($this->dorColumns() as $field => $label) {
            $data['dor_' . $field] = $this->formatValue($this->dorValue($entry, $field), $field);
        }

        return collect($columns)
            ->mapWithKeys(fn (string $label, string $key) => [$key => $data[$key] ?? '-'])
            ->all();
    }

    private function reportData(array $filters, array $selectedColumns, string $type): array
    {
        $columns = $this->reportColumns($selectedColumns);
        $rows = $this->query($filters, $type)->get()->values()
            ->map(fn (TripSheetEntry $entry, int $index) => $this->rowData($entry, $index, $columns));

        return compact('columns', 'rows');
    }

    private function query(array $filters, string $type): Builder
    {
        $query = TripSheetEntry::query()
            ->with([
                'sheet',
                'driverProfile.user',
                'vehicle',
                'dor.accountResponsible',
                'dor.kilometerLossReason',
                'dor.createdBy',
                'dor.updatedBy',
            ])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id');

        if ($type === 'trip') {
            $query->where('trip_sheets.trip_id', $filters['trip_id']);
        } elseif ($type === 'driver') {
            $query->where('trip_sheet_entries.driver_profile_id', $filters['driver_profile_id']);
        } else {
            $profileClass = $type === 'controller' ? ControllerProfile::class : SupervisorProfile::class;
            $name = $profileClass::with('user')->findOrFail($filters[$type . '_profile_id'])->user?->name;
            $query->when(
                filled($name),
                fn (Builder $query) => $query->where(function (Builder $verificationQuery) use ($name): void {
                    $verificationQuery->where('trip_sheet_entries.initial_verification_by', $name)
                        ->orWhere('trip_sheet_entries.final_verification_by', $name);
                }),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            );
        }

        return $query->select('trip_sheet_entries.*')
            ->orderByDesc('trip_sheets.date')
            ->orderBy('trip_sheet_entries.trip_order_sequence_no')
            ->orderBy('trip_sheet_entries.id');
    }

    private function validatedFilters(Request $request, array $config): array
    {
        return $request->validate([
            $config['filter_key'] => [
                Rule::requiredIf($request->boolean('generate') || $request->routeIs($config['export_route'])),
                'nullable', 'integer', 'exists:' . $config['table'] . ',id',
            ],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
        ]);
    }

    private function reportConfig(string $type): array
    {
        return match ($type) {
            'driver' => ['title' => 'Driver Trip Report', 'selector_label' => 'Driver', 'filter_key' => 'driver_profile_id', 'table' => 'driver_profiles', 'index_route' => 'reports.driver-trips.index', 'export_route' => 'reports.driver-trips.export', 'filename' => 'driver-trip-report'],
            'supervisor' => ['title' => 'Supervisor Trip Report', 'selector_label' => 'Supervisor', 'filter_key' => 'supervisor_profile_id', 'table' => 'supervisor_profiles', 'index_route' => 'reports.supervisor-trips.index', 'export_route' => 'reports.supervisor-trips.export', 'filename' => 'supervisor-trip-report'],
            'controller' => ['title' => 'Controller Trip Report', 'selector_label' => 'Controller', 'filter_key' => 'controller_profile_id', 'table' => 'controller_profiles', 'index_route' => 'reports.controller-trips.index', 'export_route' => 'reports.controller-trips.export', 'filename' => 'controller-trip-report'],
            default => ['title' => 'Trip Report', 'selector_label' => 'Trip', 'filter_key' => 'trip_id', 'table' => 'trips', 'index_route' => 'reports.trip-entries.index', 'export_route' => 'reports.trip-entries.export', 'filename' => 'trip-report'],
        };
    }

    private function selectorOptions(string $type)
    {
        if ($type === 'trip') {
            return Trip::orderBy('code')->orderBy('title')->get()->map(fn (Trip $trip) => [
                'id' => $trip->id,
                'label' => trim(($trip->code ? $trip->code . ' - ' : '') . $trip->trip_title),
            ]);
        }

        $class = match ($type) {
            'driver' => DriverProfile::class,
            'controller' => ControllerProfile::class,
            default => SupervisorProfile::class,
        };

        return $class::with('user')->whereHas('user', fn (Builder $query) => $query->where('is_active', true))
            ->get()->sortBy(fn ($profile) => $profile->user?->name)->values()->map(fn ($profile) => [
                'id' => $profile->id,
                'label' => trim(($profile->user?->code ? $profile->user->code . ' - ' : '') . ($profile->user?->name ?? '')),
            ]);
    }

    private function selectedColumns(array $columns): array
    {
        $available = $this->availableColumns();

        if ($columns === []) {
            return array_keys($available);
        }

        return collect($columns)->filter(fn ($column) => is_string($column) && isset($available[$column]))
            ->unique()->values()->all();
    }

    private function reportColumns(array $selectedColumns): array
    {
        return ['sl_no' => 'SL No'] + collect($this->availableColumns())->only($selectedColumns)->all();
    }

    private function availableColumns(): array
    {
        return collect($this->entryColumns())->mapWithKeys(fn ($label, $field) => ['entry_' . $field => 'Entry - ' . $label])
            ->merge(collect($this->dorColumns())->mapWithKeys(fn ($label, $field) => ['dor_' . $field => 'DOR - ' . $label]))
            ->all();
    }

    private function entryColumns(): array
    {
        return $this->labels([
            'code', 'status', 'trip_sheet', 'side', 'departure_time', 'arrival_time',
            'actual_start_time', 'actual_reach_time', 'driver', 'vehicle',
            'trip_order_sequence_no', 'service_code', 'round_no', 'trip_nature', 'schedule_km',
            'starting_km', 'ending_km', 'starting_electric_charge', 'ending_electric_charge',
            'vehicle_condition', 'energy_status', 'accident_status', 'accident_remarks',
            'vehicle_breakdown', 'medical_emergency', 'passenger_issue', 'security_threat',
            'is_vehicle_verified', 'vehicle_verified_by', 'vehicle_verified_at', 'is_driver_verified',
            'driver_verified_by', 'driver_verified_at', 'is_initial_verified', 'initial_verification_by',
            'initial_verification_at', 'is_final_verified', 'final_verification_by',
            'final_verification_at', 'notes', 'created_at', 'updated_at',
        ]);
    }

    private function dorColumns(): array
    {
        return $this->labels([
            'depot_name', 'dor_date', 'bus_no', 'route_no', 'duty',
            'shift', 'driver_badge_no', 'schedule_start_time', 'schedule_end_time', 'actual_start_time',
            'actual_end_time', 'start_punc', 'route_completion_time', 'schedule_km', 'route_km_loss',
            'actual_route_km', 'schedule_trip', 'actual_trip', 'miss_trip', 'odometer_start_reading',
            'odometer_start_image_path', 'odometer_end_reading', 'odometer_end_image_path',
            'odometer_diff_km', 'difference', 'dor_account_responsible', 'account_responsible',
            'dor_kilometer_loss_reason', 'reason_for_kilometer_loss', 'after_sales_reason',
            'penalty_infraction', 'remarks', 'route_start_soc_percent', 'route_start_soc_percent_image',
            'route_end_soc_percent', 'route_end_soc_percent_image', 'soc_consumption_on_route_percent',
            'soc_per_km', 'run_kilometer_per_soc', 'dor_kwh_per_km_odo', 'dor_kwh_per_km_act',
            'dor_kwh', 'dcr_kwh_per_km_odo', 'dcr_kwh_per_km_act', 'dcr_kwh', 'dcr_charged_soc',
            'energy_absorption', 'battery_size_kwh', 'vp1', 'vp2', 'dp', 'penalty', 'model_9m_12m',
            'is_completed', 'created_by_name', 'updated_by_name', 'created_at', 'updated_at',
        ]);
    }

    private function entryValue(TripSheetEntry $entry, string $field): mixed
    {
        return match ($field) {
            'trip_sheet' => $entry->sheet?->code,
            'driver' => trim(($entry->driverProfile?->user?->code ? $entry->driverProfile->user->code . ' - ' : '') . ($entry->driverProfile?->user?->name ?? '')),
            'vehicle' => $entry->vehicle?->vehicle_no,
            default => $entry->{$field},
        };
    }

    private function dorValue(TripSheetEntry $entry, string $field): mixed
    {
        $dor = $entry->dor;

        if (! $dor) {
            return null;
        }

        return match ($field) {
            'dor_account_responsible' => $dor->accountResponsible?->name,
            'dor_kilometer_loss_reason' => $dor->kilometerLossReason?->name,
            'created_by_name' => $dor->createdBy?->name,
            'updated_by_name' => $dor->updatedBy?->name,
            default => $dor->{$field},
        };
    }

    private function labels(array $fields): array
    {
        return collect($fields)->mapWithKeys(fn ($field) => [$field => str($field)->replace('_', ' ')->title()->toString()])->all();
    }

    private function formatValue(mixed $value, string $field): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->format(str_ends_with($field, '_at') ? 'd-m-Y H:i:s' : 'd-m-Y');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value) && str_contains($field, 'time')) {
            return substr($value, 0, 5);
        }

        return filled($value) ? $value : '-';
    }
}
