<?php

namespace App\Http\Controllers;

use App\Exports\RouteStopExport;
use App\Http\Requests\StoreRouteStopRequest;
use App\Http\Requests\UpdateRouteStopRequest;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RouteStopController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('routes.edit'), ['index', 'create', 'store', 'update', 'export']),
            new Middleware(PermissionMiddleware::using('routes.delete'), ['destroy']),
        ];
    }

    public function index(RouteModel $route)
    {
        $route->load(['state', 'startPoint', 'endPoint']);

        if (request()->ajax()) {
            $query = $route->stops()
                ->select(['id', 'route_id', 'name', 'expected_reach_time', 'position', 'created_at'])
                ->orderBy('position');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('expected_reach_time', function ($row) {
                    return $row->expected_reach_time ? substr($row->expected_reach_time, 0, 5) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('route-stop.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'checkbox'])
                ->make(true);
        }

        return view('route-stop.index', compact('route'));
    }

    public function create(RouteModel $route, Request $request)
    {
        if ($request->id) {
            $record = RouteStop::where('route_id', $route->id)->findOrFail($request->id);

            return response()->json([
                'html' => view('route-stop.form', compact('route', 'record'))->render(),
                'title' => 'Update Route Stop',
            ]);
        }

        $nextPosition = ((int) $route->stops()->max('position')) + 1;

        return response()->json([
            'html' => view('route-stop.form', compact('route', 'nextPosition'))->render(),
            'title' => 'Add Route Stop',
        ]);
    }

    public function store(StoreRouteStopRequest $request, RouteModel $route)
    {
        $routeStop = $route->stops()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Route stop created successfully.',
            'data' => $routeStop,
        ], 201);
    }

    public function update(UpdateRouteStopRequest $request, RouteStop $routeStop)
    {
        $routeStop->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Route stop updated successfully.',
            'data' => $routeStop->fresh(),
        ]);
    }

    public function destroy(RouteStop $routeStop)
    {
        $routeStop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Route stop deleted successfully.',
        ]);
    }

    public function export(Request $request, RouteModel $route)
    {
        $ids = $request->input('ids', []);
        $query = $route->stops()
            ->select('name', 'expected_reach_time', 'position', 'created_at')
            ->orderBy('position');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new RouteStopExport($query, $route), 'route-stops.xlsx');
    }
}
