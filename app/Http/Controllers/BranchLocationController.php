<?php

namespace App\Http\Controllers;

use App\Exports\BranchLocationExport;
use App\Http\Requests\StoreBranchLocationRequest;
use App\Http\Requests\UpdateBranchLocationRequest;
use App\Models\BranchLocation;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class BranchLocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('branch-locations.view'), ['index', 'show', 'export', 'districtsByState', 'locationsByDistrict']),
            new Middleware(PermissionMiddleware::using('branch-locations.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('branch-locations.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('branch-locations.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = BranchLocation::with(['state', 'district', 'location'])
                ->select(['id', 'state_id', 'district_id', 'location_id', 'code', 'name', 'remarks', 'status', 'created_at'])
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

            if (request()->filled('status') && in_array(request('status'), ['active', 'inactive', 'suspended'], true)) {
                $query->where('status', request('status'));
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
                ->addColumn('status_badge', function ($row) {
                    return match ($row->status) {
                        'active' => '<span class="status-green">Active</span>',
                        'suspended' => '<span class="status-red">Suspended</span>',
                        default => '<span class="status-red">Inactive</span>',
                    };
                })
                ->addColumn('action', function ($row) {
                    return view('branch-location.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status_badge', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);

        return view('branch-location.index', compact('states'));
    }

    public function create(Request $request)
    {
        $states = State::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = BranchLocation::findOrFail($request->id);
            $districts = District::where('state_id', $record->state_id)->orderBy('name')->get(['id', 'name']);
            $locations = Location::where('state_id', $record->state_id)
                ->where('district_id', $record->district_id)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'html' => view('branch-location.form', compact('record', 'states', 'districts', 'locations'))->render(),
                'title' => 'Update Branch Location',
            ]);
        }

        $districts = collect();
        $locations = collect();
        $generatedCode = generate_code('Branch Location Module', ((int) BranchLocation::max('id')) + 1, 3, 'BL');

        return response()->json([
            'html' => view('branch-location.form', compact('generatedCode', 'states', 'districts', 'locations'))->render(),
            'title' => 'Add Branch Location',
        ]);
    }

    public function store(StoreBranchLocationRequest $request)
    {
        $branchLocation = BranchLocation::create($request->validated());
        $branchLocation->code = generate_code('Branch Location Module', $branchLocation->id, 3, 'BL');
        $branchLocation->save();

        return response()->json([
            'success' => true,
            'message' => 'Branch location created successfully.',
            'data' => $branchLocation,
        ], 201);
    }

    public function show(BranchLocation $branchLocation) {}

    public function edit(BranchLocation $branchLocation) {}

    public function update(UpdateBranchLocationRequest $request, BranchLocation $branchLocation)
    {
        $branchLocation->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Branch location updated successfully.',
            'data' => $branchLocation->fresh(),
        ]);
    }

    public function destroy(BranchLocation $branchLocation)
    {
        $branchLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch location deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = BranchLocation::with(['state', 'district', 'location'])
            ->select('state_id', 'district_id', 'location_id', 'code', 'name', 'remarks', 'status');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new BranchLocationExport($query), 'branch-locations.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:branch_locations,id'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $branchLocation = BranchLocation::findOrFail($request->id);
        $branchLocation->status = $request->status;
        $branchLocation->save();

        return response()->json([
            'success' => true,
            'message' => 'Branch location status updated successfully.',
        ]);
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
