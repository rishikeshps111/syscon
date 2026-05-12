<?php

namespace App\Http\Controllers;

use App\Models\Prefix;
use App\Http\Requests\StorePrefixRequest;
use App\Http\Requests\UpdatePrefixRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PrefixExport;

class PrefixController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('prefixes.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('prefixes.create'),  ['create', 'store']),
            new Middleware(PermissionMiddleware::using('prefixes.edit'),  ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('prefixes.delete'), ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $query = Prefix::select(['id', 'prefix', 'module', 'is_active', 'created_at'])->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('status', function ($row) {
                    $status = $row->is_active ? 'Active' : 'Inactive';
                    $class = match ($status) {
                        'Active' => 'status-green',
                        'Inactive' => 'status-red',
                        default => 'status-orange',
                    };
                    return '<span class="' . $class . '">' . $status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('prefix.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('prefix.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->id) {
            $record = Prefix::findOrFail($request->id);
            return response()->json([
                'html' => view('prefix.form', compact('record'))->render(),
                'title' => 'Update Prefix'
            ]);
        }
        return response()->json([
            'html' => view('prefix.form')->render(),
            'title' => 'Add Prefix'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePrefixRequest $request)
    {
        $data = $request->validated();
        $prefix = Prefix::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Prefix created successfully.',
            'data' => $prefix,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Prefix $prefix) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prefix $prefix) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePrefixRequest $request, Prefix $prefix)
    {
        $data = $request->validated();
        $prefix->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Prefix updated successfully.',
            'data' => $prefix,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prefix $prefix)
    {
        $prefix->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prefix deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Prefix::select('prefix', 'module', 'is_active', 'created_at');
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }
        return Excel::download(new PrefixExport($query), 'prefixes.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:prefixes,id',
            'status' => 'required|boolean',
        ]);
        $prefix = Prefix::findOrFail($request->id);
        $prefix->is_active = $request->status;
        $prefix->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
