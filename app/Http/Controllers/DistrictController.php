<?php

namespace App\Http\Controllers;

use App\Exports\DistrictExport;
use App\Http\Requests\StoreDistrictRequest;
use App\Http\Requests\UpdateDistrictRequest;
use App\Models\District;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DistrictController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('districts.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('districts.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('districts.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('districts.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = District::with('state')
                ->select(['id', 'state_id', 'code', 'name', 'is_default', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('state_id')) {
                $query->where('state_id', request('state_id'));
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
                    return view('district.partials.action', compact('row'))->render();
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M Y') : '';
                })
                ->rawColumns(['action', 'default', 'status', 'checkbox'])
                ->make(true);
        }

        $states = State::orderBy('name')->get(['id', 'name']);

        return view('district.index', compact('states'));
    }

    public function create(Request $request)
    {
        $states = State::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = District::findOrFail($request->id);

            return response()->json([
                'html' => view('district.form', compact('record', 'states'))->render(),
                'title' => 'Update District',
            ]);
        }

        $generatedCode = generate_code('District Module', ((int) District::max('id')) + 1, 3, 'DS');

        return response()->json([
            'html' => view('district.form', compact('generatedCode', 'states'))->render(),
            'title' => 'Add District',
        ]);
    }

    public function store(StoreDistrictRequest $request)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        $district = DB::transaction(function () use ($data, $makeDefault) {
            if ($makeDefault) {
                District::where('is_default', true)->update(['is_default' => false]);
            }

            $district = District::create($data);
            $district->code = generate_code('District Module', $district->id, 3, 'DS');
            $district->save();

            return $district;
        });

        return response()->json([
            'success' => true,
            'message' => 'District created successfully.',
            'data' => $district,
        ], 201);
    }

    public function show(District $district) {}

    public function edit(District $district) {}

    public function update(UpdateDistrictRequest $request, District $district)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        DB::transaction(function () use ($district, $data, $makeDefault) {
            if ($makeDefault) {
                District::where('id', '!=', $district->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $district->update($data);
        });

        return response()->json([
            'success' => true,
            'message' => 'District updated successfully.',
            'data' => $district->fresh(),
        ]);
    }

    public function destroy(District $district)
    {
        if ($this->hasRelatedRecords($district)) {
            return response()->json([
                'success' => false,
                'message' => 'This district is already used in related records and cannot be deleted.',
            ], 422);
        }

        $district->delete();

        return response()->json([
            'success' => true,
            'message' => 'District deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = District::with('state')
            ->select('state_id', 'code', 'name', 'is_default', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new DistrictExport($query), 'districts.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:districts,id'],
            'status' => ['required', 'boolean'],
        ]);

        $district = District::findOrFail($request->id);
        $district->is_active = $request->status;
        $district->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function hasRelatedRecords(District $district): bool
    {
        $relatedTables = ['locations'];
        foreach ($relatedTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->where('district_id', $district->id)->count();
                if ($count > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
