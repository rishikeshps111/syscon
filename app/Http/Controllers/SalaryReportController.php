<?php

namespace App\Http\Controllers;

use App\Exports\SalaryReportExport;
use App\Models\Attendance;
use App\Models\Depot;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SalaryReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-reports.view'), ['index', 'show', 'export']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);

        if ($request->ajax()) {
            return DataTables::of($this->query($filters))
                ->addIndexColumn()
                ->addColumn('user_name', fn (SalaryProcessingItem $item) => $item->user?->name ?: '-')
                ->addColumn('user_code', fn (SalaryProcessingItem $item) => $item->user?->code ?: '-')
                ->addColumn('month_name', fn (SalaryProcessingItem $item) => Carbon::create(null, $item->salaryProcessing->month, 1)->format('F'))
                ->addColumn('year', fn (SalaryProcessingItem $item) => $item->salaryProcessing->year)
                ->addColumn('depot_name', fn (SalaryProcessingItem $item) => $item->salaryProcessing->depot?->name ?: '-')
                ->addColumn('role_name', fn (SalaryProcessingItem $item) => $item->salaryProcessing->role?->name ?: '-')
                ->editColumn('basic_salary', fn (SalaryProcessingItem $item) => number_format((float) $item->basic_salary, 2))
                ->editColumn('deduction', fn (SalaryProcessingItem $item) => number_format((float) $item->deduction, 2))
                ->editColumn('lop', fn (SalaryProcessingItem $item) => number_format((float) $item->lop, 2))
                ->editColumn('net_salary', fn (SalaryProcessingItem $item) => number_format((float) $item->net_salary, 2))
                ->addColumn('status', function (SalaryProcessingItem $item) {
                    $status = $item->salaryProcessing->status;
                    $class = $status === 'Approved' ? 'status-green' : 'status-yellow';

                    return '<span class="' . $class . '">' . e($status) . '</span>';
                })
                ->addColumn('action', fn (SalaryProcessingItem $item) => '<button type="button" class="btn-nowrap btn-cstm border-0 view-salary" data-url="' . route('salary-reports.show', $item) . '">View</button>')
                ->filterColumn('user_name', fn (Builder $query, string $keyword) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$keyword}%")))
                ->filterColumn('user_code', fn (Builder $query, string $keyword) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('code', 'like', "%{$keyword}%")))
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('salary-report.index', [
            'filters' => $filters,
            'years' => range((int) date('Y'), (int) date('Y') - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ])->all(),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(SalaryProcessingItem $salaryProcessingItem)
    {
        $salaryProcessingItem->load(['user', 'salaryProcessing.depot', 'salaryProcessing.role', 'salaryProcessing.approver']);
        $processing = $salaryProcessingItem->salaryProcessing;

        return response()->json([
            'user' => [
                'name' => $salaryProcessingItem->user?->name ?: '-',
                'code' => $salaryProcessingItem->user?->code ?: '-',
                'aadhaar_no' => $salaryProcessingItem->aadhaar_no ?: '-',
            ],
            'processing' => [
                'month' => Carbon::create(null, $processing->month, 1)->format('F'),
                'year' => $processing->year,
                'depot' => $processing->depot?->name ?: '-',
                'role' => $processing->role?->name ?: '-',
                'salary_date' => $processing->salary_date?->format('d-m-Y') ?: '-',
                'payment_method' => $processing->payment_method ?: '-',
                'status' => $processing->status,
                'approved_by' => $processing->approver?->name ?: '-',
                'approved_at' => $processing->approved_at?->format('d-m-Y h:i A') ?: '-',
                'remarks' => $processing->remarks ?: '-',
            ],
            'attendance' => [
                'total_leave_taken' => (float) $salaryProcessingItem->total_leave_taken,
                'unauthorized_leaves' => (float) $salaryProcessingItem->unauthorized_leaves,
                'total_shifts_completed' => $salaryProcessingItem->total_shifts_completed,
                'total_working_days' => $salaryProcessingItem->total_working_days,
            ],
            'salary' => [
                'components' => collect($salaryProcessingItem->salary_split ?: [])->where('type', 'earning')->values(),
                'gross_salary' => (float) $salaryProcessingItem->basic_salary,
                'incentive' => (float) $salaryProcessingItem->incentive,
                'deduction' => (float) $salaryProcessingItem->deduction,
                'lop' => (float) $salaryProcessingItem->lop,
                'net_salary' => (float) $salaryProcessingItem->net_salary,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $suffix = collect([$filters['year'], $filters['month'] ?: null])->filter()->implode('-');

        return Excel::download(
            new SalaryReportExport($this->query($filters)),
            "salary-report-{$suffix}.xlsx"
        );
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'depot_id' => ['nullable', 'integer', 'exists:depots,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        return [
            'year' => (int) ($validated['year'] ?? date('Y')),
            'month' => isset($validated['month']) ? (int) $validated['month'] : null,
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'role_id' => isset($validated['role_id']) ? (int) $validated['role_id'] : null,
        ];
    }

    private function query(array $filters): Builder
    {
        return SalaryProcessingItem::query()
            ->with(['user', 'salaryProcessing.depot', 'salaryProcessing.role', 'salaryProcessing.approver'])
            ->whereHas('salaryProcessing', function (Builder $query) use ($filters) {
                $query->where('year', $filters['year']);

                foreach (['month', 'depot_id', 'role_id'] as $field) {
                    if ($filters[$field]) {
                        $query->where($field, $filters[$field]);
                    }
                }
            })
            ->latest('salary_processing_items.id');
    }
}
