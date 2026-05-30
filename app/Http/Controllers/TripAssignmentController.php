<?php

namespace App\Http\Controllers;

use App\Models\DriverProfile;
use App\Models\Trip;
use App\Models\TripAssignment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class TripAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trips.view'), ['index']),
            new Middleware(PermissionMiddleware::using('trips.assign'), ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request, Trip $trip)
    {
        $trip->load(['serviceType', 'route.startPoint', 'route.endPoint', 'depot']);

        if ($request->ajax()) {
            $query = $trip->assignments()
                ->with(['vehicle', 'driverProfile.user'])
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('date_range', fn ($row) => ($row->from_date?->format('d-m-Y') ?? '-') . ' - ' . ($row->to_date?->format('d-m-Y') ?? '-'))
                ->addColumn('vehicle_no', fn ($row) => trim(($row->vehicle?->vehicle_no ?? '-') . ($row->vehicle?->vehicle_type ? ' - ' . $row->vehicle->vehicle_type : '')))
                ->addColumn('driver_name', fn ($row) => $row->driverProfile?->user?->name ?? '-')
                ->addColumn('notes_display', fn ($row) => $row->notes ?: '-')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('trips.assign')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $editButton = '<button type="button" class="btn-edit edit-assignment" title="Edit" data-bs-toggle="modal" data-bs-target="#assignmentModal"'
                        . ' data-url="' . e(route('trip-assignments.update', $row->id)) . '"'
                        . ' data-from-date="' . e($row->from_date?->format('Y-m-d')) . '"'
                        . ' data-to-date="' . e($row->to_date?->format('Y-m-d')) . '"'
                        . ' data-vehicle-id="' . e($row->vehicle_id) . '"'
                        . ' data-driver-profile-id="' . e($row->driver_profile_id) . '"'
                        . ' data-notes="' . e($row->notes) . '">'
                        . '<i class="fa-solid fa-pen-to-square"></i></button>';

                    $deleteButton = '<button type="button" class="btn-delete" onclick="deleteAssignment(' . $row->id . ')" title="Delete">'
                        . '<i class="fa-solid fa-trash"></i></button>';

                    return '<div class="action-btns justify-content-center">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('trip.assignments.index', [
            'trip' => $trip,
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no', 'vehicle_type']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, Trip $trip)
    {
        $assignment = $trip->assignments()->create($this->validatedData($request) + [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $trip->update([
            'from_date' => $trip->from_date ?: $assignment->from_date,
            'to_date' => $trip->to_date ?: $assignment->to_date,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip assignment saved successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, TripAssignment $tripAssignment)
    {
        $tripAssignment->update($this->validatedData($request) + [
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip assignment updated successfully.',
            'data' => $tripAssignment->fresh(),
        ]);
    }

    public function destroy(TripAssignment $tripAssignment)
    {
        $tripAssignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip assignment deleted successfully.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_profile_id' => ['required', 'integer', 'exists:driver_profiles,id'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
