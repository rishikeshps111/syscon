<?php

namespace App\Http\Controllers;

use App\Exports\TripSetupExport;
use App\Http\Requests\StoreTripSetupRequest;
use App\Http\Requests\UpdateTripSetupRequest;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\TripSetup;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class TripSetupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trip-setups.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('trip-setups.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('trip-setups.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('trip-setups.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = TripSetup::with(['serviceType', 'route'])
                ->select(['id', 'service_type_id', 'route_id', 'code', 'schedule_type', 'start_time', 'end_time', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('service_type_name', function ($row) {
                    return $row->serviceType?->name ?? '';
                })
                ->addColumn('route_name', function ($row) {
                    return $row->route?->route_name ?? '';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('trip-setup.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('trip-setup.index');
    }

    public function create(Request $request)
    {
        $serviceTypes = ServiceType::orderBy('name')->get(['id', 'name']);
        $routes = RouteModel::orderBy('route_name')->get(['id', 'route_name']);

        if ($request->id) {
            $record = TripSetup::findOrFail($request->id);

            return response()->json([
                'html' => view('trip-setup.form', compact('record', 'serviceTypes', 'routes'))->render(),
                'title' => 'Update Trip Setup',
            ]);
        }

        $generatedCode = generate_code('Trip Setup Module', ((int) TripSetup::max('id')) + 1, 3, 'TSU');

        return response()->json([
            'html' => view('trip-setup.form', compact('generatedCode', 'serviceTypes', 'routes'))->render(),
            'title' => 'Add Trip Setup',
        ]);
    }

    public function store(StoreTripSetupRequest $request)
    {
        $tripSetup = TripSetup::create($request->validated());
        $tripSetup->code = generate_code('Trip Setup Module', $tripSetup->id, 3, 'TSU');
        $tripSetup->save();

        return response()->json([
            'success' => true,
            'message' => 'Trip setup created successfully.',
            'data' => $tripSetup,
        ], 201);
    }

    public function show(TripSetup $tripSetup) {}

    public function edit(TripSetup $tripSetup) {}

    public function update(UpdateTripSetupRequest $request, TripSetup $tripSetup)
    {
        $tripSetup->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Trip setup updated successfully.',
            'data' => $tripSetup->fresh(),
        ]);
    }

    public function destroy(TripSetup $tripSetup)
    {
        $tripSetup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip setup deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = TripSetup::with(['serviceType', 'route'])
            ->select('service_type_id', 'route_id', 'code', 'schedule_type', 'start_time', 'end_time', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new TripSetupExport($query), 'trip-setups.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:trip_setups,id'],
            'status' => ['required', 'boolean'],
        ]);

        $tripSetup = TripSetup::findOrFail($request->id);
        $tripSetup->is_active = $request->status;
        $tripSetup->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
