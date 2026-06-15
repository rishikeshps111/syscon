<?php

namespace App\Http\Controllers;

use App\Exports\DorKilometerLossReasonExport;
use App\Models\DorAccountResponsible;
use App\Models\DorKilometerLossReason;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DorKilometerLossReasonController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('dor-kilometer-loss-reasons.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('dor-kilometer-loss-reasons.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('dor-kilometer-loss-reasons.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('dor-kilometer-loss-reasons.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = DorKilometerLossReason::with('accountResponsible')
                ->select(['id', 'dor_account_responsible_id', 'code', 'name', 'is_active', 'created_at'])
                ->latest();

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            if (request()->filled('dor_account_responsible_id')) {
                $query->where('dor_account_responsible_id', request('dor_account_responsible_id'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('account_responsible', fn ($row) => $row->accountResponsible?->name ?: '-')
                ->addColumn('status', fn ($row) => $row->is_active ? '<span class="status-green">Active</span>' : '<span class="status-red">Inactive</span>')
                ->addColumn('action', fn ($row) => view('dor-kilometer-loss-reason.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('dor-kilometer-loss-reason.index', [
            'accounts' => DorAccountResponsible::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        $accounts = DorAccountResponsible::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = DorKilometerLossReason::findOrFail($request->id);

            return response()->json([
                'html' => view('dor-kilometer-loss-reason.form', compact('record', 'accounts'))->render(),
                'title' => 'Update Reason for Kilometer Loss',
            ]);
        }

        $generatedCode = $this->code(((int) DorKilometerLossReason::max('id')) + 1);

        return response()->json([
            'html' => view('dor-kilometer-loss-reason.form', compact('generatedCode', 'accounts'))->render(),
            'title' => 'Add Reason for Kilometer Loss',
        ]);
    }

    public function store(Request $request)
    {
        $record = DorKilometerLossReason::create($this->validated($request) + ['code' => null]);
        $record->update(['code' => $this->code($record->id)]);

        return response()->json(['success' => true, 'message' => 'Reason for kilometer loss created successfully.'], 201);
    }

    public function show(DorKilometerLossReason $dorKilometerLossReason) {}

    public function edit(DorKilometerLossReason $dorKilometerLossReason) {}

    public function update(Request $request, DorKilometerLossReason $dorKilometerLossReason)
    {
        $dorKilometerLossReason->update($this->validated($request, $dorKilometerLossReason->id));

        return response()->json(['success' => true, 'message' => 'Reason for kilometer loss updated successfully.']);
    }

    public function destroy(DorKilometerLossReason $dorKilometerLossReason)
    {
        $dorKilometerLossReason->delete();

        return response()->json(['success' => true, 'message' => 'Reason for kilometer loss deleted successfully.']);
    }

    public function export(Request $request)
    {
        $query = DorKilometerLossReason::with('accountResponsible')->select('*');

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids', []));
        }

        return Excel::download(new DorKilometerLossReasonExport($query), 'dor-kilometer-loss-reasons.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:dor_kilometer_loss_reasons,id'],
            'status' => ['required', 'boolean'],
        ]);

        DorKilometerLossReason::whereKey($validated['id'])->update(['is_active' => $validated['status']]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'dor_account_responsible_id' => ['required', 'integer', 'exists:dor_account_responsibles,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dor_kilometer_loss_reasons', 'name')
                    ->where(fn ($query) => $query->where('dor_account_responsible_id', $request->dor_account_responsible_id))
                    ->ignore($ignoreId),
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function code(int $id): string
    {
        return 'DKL' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
