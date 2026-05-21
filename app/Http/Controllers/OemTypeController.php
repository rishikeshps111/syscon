<?php

namespace App\Http\Controllers;

use App\Exports\OemTypeExport;
use App\Http\Requests\StoreOemTypeRequest;
use App\Http\Requests\UpdateOemTypeRequest;
use App\Models\OemType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oem-types.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('oem-types.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('oem-types.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('oem-types.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = OemType::select(['id', 'name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('status', fn ($row) => $row->is_active
                    ? '<span class="status-green">Active</span>'
                    : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('oem-type.partials.action', compact('row'))->render())
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('oem-type.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = OemType::findOrFail($request->id);

            return response()->json([
                'html' => view('oem-type.form', compact('record'))->render(),
                'title' => 'Update OEM Type',
            ]);
        }

        return response()->json([
            'html' => view('oem-type.form')->render(),
            'title' => 'Add OEM Type',
        ]);
    }

    public function store(StoreOemTypeRequest $request)
    {
        $oemType = OemType::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'OEM type created successfully.',
            'data' => $oemType,
        ], 201);
    }

    public function show(OemType $oemType) {}

    public function edit(OemType $oemType) {}

    public function update(UpdateOemTypeRequest $request, OemType $oemType)
    {
        $oemType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'OEM type updated successfully.',
            'data' => $oemType->fresh(),
        ]);
    }

    public function destroy(OemType $oemType)
    {
        $oemType->delete();

        return response()->json([
            'success' => true,
            'message' => 'OEM type deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = OemType::select('name', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new OemTypeExport($query), 'oem-types.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:oem_types,id'],
            'status' => ['required', 'boolean'],
        ]);

        $oemType = OemType::findOrFail($request->id);
        $oemType->is_active = $request->status;
        $oemType->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
