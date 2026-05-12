<?php

namespace App\Http\Controllers;

use App\Exports\ServiceTypeExport;
use App\Http\Requests\StoreServiceTypeRequest;
use App\Http\Requests\UpdateServiceTypeRequest;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ServiceTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('service-types.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('service-types.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('service-types.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('service-types.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = ServiceType::select(['id', 'code', 'name', 'description', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('description', function ($row) {
                    return $row->description ?: '-';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('service-type.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('service-type.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = ServiceType::findOrFail($request->id);

            return response()->json([
                'html' => view('service-type.form', compact('record'))->render(),
                'title' => 'Update Service Type',
            ]);
        }

        $generatedCode = generate_code('Service Type Module', ((int) ServiceType::max('id')) + 1, 3, 'SRT');

        return response()->json([
            'html' => view('service-type.form', compact('generatedCode'))->render(),
            'title' => 'Add Service Type',
        ]);
    }

    public function store(StoreServiceTypeRequest $request)
    {
        $data = $request->validated();
        $serviceType = ServiceType::create($data);
        $serviceType->code = generate_code('Service Type Module', $serviceType->id, 3, 'SRT');
        $serviceType->save();

        return response()->json([
            'success' => true,
            'message' => 'Service type created successfully.',
            'data' => $serviceType,
        ], 201);
    }

    public function show(ServiceType $serviceType) {}

    public function edit(ServiceType $serviceType) {}

    public function update(UpdateServiceTypeRequest $request, ServiceType $serviceType)
    {
        $serviceType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service type updated successfully.',
            'data' => $serviceType->fresh(),
        ]);
    }

    public function destroy(ServiceType $serviceType)
    {
        $serviceType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service type deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = ServiceType::select('code', 'name', 'description', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new ServiceTypeExport($query), 'service-types.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:service_types,id'],
            'status' => ['required', 'boolean'],
        ]);

        $serviceType = ServiceType::findOrFail($request->id);
        $serviceType->is_active = $request->status;
        $serviceType->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
