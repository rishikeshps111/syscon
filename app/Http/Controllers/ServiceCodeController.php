<?php

namespace App\Http\Controllers;

use App\Models\ServiceCode;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceCodeController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(ServiceCode::query()->latest())->addIndexColumn()->editColumn('description', fn ($r) => $r->description ?: '-')->addColumn('action', fn ($r) => view('service-code.partials-action', ['row' => $r])->render())->addColumn('status', fn ($r) => $r->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')->rawColumns(['status', 'action'])->make(true);
        }

        return view('service-code.index');
    }

    public function create(Request $request)
    {
        $record = $request->filled('id') ? ServiceCode::findOrFail($request->integer('id')) : null;

        return response()->json(['html' => view('service-code.form', compact('record'))->render(), 'title' => $record ? 'Update Service Code' : 'Add Service Code']);
    }

    public function store(Request $request)
    {
        $record = ServiceCode::create($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Service code created successfully.', 'data' => $record], 201);
    }

    public function update(Request $request, ServiceCode $serviceCode)
    {
        $serviceCode->update($this->validated($request, $serviceCode->id));

        return response()->json(['success' => true, 'message' => 'Service code updated successfully.', 'data' => $serviceCode->fresh()]);
    }

    public function destroy(ServiceCode $serviceCode)
    {
        $serviceCode->delete();

        return response()->json(['success' => true, 'message' => 'Service code deleted successfully.']);
    }

    public function status(Request $request)
    {
        $data = $request->validate(['id' => 'required|exists:service_codes,id', 'status' => 'required|boolean']);
        ServiceCode::whereKey($data['id'])->update(['is_active' => $data['status']]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'is_active' => ['required', 'boolean']]);
    }
}
