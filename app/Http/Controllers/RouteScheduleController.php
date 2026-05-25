<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
use App\Models\RouteSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RouteScheduleController extends Controller implements HasMiddleware
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
            $query = $route->schedules()
                ->with(['vehicle', 'driver'])
                ->latest('schedule_date');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('schedule_date_display', fn ($row) => $row->schedule_date?->format('d-m-Y') ?? '-')
                ->editColumn('planned_start_time', fn ($row) => $row->planned_start_time ? substr($row->planned_start_time, 0, 5) : '-')
                ->editColumn('planned_end_time', fn ($row) => $row->planned_end_time ? substr($row->planned_end_time, 0, 5) : '-')
                ->addColumn('vehicle_name', fn ($row) => $row->vehicle?->vehicle_no ?? '-')
                ->addColumn('driver_name', fn ($row) => trim(($row->driver?->code ? $row->driver->code . ' - ' : '') . ($row->driver?->name ?? '-')))
                ->addColumn('status_badge', fn ($row) => match ($row->status) {
                    'Running' => '<span class="status-orange">Running</span>',
                    'Completed' => '<span class="status-green">Completed</span>',
                    'Cancelled' => '<span class="status-red">Cancelled</span>',
                    default => '<span class="status-blue">Planned</span>',
                })
                ->addColumn('action', fn ($row) => view('route-schedule.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status_badge', 'action'])
                ->make(true);
        }

        return view('route-schedule.index', [
            'route' => $route,
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'statuses' => RouteSchedule::STATUSES,
        ]);
    }

    public function create(RouteModel $route, Request $request)
    {
        $vehicles = Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no']);
        $drivers = User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $statuses = RouteSchedule::STATUSES;

        if ($request->id) {
            $record = RouteSchedule::where('route_id', $route->id)->findOrFail($request->id);

            return response()->json([
                'html' => view('route-schedule.form', compact('route', 'record', 'vehicles', 'drivers', 'statuses'))->render(),
                'title' => 'Update Route Schedule',
            ]);
        }

        return response()->json([
            'html' => view('route-schedule.form', compact('route', 'vehicles', 'drivers', 'statuses'))->render(),
            'title' => 'Add Route Schedule',
        ]);
    }

    public function store(Request $request, RouteModel $route)
    {
        $schedule = $route->schedules()->create($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Route schedule created successfully.',
            'data' => $schedule,
        ], 201);
    }

    public function update(Request $request, RouteSchedule $routeSchedule)
    {
        $routeSchedule->update($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Route schedule updated successfully.',
            'data' => $routeSchedule->fresh(),
        ]);
    }

    public function destroy(RouteSchedule $routeSchedule)
    {
        $routeSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route schedule deleted successfully.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'schedule_date' => ['required', 'date'],
            'planned_start_time' => ['required', 'date_format:H:i'],
            'planned_end_time' => ['required', 'date_format:H:i', 'after:planned_start_time'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(RouteSchedule::STATUSES))],
        ]);

        if (! User::role('Driver')->whereKey($validated['driver_id'])->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Please select a valid driver.',
            ]);
        }

        return $validated;
    }
}
