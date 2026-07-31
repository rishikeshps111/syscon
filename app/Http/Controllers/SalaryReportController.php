<?php

namespace App\Http\Controllers;

use App\Exports\SalaryReportExport;
use App\Mail\SalaryReportMail;
use App\Models\Attendance;
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
                'components' => collect($salaryProcessingItem->salary_split ?: [])->values(),
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
        $filters = $this->validatedFilters($request, true);
        $report = $this->reportData($filters);

        return Excel::download(new SalaryReportExport($report), $this->fileName($report, 'xlsx'));
    }

    public function pdf(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $report = $this->reportData($filters);
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
        $query = SalaryProcessing::with([
            'depot',
            'role',
            'approver',
            'items.salaryProcessing.role',
            'items.salaryProcessing.approver',
            'items.user.roles',
            'items.user.staffProfile.designation',
        ])
            ->where('year', $filters['year'])
            ->where('month', $filters['month'])
            ->where('depot_id', $filters['depot_id'])
            ->whereIn('status', ['Completed', 'Approved'])
            ->when($filters['role_id'] !== 'all', fn ($query) => $query->where('role_id', $filters['role_id']));

        $processings = $query->get();
        $processing = $processings->first();
        $items = $processings
            ->flatMap(fn (SalaryProcessing $salaryProcessing) => $salaryProcessing->items)
            ->sortBy(fn (SalaryProcessingItem $item) => $item->user?->name ?? '')
            ->values();

        $componentNames = $items
            ->flatMap(fn(SalaryProcessingItem $item) => collect($item->salary_split ?: [])->map(
                fn ($component) => ($component['name'] ?? 'Component') . ' (' . ucfirst($component['type'] ?? 'earning') . ')'
            ))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'filters' => $filters,
            'processing' => $processing,
            'processings' => $processings,
            'items' => $items,
            'componentNames' => $componentNames,
            'monthName' => $filters['month'] ? Carbon::create(null, $filters['month'], 1)->format('F') : '-',
            'year' => $filters['year'],
            'depot' => $processing?->depot ?: Depot::find($filters['depot_id']),
            'role' => $filters['role_id'] === 'all'
                ? null
                : ($processing?->role ?: Role::find($filters['role_id'])),
            'roleLabel' => $filters['role_id'] === 'all'
                ? 'All'
                : ($processing?->role?->name ?: Role::find($filters['role_id'])?->name ?: '-'),
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
        $lines = [
            'SYSCON Salary Report',
            'Period: ' . $this->periodLabel($report),
            'Depo: ' . ($report['depot']?->name ?? '-'),
            'Role: ' . $report['roleLabel'],
            'Generated: ' . now()->format('d-m-Y h:i A'),
            '',
            $report['showRoleColumns']
                ? 'SL  Code        Name                 Role        Designation          Gross       Deduction   LOP        Net'
                : 'SL  Code        Name                         Gross       Deduction   LOP        Net        Status',
        ];

        foreach ($report['items'] as $index => $item) {
            if ($report['showRoleColumns']) {
                $roleName = $item->salaryProcessing?->role?->name ?: '-';
                $designation = $roleName === 'Staff'
                    ? ($item->user?->staffProfile?->designation?->name ?: '-')
                    : '-';
                $lines[] = sprintf(
                    '%-3s %-11s %-20s %-11s %-20s %10s %10s %10s %10s',
                    $index + 1,
                    substr((string) ($item->user?->code ?: '-'), 0, 11),
                    substr((string) ($item->user?->name ?: '-'), 0, 20),
                    substr($roleName, 0, 11),
                    substr($designation, 0, 20),
                    $this->money($item->basic_salary),
                    $this->money($item->deduction),
                    $this->money($item->lop),
                    $this->money($item->net_salary),
                );
            } else {
                $lines[] = sprintf(
                    '%-3s %-11s %-28s %10s %10s %10s %10s %s',
                    $index + 1,
                    substr((string) ($item->user?->code ?: '-'), 0, 11),
                    substr((string) ($item->user?->name ?: '-'), 0, 28),
                    $this->money($item->basic_salary),
                    $this->money($item->deduction),
                    $this->money($item->lop),
                    $this->money($item->net_salary),
                    $item->salaryProcessing?->status ?: '-'
                );
            }
        }

        if ($report['items']->isEmpty()) {
            $lines[] = 'No completed salary records found for the selected filters.';
        }

        return $this->simplePdf($lines);
    }

    private function simplePdf(array $lines): string
    {
        $content = "%PDF-1.4\n";
        $objects = [];
        $stream = "BT\n/F1 9 Tf\n50 550 Td\n";

        foreach ($lines as $line) {
            $stream .= '(' . $this->pdfEscape($line) . ") Tj\n0 -15 Td\n";
        }

        $stream .= "ET";
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";

        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($content);
            $content .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($content);
        $content .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $content .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $content .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $content;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
