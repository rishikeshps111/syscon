<?php

namespace App\Http\Controllers;

use App\Exports\LevelExport;
use App\Http\Requests\StoreLevelRequest;
use App\Http\Requests\UpdateLevelRequest;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class LevelController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('levels.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('levels.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('levels.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('levels.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Level::select(['id', 'code', 'name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('level.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('level.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = Level::findOrFail($request->id);

            return response()->json([
                'html' => view('level.form', compact('record'))->render(),
                'title' => 'Update Level',
            ]);
        }

        $generatedCode = generate_code('Level Module', ((int) Level::max('id')) + 1, 3, 'LVL');

        return response()->json([
            'html' => view('level.form', compact('generatedCode'))->render(),
            'title' => 'Add Level',
        ]);
    }

    public function store(StoreLevelRequest $request)
    {
        $level = Level::create($request->validated());
        $level->code = generate_code('Level Module', $level->id, 3, 'LVL');
        $level->save();

        return response()->json([
            'success' => true,
            'message' => 'Level created successfully.',
            'data' => $level,
        ], 201);
    }

    public function show(Level $level) {}

    public function edit(Level $level) {}

    public function update(UpdateLevelRequest $request, Level $level)
    {
        $level->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Level updated successfully.',
            'data' => $level->fresh(),
        ]);
    }

    public function destroy(Level $level)
    {
        $level->delete();

        return response()->json([
            'success' => true,
            'message' => 'Level deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Level::select('code', 'name', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new LevelExport($query), 'levels.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:levels,id'],
            'status' => ['required', 'boolean'],
        ]);

        $level = Level::findOrFail($request->id);
        $level->is_active = $request->status;
        $level->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
