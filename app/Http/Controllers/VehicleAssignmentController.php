<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
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
        $drivers = User::role('Driver')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $routes = RouteModel::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $statuses = VehicleAssignment::STATUSES;

        if ($request->ajax()) {
            $query = VehicleAssignment::with(['driver', 'route'])
                ->where('vehicle_id', $vehicle->id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('driver_name', fn ($row) => trim(($row->driver?->code ? $row->driver->code . ' - ' : '') . ($row->driver?->name ?? '-')))
                ->addColumn('route_name', fn ($row) => trim(($row->route?->code ? $row->route->code . ' - ' : '') . ($row->route?->name ?? '-')))
                ->addColumn('assigned_from_display', fn ($row) => $row->assigned_from?->format('d-m-Y h:i A') ?? '-')
                ->addColumn('assigned_to_display', fn ($row) => $row->assigned_to?->format('d-m-Y h:i A') ?? '-')
                ->addColumn('status_badge', fn ($row) => $row->status === 'Active'
                    ? '<span class="status-green">Active</span>'
                    : '<span class="status-orange">Completed</span>')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('vehicles.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $editButton = '<button type="button" class="btn-edit edit-assignment" title="Edit" data-bs-toggle="modal" data-bs-target="#addAssignmentModal"'
                        . ' data-url="' . e(route('vehicle-assignments.update', $row->id)) . '"'
                        . ' data-driver-id="' . e($row->driver_id) . '"'
                        . ' data-route-id="' . e($row->route_id) . '"'
                        . ' data-assigned-from="' . e($row->assigned_from?->format('Y-m-d\TH:i')) . '"'
                        . ' data-assigned-to="' . e($row->assigned_to?->format('Y-m-d\TH:i')) . '"'
                        . ' data-status="' . e($row->status) . '">'
                        . '<i class="fa-solid fa-pen-to-square"></i></button>';
                    $deleteButton = '<button type="button" class="btn-delete" onclick="deleteAssignment(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';

                    return '<div class="action-btns justify-content-center">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('vehicle.assignments.index', compact('vehicle', 'drivers', 'routes', 'statuses'));
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
