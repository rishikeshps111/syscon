<?php

namespace App\Http\Controllers;

use App\Exports\DepotExport;
use App\Http\Requests\StoreDepotRequest;
use App\Http\Requests\UpdateDepotRequest;
use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DepotController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('depots.view'), ['index', 'show', 'export', 'districtsByState', 'locationsByDistrict']),
            new Middleware(PermissionMiddleware::using('depots.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('depots.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('depots.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Depot::with(['state', 'district', 'location'])
                ->select(['id', 'state_id', 'district_id', 'location_id', 'code', 'name', 'short_name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
            }

            if (request()->filled('district_id')) {
                $query->where('district_id', request('district_id'));
            }

            if (request()->filled('location_id')) {
                $query->where('location_id', request('location_id'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('state_name', function ($row) {
                    return $row->state?->name ?? '';
                })
                ->addColumn('district_name', function ($row) {
                    return $row->district?->name ?? '';
                })
                ->addColumn('location_name', function ($row) {
                    return $row->location?->name ?? '';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('depot.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);

        return view('depot.index', compact('states'));
    }

    public function create(Request $request)
    {
        $states = State::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = Depot::with('location')->findOrFail($request->id);
            $stateId = $record->state_id ?: $record->location?->state_id;
            $districtId = $record->district_id ?: $record->location?->district_id;
            $record->setAttribute('state_id', $stateId);
            $record->setAttribute('district_id', $districtId);
            $districts = $stateId
                ? District::where('state_id', $stateId)->orderBy('name')->get(['id', 'name'])
                : collect();
            $locations = $stateId && $districtId
                ? Location::where('state_id', $stateId)->where('district_id', $districtId)->orderBy('name')->get(['id', 'name'])
                : collect();

            return response()->json([
                'html' => view('depot.form', compact('record', 'states', 'districts', 'locations'))->render(),
                'title' => 'Update Depot',
            ]);
        }

        $districts = collect();
        $locations = collect();
        $generatedCode = generate_code('Depot Module', ((int) Depot::max('id')) + 1, 3, 'DPM');

        return response()->json([
            'html' => view('depot.form', compact('generatedCode', 'states', 'districts', 'locations'))->render(),
            'title' => 'Add Depot',
        ]);
    }

    public function store(StoreDepotRequest $request)
    {
        $depot = Depot::create($request->validated());
        $depot->code = generate_code('Depot Module', $depot->id, 3, 'DPM');
        $depot->save();

        return response()->json([
            'success' => true,
            'message' => 'Depot created successfully.',
            'data' => $depot,
        ], 201);
    }

    public function show(Depot $depot) {}

    public function edit(Depot $depot) {}

    public function update(UpdateDepotRequest $request, Depot $depot)
    {
        $depot->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Depot updated successfully.',
            'data' => $depot->fresh(),
        ]);
    }

    public function destroy(Depot $depot)
    {
        $depot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Depot deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Depot::with(['state', 'district', 'location'])
            ->select('state_id', 'district_id', 'location_id', 'code', 'name', 'short_name', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new DepotExport($query), 'depots.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:depots,id'],
            'status' => ['required', 'boolean'],
        ]);

        $depot = Depot::findOrFail($request->id);
        $depot->is_active = $request->status;
        $depot->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function districtsByState(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
        ]);

        if (! $request->filled('state_id')) {
            return response()->json([]);
        }

        return response()->json(
            District::where('state_id', $request->state_id)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function locationsByDistrict(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ]);

        if (! $request->filled('state_id') || ! $request->filled('district_id')) {
            return response()->json([]);
        }

        return response()->json(
            Location::where('state_id', $request->state_id)
                ->where('district_id', $request->district_id)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }
}
