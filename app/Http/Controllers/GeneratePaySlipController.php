<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class GeneratePaySlipController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-slips.view'), ['index', 'users', 'preview', 'pdf', 'zip']),
        ];
    }

    public function index()
    {
        return view('payroll.pay-slip.index', $this->commonData() + [
            'filters' => [
                'year' => (int) date('Y'),
                'month' => null,
                'depot_id' => null,
                'user_id' => null,
            ],
        ]);
    }

    public function users(Request $request)
    {
        $filters = $this->validatedUserFilters($request);
        $processing = SalaryProcessing::query()
            ->where('year', $filters['year'])
            ->where('month', $filters['month'])
            ->where('depot_id', $filters['depot_id'])
            ->whereRaw('LOWER(status) = ?', ['approved'])
            ->with(['items.user'])
            ->first();

        $users = $processing?->items
            ->filter(fn ($item) => $item->user)
            ->sortBy(fn ($item) => $item->user->name)
            ->map(fn ($item) => [
                'id' => (int) $item->user->id,
                'text' => trim(($item->user->code ? $item->user->code . ' - ' : '') . $item->user->name),
            ])
            ->values()
            ->all() ?? [];

        return response()->json($users);
    }

    private function usersForRoleAndDepot(string $roleName, int $depotId)
    {
        return User::role($roleName)
            ->where('is_active', true)
            ->with(['driverProfile', 'housekeepingProfile', 'staffProfile', 'controllerProfile', 'supervisorProfile'])
            ->where(function ($query) use ($roleName, $depotId) {
                match ($roleName) {
                    'Driver' => $query->whereHas('driverProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
                    'Housekeeping' => $query->whereHas('housekeepingProfile', fn ($profile) => $profile->where('depot_id', $depotId)),
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

    public function zip(Request $request)
    {
        $filters = $this->validatedUserFilters($request);
        $processing = $this->processing($filters);
        abort_if(! $processing, 404, 'No approved salary processing found for the selected filters.');
        $processing->load(['depot', 'approver']);
        $items = $processing->items()->with([
            'user.roles', 'user.driverProfile', 'user.housekeepingProfile',
            'user.staffProfile', 'user.controllerProfile', 'user.supervisorProfile',
        ])->orderBy('user_id')->get()->filter(fn ($item) => $item->user);
        abort_if($items->isEmpty(), 404, 'No employee payslips are available for the selected payroll.');

        $path = tempnam(storage_path('app'), 'payslips-');
        abort_if($path === false, 500, 'Unable to create payslip archive.');
        $archive = new \ZipArchive();
        $opened = false;
        try {
            if ($archive->open($path, \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to open payslip archive.');
            }
            $opened = true;
            foreach ($items as $item) {
                $filename = pathinfo($this->fileName($processing, $item), PATHINFO_FILENAME)
                    . '-employee-' . $item->user_id . '.pdf';
                if (! $archive->addFromString($filename, $this->buildPdf($processing, $item))) {
                    throw new \RuntimeException('Unable to add payslip to archive.');
                }
            }
            $closed = $archive->close();
            $opened = false;
            if (! $closed) {
                throw new \RuntimeException('Unable to finalize payslip archive.');
            }
            $filename = sprintf('payslips-depot-%d-%d-%02d.zip', $filters['depot_id'], $filters['year'], $filters['month']);
            return response()->download($path, $filename, ['Content-Type' => 'application/zip'])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            if ($opened) $archive->close();
            if (is_file($path)) unlink($path);
            report($exception);
            abort(500, 'Unable to generate payslip ZIP. Please try again.');
        }
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
        ];

        if ($includeUser) {
            $rules['user_id'] = ['required', 'integer', 'exists:users,id'];
        }

        $validated = $request->validate($rules);

        return [
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
            'depot_id' => (int) $validated['depot_id'],
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        ];
    }

    private function validatedUserFilters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
        ]);

        return [
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
            'depot_id' => (int) $validated['depot_id'],
        ];
    }

    private function processing(array $filters): ?SalaryProcessing
    {
        return SalaryProcessing::query()
            ->where('year', $filters['year'])
            ->where('month', $filters['month'])
            ->where('depot_id', $filters['depot_id'])
            ->whereRaw('LOWER(status) = ?', ['approved'])
            ->first();
    }

    private function paySlipRecord(Request $request): array
    {
        $filters = $this->validatedFilters($request, includeUser: true);
        $processing = $this->processing($filters);

        abort_if(! $processing, 404, 'No salary processing found for the selected filters.');

        $item = $processing->items()
            ->with([
                'user.roles',
                'user.housekeepingProfile',
                'user.driverProfile',
                'user.staffProfile',
                'user.controllerProfile',
                'user.supervisorProfile',
            ])
            ->where('user_id', $filters['user_id'])
            ->first();

        abort_if(! $item, 404, 'Selected user does not have a salary processing record.');

        $processing->load(['depot', 'approver']);

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
        ];
    }

    private function buildPdf(SalaryProcessing $processing, SalaryProcessingItem $item): string
    {
        $slip = \App\Support\PaySlipData::build($processing, $item);
        $pdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'Helvetica']);
        $pdf->loadHtml(view('payroll.pay-slip.pdf', compact('slip'))->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output(['compress' => 0]);
    }

    private function fileName(SalaryProcessing $processing, SalaryProcessingItem $item): string
    {
        $month = Carbon::create(null, $processing->month, 1)->format('F');
        $user = str($item->user?->code ?: $item->user?->name ?: 'user')->replaceMatches('/[^A-Za-z0-9]/', '')->toString();

        return "pay-slip-{$user}-{$month}-{$processing->year}.pdf";
    }

}
