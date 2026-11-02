<?php

namespace App\Http\Controllers;

use App\Exports\SalaryReportExport;
use App\Mail\SalaryReportMail;
use App\Models\Attendance;
use App\Support\WageRegister;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;

class SalaryReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-reports.view'), ['index', 'show', 'export', 'pdf', 'sendMail']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request, $request->boolean('generate'));
        $report = $this->hasCompleteFilters($filters) ? $this->reportData($filters) : null;

        if ($request->ajax() && $request->boolean('generate')) {
            $hasReport = $report && $report['processing'] && $report['items']->isNotEmpty();

            return response()->json([
                'success' => (bool) $hasReport,
                'html' => view('salary-report.partials.report-table', [
                    'report' => $report,
                    'mailTo' => config('mail.salary_report_to'),
                ])->render(),
                'message' => $hasReport ? 'Salary report generated successfully.' : 'No salary report found for the selected filters.',
                'download_pdf_url' => $hasReport ? route('salary-reports.pdf', $filters) : null,
                'download_excel_url' => $hasReport ? route('salary-reports.export', $filters) : null,
                'send_mail_url' => $hasReport ? route('salary-reports.send-mail') : null,
                'filters' => $filters,
            ]);
        }

        return view('salary-report.index', $this->commonData() + [
            'filters' => $filters,
            'report' => $report,
            'mailTo' => config('mail.salary_report_to'),
        ]);
    }

    public function show(SalaryProcessingItem $salaryProcessingItem)
    {
        $salaryProcessingItem->load(['user.roles', 'salaryProcessing.depot', 'salaryProcessing.approver']);
        $processing = $salaryProcessingItem->salaryProcessing;
        abort_unless($processing && strtolower($processing->status) === 'approved', 404, 'No approved salary processing found.');
        $register = WageRegister::row($salaryProcessingItem, 0);

        return response()->json([
            'user' => [
                'name' => $salaryProcessingItem->user?->name ?: '-',
                'code' => $salaryProcessingItem->user?->ref_code ?? '',
                'aadhaar_no' => $salaryProcessingItem->aadhaar_no ?: '-',
            ],
            'processing' => [
                'month' => Carbon::create(null, $processing->month, 1)->format('F'),
                'year' => $processing->year,
                'depot' => $processing->depot?->name ?: '-',
                'role' => WageRegister::role($salaryProcessingItem) ?? '',
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
            'register' => ['headings' => WageRegister::headings(), 'row' => $register, 'warnings' => WageRegister::warnings([$register])],
            'salary' => [
                'components' => collect([40, 41, 43, 44, 45, 46, 47])->filter(fn ($c) => $register[$c] !== null)
                    ->map(fn ($c) => ['name' => WageRegister::headings()[$c], 'type' => 'earning', 'amount' => $register[$c]])->values(),
                'gross_salary' => $register[48],
                'incentive' => $register[47],
                'deduction' => $register[57],
                'lop' => (float) $salaryProcessingItem->lop,
                'net_salary' => $register[58],
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $report = $this->reportData($filters);
        abort_unless($report['processing'] && $report['items']->isNotEmpty(), 404, 'No approved salary processing found for the selected filters.');

        return Excel::download(new SalaryReportExport($report), $this->fileName($report, 'xlsx'));
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $report = $this->reportData($filters);
        abort_unless($report['processing'] && $report['items']->isNotEmpty(), 404, 'No approved salary processing found for the selected filters.');
        $pdf = $this->buildPdf($report);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->fileName($report, 'pdf') . '"',
        ]);
    }

    public function sendMail(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $report = $this->reportData($filters);
        abort_unless($report['processing'] && $report['items']->isNotEmpty(), 404, 'No approved salary processing found for the selected filters.');
        $pdf = $this->buildPdf($report);
        $to = config('mail.salary_report_to');

        Mail::to($to)->send(new SalaryReportMail(
            period: $this->periodLabel($report),
            depot: $report['depot']?->name ?? '-',
            role: $report['roleLabel'],
            pdfContent: $pdf,
            fileName: $this->fileName($report, 'pdf'),
        ));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Salary report PDF sent to {$to}.",
            ]);
        }

        return redirect()
            ->route('salary-reports.index', $filters + ['generate' => 1])
            ->with('success', "Salary report PDF sent to {$to}.");
    }

    private function validatedFilters(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';
        $validated = $request->validate([
            'year' => [$presence, 'integer', 'between:2000,2100'],
            'month' => [$presence, 'integer', 'between:1,12'],
            'depot_id' => [$presence, 'integer', 'exists:depots,id'],
            'role_id' => [
                $presence,
                Rule::when(
                    $request->input('role_id') !== 'all',
                    ['integer', 'exists:roles,id'],
                    ['in:all']
                ),
            ],
        ]);

        return [
            'year' => isset($validated['year']) ? (int) $validated['year'] : (int) date('Y'),
            'month' => isset($validated['month']) ? (int) $validated['month'] : null,
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'role_id' => isset($validated['role_id'])
                ? ($validated['role_id'] === 'all' ? 'all' : (int) $validated['role_id'])
                : null,
        ];
    }

    private function reportData(array $filters): array
    {
        $relations = ['depot', 'approver', 'items.user.roles', 'items.salaryProcessing.depot'];
        foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $profile) {
            $relations[] = 'items.user.'.$profile.'.designation';
            $relations[] = 'items.user.'.$profile.'.location';
        }
        $processings = SalaryProcessing::with($relations)
            ->where('year', $filters['year'])->where('month', $filters['month'])
            ->where('depot_id', $filters['depot_id'])->whereRaw('LOWER(status) = ?', ['approved'])->get();
        $processing = $processings->first();
        $role = $filters['role_id'] === 'all' ? null : Role::find($filters['role_id']);
        $items = $processings->flatMap(fn ($payroll) => $payroll->items)
            ->filter(fn ($item) => $filters['role_id'] === 'all' || WageRegister::role($item) === $role?->name)
            ->sortBy(fn ($item) => $item->user?->name ?? '')->values();
        $rows = $items->map(fn ($item, $index) => WageRegister::row($item, $index))->all();

        return [
            'filters' => $filters, 'processing' => $processing, 'processings' => $processings,
            'items' => $items, 'rows' => $rows, 'headings' => WageRegister::headings(),
            'groups' => WageRegister::GROUPS, 'warnings' => WageRegister::warnings($rows),
            'monthName' => $filters['month'] ? Carbon::create(null, $filters['month'], 1)->format('F') : '',
            'year' => $filters['year'], 'depot' => $processing?->depot ?: Depot::find($filters['depot_id']),
            'role' => $role, 'roleLabel' => $filters['role_id'] === 'all' ? 'All' : ($role?->name ?? ''),
            'showRoleColumns' => $filters['role_id'] === 'all',
        ];
    }

    private function commonData(): array
    {
        return [
            'years' => range((int) date('Y'), (int) date('Y') - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn($month) => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ])->all(),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function hasCompleteFilters(array $filters): bool
    {
        return $filters['year'] && $filters['month'] && $filters['depot_id'] && $filters['role_id'] !== null;
    }

    private function fileName(array $report, string $extension): string
    {
        return $this->fileBaseName($report) . ".{$extension}";
    }

    private function fileBaseName(array $report): string
    {
        $depot = $report['depot']?->short_name
            ?: $report['depot']?->code
            ?: $report['depot']?->name
            ?: 'Depot';
        $month = $report['monthName'] ?: 'Month';
        $year = $report['year'] ?: date('Y');
        $role = $report['roleLabel'] ?: 'Role';

        $depot = str($depot)->replaceMatches('/[^A-Za-z0-9]/', '')->toString();
        $month = str($month)->replaceMatches('/[^A-Za-z0-9]/', '')->toString();
        $role = str($role)->replaceMatches('/[^A-Za-z0-9]/', '')->headline()->replace(' ', '')->toString();

        return "{$depot}{$month}{$year}_{$role}";
    }

    private function periodLabel(array $report): string
    {
        return $report['monthName'] . ' ' . $report['year'];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2);
    }

    private function buildPdf(array $report): string
    {
        return \App\Support\WageRegisterPdf::render($report);
    }
}
