<?php

namespace App\Http\Controllers;

use App\Exports\RouteStopExport;
use App\Http\Requests\StoreRouteStopRequest;
use App\Http\Requests\UpdateRouteStopRequest;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RouteStopController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('routes.edit'), ['index', 'create', 'store', 'update', 'reorder', 'export']),
            new Middleware(PermissionMiddleware::using('routes.delete'), ['destroy']),
        ];
    }

    public function index(RouteModel $route)
    {
        $route->load(['state', 'startPoint', 'endPoint']);

        if (request()->ajax()) {
            $query = $route->stops()
                ->with('location')
                ->select(['id', 'route_id', 'location_id', 'name', 'position', 'created_at'])
                ->orderBy('position');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    $name = $row->location?->name ?? $row->name;
                    $shortName = $row->location?->short_name;

                    return $shortName ? $name . ' (' . $shortName . ')' : $name;
                })
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
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
        $locations = Location::where('is_active', true)
            ->where('state_id', $route->state_id)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        if ($request->id) {
            $record = RouteStop::where('route_id', $route->id)->findOrFail($request->id);

            return response()->json([
                'html' => view('route-stop.form', compact('route', 'record', 'locations'))->render(),
                'title' => 'Update Route Stop',
            ]);
        }

        return response()->json([
            'html' => view('route-stop.form', compact('route', 'locations'))->render(),
            'title' => 'Add Route Stop',
        ]);
    }

    public function store(StoreRouteStopRequest $request, RouteModel $route)
    {
        $routeStop = $route->stops()->create($request->validated() + [
            'position' => ((int) $route->stops()->max('position')) + 1,
        ]);

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

    public function reorder(Request $request, RouteModel $route)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:route_stops,id'],
        ]);

        $stops = $route->stops()->whereIn('id', $validated['ids'])->get(['id', 'position']);
        abort_unless($stops->count() === count($validated['ids']), 422, 'Invalid route stop order.');
        $positions = $stops->pluck('position')->sort()->values();

        DB::transaction(function () use ($validated, $positions) {
            foreach ($validated['ids'] as $index => $id) {
                RouteStop::whereKey($id)->update(['position' => $positions[$index]]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Route stop order updated successfully.']);
    }

    public function export(Request $request, RouteModel $route)
    {
        $ids = $request->input('ids', []);
        $query = $route->stops()
            ->with('location')
            ->select('location_id', 'name', 'position', 'created_at')
            ->orderBy('position');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new RouteStopExport($query, $route), 'route-stops.xlsx');
    }
}
