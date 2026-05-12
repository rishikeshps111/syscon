<?php

namespace App\Http\Controllers;

use App\Exports\DepotExport;
use App\Http\Requests\StoreDepotRequest;
use App\Http\Requests\UpdateDepotRequest;
use App\Models\Depot;
use App\Models\Location;
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
            new Middleware(PermissionMiddleware::using('depots.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('depots.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('depots.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('depots.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Depot::with('location')
                ->select(['id', 'location_id', 'code', 'name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
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

        return view('depot.index');
    }

    public function create(Request $request)
    {
        $locations = Location::orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = Depot::findOrFail($request->id);

            return response()->json([
                'html' => view('depot.form', compact('record', 'locations'))->render(),
                'title' => 'Update Depot',
            ]);
        }

        $generatedCode = generate_code('Depot Module', ((int) Depot::max('id')) + 1, 3, 'DPM');

        return response()->json([
            'html' => view('depot.form', compact('generatedCode', 'locations'))->render(),
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
        $query = Depot::with('location')
            ->select('location_id', 'code', 'name', 'is_active', 'created_at');

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
}
