<?php

namespace App\Http\Controllers;

use App\Exports\RouteExport;
use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\District;
use App\Models\Depot;
use App\Models\Route as RouteModel;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RouteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('routes.view'), ['index', 'show', 'export', 'preview', 'previewExport']),
            new Middleware(PermissionMiddleware::using('routes.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('routes.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('routes.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = RouteModel::with(['state', 'startPoint', 'endPoint', 'activeRouteAssignments.vehicle', 'activeRouteAssignments.driver'])
                ->select([
                    'id',
                    'state_id',
                    'district_id',
                    'start_point_id',
                    'end_point_id',
                    'route_code',
                    'route_name',
                    'total_distance_km',
                    'route_type',
                    'route_category',
                    'status',
                    'created_at',
                ])
                ->orderBy('created_at', 'desc');

            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
            }

            if (request()->filled('district_id')) {
                $query->where('district_id', request('district_id'));
            }

            if (request()->filled('start_point_id')) {
                $query->where('start_point_id', request('start_point_id'));
            }

            if (request()->filled('end_point_id')) {
                $query->where('end_point_id', request('end_point_id'));
            }

            if (request()->filled('route_type')) {
                $query->where('route_type', request('route_type'));
            }

            if (request()->filled('route_category')) {
                $query->where('route_category', request('route_category'));
            }

            if (request()->filled('status')) {
                $status = request('status');
                if (in_array($status, ['0', '1'], true)) {
                    $status = $status === '1' ? 'Active' : 'Inactive';
                }

                $query->where('status', $status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('start_end', function ($row) {
                    return trim(($row->startPoint?->name ?? '-') . ' &rarr; ' . ($row->endPoint?->name ?? '-'));
                })
                ->addColumn('name', function ($row) {
                    return $row->route_name;
                })
                ->addColumn('start_point', function ($row) {
                    return $row->startPoint?->name ?? '';
                })
                ->addColumn('end_point', function ($row) {
                    return $row->endPoint?->name ?? '';
                })
                ->editColumn('total_distance_km', function ($row) {
                    return $row->total_distance_km !== null ? number_format((float) $row->total_distance_km, 2) . ' KM' : '-';
                })
                ->addColumn('assigned_vehicle', function ($row) {
                    $vehicles = $row->activeRouteAssignments
                        ->pluck('vehicle.vehicle_no')
                        ->filter()
                        ->unique()
                        ->values();

                    return $vehicles->isNotEmpty() ? $vehicles->implode('<br>') : '-';
                })
                ->addColumn('assigned_driver', function ($row) {
                    $drivers = $row->activeRouteAssignments
                        ->map(fn ($assignment) => trim(($assignment->driver?->code ? $assignment->driver->code . ' - ' : '') . ($assignment->driver?->name ?? '')))
                        ->filter()
                        ->unique()
                        ->values();

                    return $drivers->isNotEmpty() ? $drivers->implode('<br>') : '-';
                })
                ->addColumn('status', function ($row) {
                    return $row->status === 'Active'
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('route.partials.action', compact('row'))->render();
                })
                ->rawColumns(['start_end', 'assigned_vehicle', 'assigned_driver', 'action', 'status', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);
        $districts = District::orderBy('name')->get(['id', 'name', 'state_id']);
        $routeTypes = RouteModel::ROUTE_TYPES;
        $routeCategories = RouteModel::ROUTE_CATEGORIES;
        $statuses = RouteModel::STATUSES;

        return view('route.index', compact('states', 'districts', 'routeTypes', 'routeCategories', 'statuses'));
    }

    public function create()
    {
        $generatedCode = generate_code('Route Module', ((int) RouteModel::max('id')) + 1, 3, 'RT');

        return view('route.form', $this->formData() + compact('generatedCode'));
    }

    public function store(StoreRouteRequest $request)
    {
        $route = RouteModel::create($request->validated() + [
            'route_code' => null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        $route->route_code = generate_code('Route Module', $route->id, 3, 'RT');
        $route->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Route created successfully.',
                'data' => $route,
            ], 201);
        }

        return redirect()->route('routes.index')->with('success', 'Route created successfully.');
    }

    public function show(RouteModel $route) {}

    public function edit(RouteModel $route)
    {
        return view('route.form', $this->formData() + [
            'record' => $route,
        ]);
    }

    public function update(UpdateRouteRequest $request, RouteModel $route)
    {
        $route->update($request->validated() + [
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully.',
                'data' => $route->fresh(),
            ]);
        }

        return redirect()->route('routes.index')->with('success', 'Route updated successfully.');
    }

    public function destroy(RouteModel $route)
    {
        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = RouteModel::with(['state', 'district', 'startPoint', 'endPoint', 'activeRouteAssignments.vehicle', 'activeRouteAssignments.driver'])
            ->select('routes.*');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new RouteExport($query), 'routes.xlsx');
    }

    public function preview(RouteModel $route)
    {
        $route->load(['state', 'startPoint', 'endPoint', 'stops' => function ($query) {
            $query->orderBy('position');
        }]);

        return view('route.preview', compact('route'));
    }

    public function previewExport(RouteModel $route): StreamedResponse
    {
        $route->load(['startPoint', 'endPoint', 'stops' => function ($query) {
            $query->orderBy('position');
        }]);

        $filename = str($route->code ?: $route->name)
            ->slug()
            ->append('-preview.csv')
            ->toString();

        return response()->streamDownload(function () use ($route) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Place Name', 'Expected Reach Time', 'Position']);
            fputcsv($handle, ['Start', $route->startPoint?->name, '', '']);

            foreach ($route->stops as $stop) {
                fputcsv($handle, [
                    'Stop',
                    $stop->name,
                    $stop->expected_reach_time ? substr($stop->expected_reach_time, 0, 5) : '',
                    $stop->position,
                ]);
            }

            fputcsv($handle, ['End', $route->endPoint?->name, '', '']);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:routes,id'],
            'status' => ['required', Rule::in(array_keys(RouteModel::STATUSES))],
        ]);

        $route = RouteModel::findOrFail($request->id);
        $route->status = $request->status;
        $route->updated_by = auth()->id();
        $route->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function formData(): array
    {
        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'name', 'state_id']),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name', 'state_id', 'district_id']),
            'routeTypes' => RouteModel::ROUTE_TYPES,
            'routeCategories' => RouteModel::ROUTE_CATEGORIES,
            'statuses' => RouteModel::STATUSES,
        ];
    }
}
