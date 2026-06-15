<?php

namespace App\Http\Controllers;

use App\Exports\DorAccountResponsibleExport;
use App\Models\DorAccountResponsible;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DorAccountResponsibleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('dor-account-responsibles.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('dor-account-responsibles.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('dor-account-responsibles.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('dor-account-responsibles.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = DorAccountResponsible::select(['id', 'code', 'name', 'is_active', 'created_at'])->latest();

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('status', fn ($row) => $row->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('dor-account-responsible.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('dor-account-responsible.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = DorAccountResponsible::findOrFail($request->id);

            return response()->json([
                'html' => view('dor-account-responsible.form', compact('record'))->render(),
                'title' => 'Update Account Responsible',
            ]);
        }

        $generatedCode = $this->code(((int) DorAccountResponsible::max('id')) + 1);

        return response()->json([
            'html' => view('dor-account-responsible.form', compact('generatedCode'))->render(),
            'title' => 'Add Account Responsible',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $record = DorAccountResponsible::create($validated + ['code' => null]);
        $record->update(['code' => $this->code($record->id)]);

        return response()->json(['success' => true, 'message' => 'Account responsible created successfully.'], 201);
    }

    public function show(DorAccountResponsible $dorAccountResponsible) {}

    public function edit(DorAccountResponsible $dorAccountResponsible) {}

    public function update(Request $request, DorAccountResponsible $dorAccountResponsible)
    {
        $dorAccountResponsible->update($this->validated($request, $dorAccountResponsible->id));

        return response()->json(['success' => true, 'message' => 'Account responsible updated successfully.']);
    }

    public function destroy(DorAccountResponsible $dorAccountResponsible)
    {
        $dorAccountResponsible->delete();

        return response()->json(['success' => true, 'message' => 'Account responsible deleted successfully.']);
    }

    public function export(Request $request)
    {
        $query = DorAccountResponsible::select('code', 'name', 'is_active', 'created_at');

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids', []));
        }

        return Excel::download(new DorAccountResponsibleExport($query), 'dor-account-responsibles.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:dor_account_responsibles,id'],
            'status' => ['required', 'boolean'],
        ]);

        DorAccountResponsible::whereKey($validated['id'])->update(['is_active' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('dor_account_responsibles', 'name')->ignore($ignoreId)],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function code(int $id): string
    {
        return 'DAR' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
