<?php

namespace App\Http\Controllers;

use App\Exports\StateExport;
use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class StateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('states.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('states.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('states.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('states.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = State::select(['id', 'code', 'name', 'is_default', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
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
                    return view('state.partials.action', compact('row'))->render();
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d M Y') : '';
                })
                ->rawColumns(['action', 'default', 'status', 'checkbox'])
                ->make(true);
        }

        return view('state.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = State::findOrFail($request->id);

            return response()->json([
                'html' => view('state.form', compact('record'))->render(),
                'title' => 'Update State',
            ]);
        }

        $generatedCode = generate_code('State Module', ((int) State::max('id')) + 1, 3, 'ST');

        return response()->json([
            'html' => view('state.form', compact('generatedCode'))->render(),
            'title' => 'Add State',
        ]);
    }

    public function store(StoreStateRequest $request)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        $state = DB::transaction(function () use ($data, $makeDefault) {
            if ($makeDefault) {
                State::where('is_default', true)->update(['is_default' => false]);
            }

            $state = State::create($data);
            $state->code = generate_code('State Module', $state->id, 3, 'ST');
            $state->save();

            return $state;
        });

        return response()->json([
            'success' => true,
            'message' => 'State created successfully.',
            'data' => $state,
        ], 201);
    }

    public function show(State $state) {}

    public function edit(State $state) {}

    public function update(UpdateStateRequest $request, State $state)
    {
        $data = $request->validated();
        $makeDefault = (bool) $data['is_default'];

        DB::transaction(function () use ($state, $data, $makeDefault) {
            if ($makeDefault) {
                State::where('id', '!=', $state->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $state->update($data);
        });

        return response()->json([
            'success' => true,
            'message' => 'State updated successfully.',
            'data' => $state->fresh(),
        ]);
    }

    public function destroy(State $state)
    {
        if ($this->hasRelatedRecords($state)) {
            return response()->json([
                'success' => false,
                'message' => 'This state is already used in related records and cannot be deleted.',
            ], 422);
        }

        $state->delete();

        return response()->json([
            'success' => true,
            'message' => 'State deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = State::select('code', 'name', 'is_default', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new StateExport($query), 'states.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:states,id'],
            'status' => ['required', 'boolean'],
        ]);

        $state = State::findOrFail($request->id);
        $state->is_active = $request->status;
        $state->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function hasRelatedRecords(State $state): bool
    {
        $relatedTables = ['districts'];
        foreach ($relatedTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->where('state_id', $state->id)->count();
                if ($count > 0) {
                    return true;
                }
            }
        }
        return false;
    }
}
