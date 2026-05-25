<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
use App\Models\RouteAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RouteAssignmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('routes.edit'), ['index', 'create', 'store', 'update']),
            new Middleware(PermissionMiddleware::using('routes.delete'), ['destroy']),
        ];
    }

    public function index(RouteModel $route, Request $request)
    {
        $route->load(['state', 'startPoint', 'endPoint']);

        if ($request->ajax()) {
            $query = $route->routeAssignments()
                ->with(['vehicle', 'driver'])
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('vehicle_name', fn ($row) => $row->vehicle?->vehicle_no ?? '-')
                ->addColumn('driver_name', fn ($row) => trim(($row->driver?->code ? $row->driver->code . ' - ' : '') . ($row->driver?->name ?? '-')))
                ->editColumn('start_time', fn ($row) => $row->start_time ? substr($row->start_time, 0, 5) : '-')
                ->editColumn('end_time', fn ($row) => $row->end_time ? substr($row->end_time, 0, 5) : '-')
                ->addColumn('effective_from_display', fn ($row) => $row->effective_from?->format('d-m-Y') ?? '-')
                ->addColumn('effective_to_display', fn ($row) => $row->effective_to?->format('d-m-Y') ?? '-')
                ->addColumn('status_badge', fn ($row) => $row->status === 'Active'
                    ? '<span class="status-green">Active</span>'
                    : '<span class="status-orange">Completed</span>')
                ->addColumn('action', fn ($row) => view('route-assignment.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status_badge', 'action'])
                ->make(true);
        }

        return view('route-assignment.index', [
            'route' => $route,
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'shiftTypes' => RouteAssignment::SHIFT_TYPES,
            'statuses' => RouteAssignment::STATUSES,
        ]);
    }

    public function create(RouteModel $route, Request $request)
    {
        $vehicles = Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no']);
        $drivers = User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $shiftTypes = RouteAssignment::SHIFT_TYPES;
        $statuses = RouteAssignment::STATUSES;

        if ($request->id) {
            $record = RouteAssignment::where('route_id', $route->id)->findOrFail($request->id);

            return response()->json([
                'html' => view('route-assignment.form', compact('route', 'record', 'vehicles', 'drivers', 'shiftTypes', 'statuses'))->render(),
                'title' => 'Update Route Assignment',
            ]);
        }

        return response()->json([
            'html' => view('route-assignment.form', compact('route', 'vehicles', 'drivers', 'shiftTypes', 'statuses'))->render(),
            'title' => 'Add Route Assignment',
        ]);
    }

    public function store(Request $request, RouteModel $route)
    {
        $validated = $this->validatedData($request);
        $assignment = $route->routeAssignments()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Route assignment created successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function update(Request $request, RouteAssignment $routeAssignment)
    {
        $routeAssignment->update($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Route assignment updated successfully.',
            'data' => $routeAssignment->fresh(),
        ]);
    }

    public function destroy(RouteAssignment $routeAssignment)
    {
        $routeAssignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route assignment deleted successfully.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'trip_id' => ['nullable', 'integer'],
            'shift_type' => ['required', Rule::in(array_keys(RouteAssignment::SHIFT_TYPES))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', Rule::in(array_keys(RouteAssignment::STATUSES))],
        ]);

        if (! User::role('Driver')->whereKey($validated['driver_id'])->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Please select a valid driver.',
            ]);
        }

        return $validated;
    }
}
