<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;

class GeneratePaySlipController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-slips.view'), ['index', 'users', 'preview', 'pdf']),
        ];
    }

    public function index()
    {
        return view('payroll.pay-slip.index', $this->commonData() + [
            'filters' => [
                'year' => (int) date('Y'),
                'month' => null,
                'depot_id' => null,
                'role_id' => null,
                'user_id' => null,
            ],
        ]);
    }

    public function users(Request $request)
    {
        $filters = $this->validatedUserFilters($request);
        $role = Role::find($filters['role_id']);

        if (! $role) {
            return response()->json([]);
        }

        return response()->json(
            $this->usersForRoleAndDepot($role->name, $filters['depot_id'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'text' => trim(($user->code ? $user->code . ' - ' : '') . $user->name),
                ])
                ->values()
        );
    }

    private function usersForRoleAndDepot(string $roleName, int $depotId)
    {
        return User::role($roleName)
            ->where('is_active', true)
            ->with(['driverProfile', 'staffProfile', 'controllerProfile', 'supervisorProfile'])
            ->where(function ($query) use ($roleName, $depotId) {
                match ($roleName) {
                    'Driver' => $query->whereHas('driverProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
                    'Controller' => $query->whereHas('controllerProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
                    'Supervisor' => $query->whereHas('supervisorProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
                    default => $query->whereHas('staffProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
                };
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active']);
    }

    public function pdf(Request $request)
    {
        [$processing, $item] = $this->paySlipRecord($request);

        $pdf = $this->buildPdf($processing, $item);
        $fileName = $this->fileName($processing, $item);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function preview(Request $request)
    {
        [$processing, $item] = $this->paySlipRecord($request);

        return response()->json([
            'success' => true,
            'html' => view('payroll.pay-slip.partials.preview', [
                'processing' => $processing,
                'item' => $item,
                'monthName' => Carbon::create(null, $processing->month, 1)->format('F'),
            ])->render(),
            'download_pdf_url' => route('salary-slips.pdf', $this->validatedFilters($request, includeUser: true)),
        ]);
    }

    private function validatedFilters(Request $request, bool $includeUser): array
    {
        $rules = [
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];

        if ($includeUser) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $validated = $request->validate($rules);

        return [
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
            'depot_id' => (int) $validated['depot_id'],
            'role_id' => (int) $validated['role_id'],
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        ];
    }

    private function validatedUserFilters(Request $request): array
    {
        $validated = $request->validate([
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        return [
            'depot_id' => (int) $validated['depot_id'],
            'role_id' => (int) $validated['role_id'],
        ];
    }

    private function processing(array $filters): ?SalaryProcessing
    {
        return SalaryProcessing::query()
            ->where('year', $filters['year'])
            ->where('month', $filters['month'])
            ->where('depot_id', $filters['depot_id'])
            ->where('role_id', $filters['role_id'])
            ->first();
    }

    private function paySlipRecord(Request $request): array
    {
        $filters = $this->validatedFilters($request, includeUser: true);
        $processing = $this->processing($filters);

        abort_if(! $processing, 404, 'No salary processing found for the selected filters.');

        $item = $processing->items()
            ->with('user')
            ->where('user_id', $filters['user_id'])
            ->first();

        abort_if(! $item, 404, 'Selected user does not have a salary processing record.');

        $processing->load(['depot', 'role', 'approver']);

        return [$processing, $item];
    }

    private function commonData(): array
    {
        return [
            'years' => range((int) date('Y'), (int) date('Y') - 5),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($month) => [
                $month => Carbon::create(null, $month, 1)->format('F'),
            ])->all(),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function buildPdf(SalaryProcessing $processing, SalaryProcessingItem $item): string
    {
        $components = collect($item->salary_split ?: [])
            ->where('type', 'earning')
            ->values();

        $month = Carbon::create(null, $processing->month, 1)->format('F');
        $status = $processing->status ?: 'Pending';
        $stream = '';

        $stream .= $this->pdfFillRect(0, 0, 595, 842, [246, 248, 251]);
        $stream .= $this->pdfFillRect(36, 742, 523, 64, [17, 24, 39]);
        $stream .= $this->pdfText('SYSCON', 56, 786, 10, 'F2', [191, 219, 254]);
        $stream .= $this->pdfText('Pay Slip', 56, 764, 24, 'F2', [255, 255, 255]);
        $stream .= $this->pdfText($month . ' ' . $processing->year, 438, 786, 11, 'F2', [255, 255, 255]);
        $stream .= $this->pdfText('Generated ' . now()->format('d-m-Y h:i A'), 438, 766, 9, 'F1', [203, 213, 225]);

        $stream .= $this->pdfCard(36, 642, 160, 74);
        $stream .= $this->pdfLabelValue('Employee', $item->user?->name ?: '-', 52, 690);
        $stream .= $this->pdfText($item->user?->code ?: 'No code', 52, 662, 9, 'F1', [107, 114, 128]);

        $stream .= $this->pdfCard(216, 642, 160, 74);
        $stream .= $this->pdfLabelValue('Working Days', (string) $item->total_working_days, 232, 690);
        $stream .= $this->pdfText($item->total_shifts_completed . ' shifts completed', 232, 662, 9, 'F1', [107, 114, 128]);

        $stream .= $this->pdfFillRect(396, 642, 163, 74, [17, 24, 39]);
        $stream .= $this->pdfText('NET SALARY', 412, 690, 9, 'F2', [203, 213, 225]);
        $stream .= $this->pdfText('INR ' . $this->money($item->net_salary), 412, 664, 18, 'F2', [255, 255, 255]);

        $stream .= $this->pdfCard(36, 488, 252, 128);
        $stream .= $this->pdfSectionTitle('Employee Details', 52, 590);
        $stream .= $this->pdfPair('Employee Code', $item->user?->code ?: '-', 52, 562);
        $stream .= $this->pdfPair('Name', $item->user?->name ?: '-', 52, 536);
        $stream .= $this->pdfPair('Aadhaar No', $item->aadhaar_no ?: '-', 52, 510);
        $stream .= $this->pdfPair('Depo', $processing->depot?->name ?? '-', 166, 510);

        $stream .= $this->pdfCard(307, 488, 252, 128);
        $stream .= $this->pdfSectionTitle('Attendance', 323, 590);
        $stream .= $this->pdfPair('Working Days', (string) $item->total_working_days, 323, 562);
        $stream .= $this->pdfPair('Leave Taken', (string) $item->total_leave_taken, 437, 562);
        $stream .= $this->pdfPair('Unauthorized Leaves', (string) $item->unauthorized_leaves, 323, 536);
        $stream .= $this->pdfPair('Shifts Completed', (string) $item->total_shifts_completed, 437, 536);

        $stream .= $this->pdfCard(36, 234, 318, 230);
        $stream .= $this->pdfSectionTitle('Earnings', 52, 438);
        $stream .= $this->pdfFillRect(52, 408, 286, 24, [248, 250, 252]);
        $stream .= $this->pdfText('Component', 64, 416, 9, 'F2', [75, 85, 99]);
        $stream .= $this->pdfText('Amount', 280, 416, 9, 'F2', [75, 85, 99]);

        $rowY = 386;
        $visibleComponents = $components->take(8);

        if ($visibleComponents->isEmpty()) {
            $stream .= $this->pdfText('No earning components found.', 64, $rowY, 10, 'F1', [107, 114, 128]);
        } else {
            foreach ($visibleComponents as $component) {
                $stream .= $this->pdfLine(52, $rowY + 15, 338, $rowY + 15, [237, 241, 247]);
                $stream .= $this->pdfText($component['name'] ?? 'Component', 64, $rowY, 10, 'F1', [17, 24, 39]);
                $stream .= $this->pdfText($this->money($component['amount'] ?? 0), 280, $rowY, 10, 'F2', [17, 24, 39]);
                $rowY -= 22;
            }

            if ($components->count() > $visibleComponents->count()) {
                $stream .= $this->pdfText('+' . ($components->count() - $visibleComponents->count()) . ' more components', 64, $rowY, 9, 'F1', [107, 114, 128]);
            }
        }

        $stream .= $this->pdfCard(375, 234, 184, 230);
        $stream .= $this->pdfSectionTitle('Salary Summary', 391, 438);
        $stream .= $this->pdfAmountRow('Gross Salary', $this->money($item->basic_salary), 391, 404);
        $stream .= $this->pdfAmountRow('Incentive', $this->money($item->incentive), 391, 374);
        $stream .= $this->pdfAmountRow('Deduction', $this->money($item->deduction), 391, 344);
        $stream .= $this->pdfAmountRow('LOP', $this->money($item->lop), 391, 314);
        $stream .= $this->pdfFillRect(391, 254, 152, 44, [17, 24, 39]);
        $stream .= $this->pdfText('NET SALARY', 407, 280, 8, 'F2', [203, 213, 225]);
        $stream .= $this->pdfText($this->money($item->net_salary), 407, 262, 15, 'F2', [255, 255, 255]);

        $stream .= $this->pdfCard(36, 92, 523, 116);
        $stream .= $this->pdfSectionTitle('Payment & Approval', 52, 182);
        $stream .= $this->pdfPair('Payment Method', $processing->payment_method ?: '-', 52, 154);
        $stream .= $this->pdfPair('Status', $status, 190, 154);
        $stream .= $this->pdfPair('Approved By', $processing->approver?->name ?: '-', 328, 154);
        $stream .= $this->pdfPair('Approved At', $processing->approved_at?->format('d-m-Y h:i A') ?: '-', 52, 124);
        $stream .= $this->pdfPair('Remarks', $processing->remarks ?: '-', 190, 124);

        return $this->simplePdf($stream);
    }

    private function fileName(SalaryProcessing $processing, SalaryProcessingItem $item): string
    {
        $month = Carbon::create(null, $processing->month, 1)->format('F');
        $user = str($item->user?->code ?: $item->user?->name ?: 'user')->replaceMatches('/[^A-Za-z0-9]/', '')->toString();

        return "pay-slip-{$user}-{$month}-{$processing->year}.pdf";
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2);
    }

    private function simplePdf(string $stream): string
    {
        $content = "%PDF-1.4\n";
        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
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

    private function pdfText(string $text, float $x, float $y, int $size = 10, string $font = 'F1', array $rgb = [17, 24, 39]): string
    {
        return $this->pdfColor($rgb) . "BT\n/{$font} {$size} Tf\n1 0 0 1 {$x} {$y} Tm\n(" . $this->pdfEscape(substr($text, 0, 80)) . ") Tj\nET\n";
    }

    private function pdfLabelValue(string $label, string $value, float $x, float $y): string
    {
        return $this->pdfText(strtoupper($label), $x, $y, 9, 'F2', [107, 114, 128])
            . $this->pdfText($value, $x, $y - 23, 14, 'F2', [17, 24, 39]);
    }

    private function pdfPair(string $label, string $value, float $x, float $y): string
    {
        return $this->pdfText(strtoupper($label), $x, $y, 7, 'F2', [107, 114, 128])
            . $this->pdfText($value, $x, $y - 14, 9, 'F2', [17, 24, 39]);
    }

    private function pdfSectionTitle(string $title, float $x, float $y): string
    {
        return $this->pdfText($title, $x, $y, 12, 'F2', [17, 24, 39])
            . $this->pdfLine($x, $y - 10, $x + 210, $y - 10, [237, 241, 247]);
    }

    private function pdfAmountRow(string $label, string $value, float $x, float $y): string
    {
        return $this->pdfText($label, $x, $y, 9, 'F1', [75, 85, 99])
            . $this->pdfText($value, $x + 92, $y, 10, 'F2', [17, 24, 39])
            . $this->pdfLine($x, $y - 11, $x + 152, $y - 11, [237, 241, 247]);
    }

    private function pdfCard(float $x, float $y, float $width, float $height): string
    {
        return $this->pdfFillRect($x, $y, $width, $height, [255, 255, 255])
            . $this->pdfStrokeRect($x, $y, $width, $height, [230, 235, 242]);
    }

    private function pdfFillRect(float $x, float $y, float $width, float $height, array $rgb): string
    {
        return $this->pdfColor($rgb) . "{$x} {$y} {$width} {$height} re f\n";
    }

    private function pdfStrokeRect(float $x, float $y, float $width, float $height, array $rgb): string
    {
        return $this->pdfStrokeColor($rgb) . "0.8 w\n{$x} {$y} {$width} {$height} re S\n";
    }

    private function pdfLine(float $x1, float $y1, float $x2, float $y2, array $rgb): string
    {
        return $this->pdfStrokeColor($rgb) . "0.6 w\n{$x1} {$y1} m\n{$x2} {$y2} l\nS\n";
    }

    private function pdfColor(array $rgb): string
    {
        return collect($rgb)->map(fn ($value) => number_format($value / 255, 3, '.', ''))->implode(' ') . " rg\n";
    }

    private function pdfStrokeColor(array $rgb): string
    {
        return collect($rgb)->map(fn ($value) => number_format($value / 255, 3, '.', ''))->implode(' ') . " RG\n";
    }
}
