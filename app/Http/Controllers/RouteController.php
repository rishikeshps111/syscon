<?php

namespace App\Http\Controllers;

use App\Exports\RouteExport;
use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Models\Depot;
use App\Models\Route as RouteModel;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            $query = RouteModel::with(['state', 'startPoint', 'endPoint'])
                ->select([
                    'id',
                    'state_id',
                    'start_point_id',
                    'end_point_id',
                    'code',
                    'name',
                    'distance',
                    'estimated_duration',
                    'route_type',
                    'is_active',
                    'created_at',
                ])
                ->orderBy('created_at', 'desc');

            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
            }

            if (request()->filled('start_point_id')) {
                $query->where('start_point_id', request('start_point_id'));
            }

            if (request()->filled('end_point_id')) {
                $query->where('end_point_id', request('end_point_id'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('start_point', function ($row) {
                    return $row->startPoint?->name ?? '';
                })
                ->addColumn('end_point', function ($row) {
                    return $row->endPoint?->name ?? '';
                })
                ->editColumn('distance', function ($row) {
                    return $row->distance ?? '-';
                })
                ->editColumn('estimated_duration', function ($row) {
                    return $row->estimated_duration ? substr($row->estimated_duration, 0, 5) : '-';
                })
                ->addColumn('state_name', function ($row) {
                    return $row->state?->name ?? '';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('route.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);
        $depots = Depot::orderBy('name')->get(['id', 'name']);

        return view('route.index', compact('states', 'depots'));
    }

    public function create(Request $request)
    {
        $states = State::orderBy('name')->get(['id', 'name']);
        $depots = Depot::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = RouteModel::findOrFail($request->id);

            return response()->json([
                'html' => view('route.form', compact('record', 'states', 'depots'))->render(),
                'title' => 'Update Route',
            ]);
        }

        $generatedCode = generate_code('Route Module', ((int) RouteModel::max('id')) + 1, 3, 'RT');

        return response()->json([
            'html' => view('route.form', compact('generatedCode', 'states', 'depots'))->render(),
            'title' => 'Add Route',
        ]);
    }

    public function store(StoreRouteRequest $request)
    {
        $route = RouteModel::create($request->validated());
        $route->code = generate_code('Route Module', $route->id, 3, 'RT');
        $route->save();

        return response()->json([
            'success' => true,
            'message' => 'Route created successfully.',
            'data' => $route,
        ], 201);
    }

    public function show(RouteModel $route) {}

    public function edit(RouteModel $route) {}

    public function update(UpdateRouteRequest $request, RouteModel $route)
    {
        $route->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Route updated successfully.',
            'data' => $route->fresh(),
        ]);
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
        $query = RouteModel::with(['state', 'startPoint', 'endPoint'])
            ->select('state_id', 'start_point_id', 'end_point_id', 'code', 'name', 'distance', 'estimated_duration', 'route_type', 'is_active', 'created_at');

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
            'status' => ['required', 'boolean'],
        ]);

        $route = RouteModel::findOrFail($request->id);
        $route->is_active = $request->status;
        $route->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
