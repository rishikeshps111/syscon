<?php

namespace App\Http\Controllers;

use App\Exports\LocationExport;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('locations.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('locations.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('locations.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('locations.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Location::with(['state', 'district'])
                ->select(['id', 'state_id', 'district_id', 'code', 'name', 'short_name', 'pincode', 'is_default', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
            }

            if (request()->filled('district_id')) {
                $query->where('district_id', request('district_id'));
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
                ->addColumn('default', function ($row) {
                    return $row->is_default
                        ? '<span class="status-green">Yes</span>'
                        : '<span class="status-red">No</span>';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('location.partials.action', compact('row'))->render();
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M Y') : '';
                })
                ->rawColumns(['action', 'default', 'status', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);

        return view('location.index', compact('states'));
    }

    public function create(Request $request)
    {
        $states = State::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = Location::findOrFail($request->id);
            $districts = District::where('state_id', $record->state_id)->orderBy('name')->get(['id', 'name']);

            return response()->json([
                'html' => view('location.form', compact('record', 'states', 'districts'))->render(),
                'title' => 'Update Location',
            ]);
        }

        $districts = collect();
        $generatedCode = generate_code('Location Module', ((int) Location::max('id')) + 1, 3, 'LOC');

        return response()->json([
            'html' => view('location.form', compact('generatedCode', 'states', 'districts'))->render(),
            'title' => 'Add Location',
        ]);
    }

    public function store(StoreLocationRequest $request)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        $location = DB::transaction(function () use ($data, $makeDefault) {
            if ($makeDefault) {
                Location::where('is_default', true)->update(['is_default' => false]);
            }

            $location = Location::create($data);
            $location->code = generate_code('Location Module', $location->id, 3, 'LOC');
            $location->save();

            return $location;
        });

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully.',
            'data' => $location,
        ], 201);
    }

    public function show(Location $location) {}

    public function edit(Location $location) {}

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        DB::transaction(function () use ($location, $data, $makeDefault) {
            if ($makeDefault) {
                Location::where('id', '!=', $location->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $location->update($data);
        });

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => $location->fresh(),
        ]);
    }

    public function destroy(Location $location)
    {
        if ($this->hasRelatedRecords($location)) {
            return response()->json([
                'success' => false,
                'message' => 'This location is already used in related records and cannot be deleted.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Location::with(['state', 'district'])
            ->select('state_id', 'district_id', 'code', 'name', 'short_name', 'pincode', 'is_default', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new LocationExport($query), 'locations.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:locations,id'],
            'status' => ['required', 'boolean'],
        ]);

        $location = Location::findOrFail($request->id);
        $location->is_active = $request->status;
        $location->save();

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

    private function hasRelatedRecords(Location $location): bool
    {
        // foreach (Schema::getTableListing() as $tableName) {
        //     if ($tableName === 'locations' || ! Schema::hasColumn($tableName, 'location_id')) {
        //         continue;
        //     }

        //     if (DB::table($tableName)->where('location_id', $location->id)->exists()) {
        //         return true;
        //     }
        // }

        return false;
    }
}
