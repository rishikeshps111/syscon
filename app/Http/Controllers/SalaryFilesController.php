<?php

namespace App\Http\Controllers;

use App\Exports\SalaryReportExport;
use App\Models\Attendance;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;

class SalaryFilesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-files.view'), ['index', 'excel', 'pdf']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $files = $request->boolean('get_files') ? $this->files($filters) : collect();

        return view('salary-files.index', [
            'filters' => $filters,
            'files' => $files,
            'years' => range((int) date('Y'), (int) date('Y') - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ])->all(),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function excel(SalaryProcessing $salaryProcessing)
    {
        $report = $this->reportData($salaryProcessing);

        return Excel::download(new SalaryReportExport($report), $this->fileName($report, 'xlsx'));
    }

    public function pdf(SalaryProcessing $salaryProcessing)
    {
        $report = $this->reportData($salaryProcessing);

        return response($this->buildPdf($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->fileName($report, 'pdf') . '"',
        ]);
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
            'year' => isset($validated['year']) ? (int) $validated['year'] : (int) date('Y'),
            'month' => isset($validated['month']) ? (int) $validated['month'] : null,
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'role_id' => isset($validated['role_id']) ? (int) $validated['role_id'] : null,
        ];
    }

    private function files(array $filters)
    {
        return SalaryProcessing::with(['depot', 'role', 'approver'])
            ->withCount('items')
            ->whereIn('status', ['Completed', 'Approved'])
            ->when($filters['year'], fn ($query) => $query->where('year', $filters['year']))
            ->when($filters['month'], fn ($query) => $query->where('month', $filters['month']))
            ->when($filters['depot_id'], fn ($query) => $query->where('depot_id', $filters['depot_id']))
            ->when($filters['role_id'], fn ($query) => $query->where('role_id', $filters['role_id']))
            ->latest()
            ->get();
    }

    private function reportData(SalaryProcessing $processing): array
    {
        $processing->load(['depot', 'role', 'approver', 'items.user']);
        $items = $processing->items->sortBy(fn (SalaryProcessingItem $item) => $item->user?->name ?? '')->values();
        $componentNames = $items
            ->flatMap(fn (SalaryProcessingItem $item) => collect($item->salary_split ?: [])->where('type', 'earning')->pluck('name'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'filters' => [
                'year' => $processing->year,
                'month' => $processing->month,
                'depot_id' => $processing->depot_id,
                'role_id' => $processing->role_id,
            ],
            'processing' => $processing,
            'items' => $items,
            'componentNames' => $componentNames,
            'monthName' => Carbon::create(null, $processing->month, 1)->format('F'),
            'year' => $processing->year,
            'depot' => $processing->depot,
            'role' => $processing->role,
        ];
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
        $role = $report['role']?->name ?: 'Role';

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
            'SYSCON Salary File',
            'Period: ' . $this->periodLabel($report),
            'Depo: ' . ($report['depot']?->name ?? '-'),
            'Role: ' . ($report['role']?->name ?? '-'),
            'Generated: ' . now()->format('d-m-Y h:i A'),
            '',
            'SL  Code        Name                         Gross       Deduction   LOP        Net        Status',
        ];

        foreach ($report['items'] as $index => $item) {
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

        if ($report['items']->isEmpty()) {
            $lines[] = 'No salary records found in this file.';
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
