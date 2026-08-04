<?php

namespace App\Http\Controllers;

use App\Exports\TripNatureExport;
use App\Models\TripNature;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class TripNatureController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trip-natures.view'), ['index', 'show']),
            new Middleware(PermissionMiddleware::using('trip-natures.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('trip-natures.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('trip-natures.delete'), ['destroy']),
            new Middleware(PermissionMiddleware::using('trip-natures.export'), ['export']),
            new Middleware(PermissionMiddleware::using('trip-natures.status'), ['status']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = TripNature::select('id', 'title', 'description', 'is_active', 'created_at')->latest();

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->editColumn('description', fn ($row) => $row->description ?: '-')
                ->addColumn('status', fn ($row) => $row->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('trip-nature.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('trip-nature.index');
    }

    public function create(Request $request)
    {
        $record = $request->filled('id') ? TripNature::findOrFail($request->integer('id')) : null;

        return response()->json([
            'html' => view('trip-nature.form', compact('record'))->render(),
            'title' => $record ? 'Update Trip Nature' : 'Add Trip Nature',
        ]);
    }

    public function store(Request $request)
    {
        $record = TripNature::create($this->validated($request));

        return response()->json(['success' => true, 'message' => 'Trip nature created successfully.', 'data' => $record], 201);
    }

    public function show(TripNature $tripNature) {}

    public function edit(TripNature $tripNature) {}

    public function update(Request $request, TripNature $tripNature)
    {
        $tripNature->update($this->validated($request, $tripNature->id));

        return response()->json(['success' => true, 'message' => 'Trip nature updated successfully.', 'data' => $tripNature->fresh()]);
    }

    public function destroy(TripNature $tripNature)
    {
        $tripNature->delete();

        return response()->json(['success' => true, 'message' => 'Trip nature deleted successfully.']);
    }

    public function export(Request $request)
    {
        $query = TripNature::select('title', 'description', 'is_active', 'created_at');
        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids', []));
        }

        return Excel::download(new TripNatureExport($query), 'trip-natures.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:trip_natures,id'],
            'status' => ['required', 'boolean'],
        ]);
        TripNature::whereKey($validated['id'])->update(['is_active' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('trip_natures', 'title')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
