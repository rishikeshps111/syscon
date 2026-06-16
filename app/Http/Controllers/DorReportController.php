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
use Yajra\DataTables\Facades\DataTables;

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

        if ($request->ajax()) {
            return DataTables::of($this->query($filters))
                ->addIndexColumn()
                ->addColumn('trip_sheet_code', fn (TripSheetEntryDor $dor) => $dor->tripSheetEntry?->sheet?->code ?: '-')
                ->addColumn('sheet_date', fn (TripSheetEntryDor $dor) => $dor->tripSheetEntry?->sheet?->date?->format('d M Y') ?: ($dor->dor_date?->format('d M Y') ?: '-'))
                ->addColumn('side', fn (TripSheetEntryDor $dor) => ucfirst((string) ($dor->tripSheetEntry?->side ?: $dor->shift)) ?: '-')
                ->addColumn('depot_name', fn (TripSheetEntryDor $dor) => $dor->depot_name ?: ($dor->tripSheetEntry?->sheet?->trip?->depot?->name ?: '-'))
                ->addColumn('vehicle_no', fn (TripSheetEntryDor $dor) => $this->vehicleNo($dor))
                ->addColumn('driver', fn (TripSheetEntryDor $dor) => $this->driverName($dor))
                ->make(true);
        }

        return view('reports.dor', [
            'filters' => $filters,
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'trips' => Trip::orderBy('code')->orderBy('title')->get(['id', 'code', 'title', 'route_id']),
            'vehicles' => Vehicle::orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $from = $filters['date_from'] ?? 'all';
        $to = $filters['date_to'] ?? 'all';

        return Excel::download(
            new DorReportExport($this->query($filters)),
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
}
