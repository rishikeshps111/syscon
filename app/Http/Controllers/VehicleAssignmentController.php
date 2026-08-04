<?php

namespace App\Http\Controllers;

use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('vehicles.view'), ['index']),
            new Middleware(PermissionMiddleware::using('vehicles.edit'), ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request, Vehicle $vehicle)
    {
        $vehicle->load(['state', 'oem', 'depot', 'branch']);

        if ($request->ajax()) {
            $query = TripSheetEntry::query()
                ->with([
                    'sheet:id,code,date,status',
                    'rosters' => fn ($query) => $query
                        ->where('vehicle_id', $vehicle->id)
                        ->with('driverProfile.user:id,code,name')
                        ->latest('rosters.id'),
                ])
                ->whereHas('rosters', fn ($query) => $query->where('vehicle_id', $vehicle->id))
                ->select('trip_sheet_entries.*')
                ->orderByDesc(
                    TripSheet::query()
                        ->select('date')
                        ->whereColumn('trip_sheets.id', 'trip_sheet_entries.trip_sheet_id')
                        ->limit(1)
                );

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('roster_code', fn (TripSheetEntry $entry) => $entry->rosters->first()?->code ?: '-')
                ->addColumn('trip_code', fn (TripSheetEntry $entry) => $entry->sheet?->code ?: '-')
                ->addColumn('driver', function (TripSheetEntry $entry): string {
                    $driver = $entry->rosters->first()?->driverProfile?->user;

                    return trim(($driver?->code ? $driver->code . ' - ' : '') . ($driver?->name ?? '')) ?: '-';
                })
                ->addColumn('trip_date', fn (TripSheetEntry $entry) => $entry->sheet?->date?->format('d M Y') ?? '-')
                ->editColumn('actual_start_time', fn (TripSheetEntry $entry) => $this->formatTime($entry->actual_start_time))
                ->editColumn('actual_reach_time', fn (TripSheetEntry $entry) => $this->formatTime($entry->actual_reach_time))
                ->editColumn('starting_km', fn (TripSheetEntry $entry) => $entry->starting_km ?? '-')
                ->editColumn('ending_km', fn (TripSheetEntry $entry) => $entry->ending_km ?? '-')
                ->addColumn('trip_status', function (TripSheetEntry $entry): string {
                    $status = $entry->sheet?->status;
                    $label = TripSheet::STATUSES[$status] ?? ($status ?: '-');
                    $class = match ($status) {
                        'verification_completed' => 'status-green',
                        'initial_verification_completed', 'pending' => 'status-orange',
                        'cancelled' => 'status-red',
                        default => 'status-blue',
                    };

                    return '<span class="' . $class . '">' . e($label) . '</span>';
                })
                ->rawColumns(['trip_status'])
                ->make(true);
        }

        return view('vehicle.assignments.index', compact('vehicle'));
    }

    private function formatTime(?string $time): string
    {
        if (! $time) {
            return '-';
        }

        return date('h:i A', strtotime($time));
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'status' => ['required', Rule::in(array_keys(VehicleAssignment::STATUSES))],
        ]);

        if (! User::role('Driver')->whereKey($validated['driver_id'])->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Please select a valid driver.',
            ]);
        }

        $assignment = $vehicle->assignments()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle assignment added successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, VehicleAssignment $vehicleAssignment)
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'status' => ['required', Rule::in(array_keys(VehicleAssignment::STATUSES))],
        ]);

        if (! User::role('Driver')->whereKey($validated['driver_id'])->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Please select a valid driver.',
            ]);
        }

        $vehicleAssignment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle assignment updated successfully.',
            'data' => $vehicleAssignment->fresh(),
        ]);
    }

    public function destroy(VehicleAssignment $vehicleAssignment)
    {
        $vehicleAssignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle assignment deleted successfully.',
        ]);
    }
}
