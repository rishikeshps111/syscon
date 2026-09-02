<?php

namespace App\Http\Controllers;

use App\Exports\DriverChangeReasonExport;
use App\Models\DriverChangeReason;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DriverChangeReasonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('driver-change-reasons.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('driver-change-reasons.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('driver-change-reasons.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('driver-change-reasons.delete'), ['destroy']),
            new Middleware(PermissionMiddleware::using('driver-change-reasons.export'), ['export']),
            new Middleware(PermissionMiddleware::using('driver-change-reasons.status'), ['status']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = DriverChangeReason::select(['id', 'code', 'name', 'is_active', 'created_at'])->latest();

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('status', fn ($row) => $row->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('driver-change-reason.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('driver-change-reason.index');
    }

    public function create(Request $request)
    {
        $record = $request->filled('id') ? DriverChangeReason::findOrFail($request->integer('id')) : null;
        $generatedCode = $record ? null : $this->code(((int) DriverChangeReason::max('id')) + 1);

        return response()->json([
            'html' => view('driver-change-reason.form', compact('record', 'generatedCode'))->render(),
            'title' => $record ? 'Update Reason for Driver Change' : 'Add Reason for Driver Change',
        ]);
    }

    public function store(Request $request)
    {
        $record = DriverChangeReason::create($this->validated($request) + ['code' => null]);
        $record->update(['code' => $this->code($record->id)]);

        return response()->json(['success' => true, 'message' => 'Driver change reason created successfully.'], 201);
    }

    public function show(DriverChangeReason $driverChangeReason) {}

    public function edit(DriverChangeReason $driverChangeReason) {}

    public function update(Request $request, DriverChangeReason $driverChangeReason)
    {
        $driverChangeReason->update($this->validated($request, $driverChangeReason->id));

        return response()->json(['success' => true, 'message' => 'Driver change reason updated successfully.']);
    }

    public function destroy(DriverChangeReason $driverChangeReason)
    {
        $driverChangeReason->delete();

        return response()->json(['success' => true, 'message' => 'Driver change reason deleted successfully.']);
    }

    public function export(Request $request)
    {
        $query = DriverChangeReason::select('code', 'name', 'is_active', 'created_at');

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids', []));
        }

        return Excel::download(new DriverChangeReasonExport($query), 'driver-change-reasons.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:driver_change_reasons,id'],
            'status' => ['required', 'boolean'],
        ]);

        DriverChangeReason::whereKey($validated['id'])->update(['is_active' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('driver_change_reasons', 'name')->ignore($ignoreId)],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function code(int $id): string
    {
        return 'DCR' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
