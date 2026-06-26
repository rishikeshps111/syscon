<?php

namespace App\Http\Controllers;

use App\Exports\LicenseExpiryReportExport;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\TripAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;

class LicenseExpiryReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('license-expiry-reports.view'), ['index', 'export']),
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request, $request->boolean('generate'));

        if ($request->ajax() && $request->boolean('generate')) {
            $report = $this->reportData($filters);

            return response()->json([
                'success' => $report['rows']->isNotEmpty(),
                'html' => view('reports.partials.license-expiry-report-table', $report)->render(),
                'message' => $report['rows']->isNotEmpty()
                    ? 'License expiry report generated successfully.'
                    : 'No license expiry records found for the selected filters.',
                'download_excel_url' => $report['rows']->isNotEmpty()
                    ? route('reports.license-expiry.export', $filters)
                    : null,
                'filters' => $filters,
            ]);
        }

        return view('reports.license-expiry', [
            'filters' => $filters,
            'expiryFilters' => $this->expiryFilters(),
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request, true);
        $filter = $filters['expiry_filter'];

        return Excel::download(
            new LicenseExpiryReportExport($this->query($filters)),
            "license-expiry-report-{$filter}.xlsx"
        );
    }

    private function validatedFilters(Request $request, bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        $validated = $request->validate([
            'expiry_filter' => [$presence, 'in:6_months,3_months,1_month,expired'],
            'depot_id' => ['nullable', 'integer', 'exists:depots,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license' => ['nullable', 'string', 'max:50'],
        ]);

        return [
            'expiry_filter' => $validated['expiry_filter'] ?? null,
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'name' => isset($validated['name']) ? trim((string) $validated['name']) : null,
            'phone' => isset($validated['phone']) ? trim((string) $validated['phone']) : null,
            'license' => isset($validated['license']) ? trim((string) $validated['license']) : null,
        ];
    }

    private function query(array $filters): Builder
    {
        $filter = $filters['expiry_filter'];
        $today = Carbon::today();

        $query = DriverProfile::query()
            ->with(['user', 'depot'])
            ->whereHas('user', fn (Builder $userQuery) => $userQuery->where('is_active', true));

        if ($filter === 'expired') {
            $query->where(function (Builder $subQuery) use ($today) {
                $subQuery->whereDate('expiry_date', '<', $today)
                    ->orWhereDate('badge_expiry_date', '<', $today);
            });
        } else {
            $months = match ($filter) {
                '1_month' => 1,
                '3_months' => 3,
                default => 6,
            };
            $until = $today->copy()->addMonths($months);

            $query->where(function (Builder $subQuery) use ($today, $until) {
                $subQuery->whereBetween('expiry_date', [$today->toDateString(), $until->toDateString()])
                    ->orWhereBetween('badge_expiry_date', [$today->toDateString(), $until->toDateString()]);
            });
        }

        if (! empty($filters['depot_id'])) {
            $query->where('depot_id', $filters['depot_id']);
        }

        if (! empty($filters['name'])) {
            $name = $filters['name'];

            $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                ->where('name', 'like', '%' . $name . '%'));
        }

        if (! empty($filters['phone'])) {
            $phone = $filters['phone'];

            $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                ->where('phone', 'like', '%' . $phone . '%')
                ->orWhere('country_code', 'like', '%' . $phone . '%'));
        }

        if (! empty($filters['license'])) {
            $license = $filters['license'];

            $query->where(function (Builder $subQuery) use ($license) {
                $subQuery->where('license_number', 'like', '%' . $license . '%')
                    ->orWhere('badge_number', 'like', '%' . $license . '%');
            });
        }

        return $query
            ->orderByRaw('COALESCE(expiry_date, badge_expiry_date) asc')
            ->orderBy('id');
    }

    private function expiryFilters(): array
    {
        return [
            '6_months' => 'Next 6 Months',
            '3_months' => 'Next 3 Months',
            '1_month' => 'Next 1 Month',
            'expired' => 'Expired List',
        ];
    }

    private function reportData(array $filters): array
    {
        $rows = $this->query($filters)
            ->get()
            ->values()
            ->map(fn (DriverProfile $driver, int $index) => $this->rowData($driver, $index));

        return [
            'rows' => $rows,
            'columns' => $this->columns(),
        ];
    }

    public function rowData(DriverProfile $driver, int $index): array
    {
        return [
            'sl_no' => $index + 1,
            'driver_name' => $driver->user?->name ?: '-',
            'assigned' => self::assignmentLabel($driver),
            'depot_name' => $driver->depot?->name ?: '-',
            'license_no' => $driver->license_number ?: '-',
            'badge_no' => $driver->badge_number ?: '-',
            'license_expiry_date' => $driver->expiry_date?->format('d-m-Y') ?: '-',
            'badge_expiry_date' => $driver->badge_expiry_date?->format('d-m-Y') ?: '-',
            'phone_no' => $driver->user?->full_phone ?: '-',
        ];
    }

    public function columns(): array
    {
        return [
            'sl_no' => 'SL No',
            'driver_name' => 'Driver Name',
            'assigned' => 'Assigned',
            'depot_name' => 'Depot',
            'license_no' => 'License No',
            'badge_no' => 'Badge No',
            'license_expiry_date' => 'License Expiry Date',
            'badge_expiry_date' => 'Badge Expiry Date',
            'phone_no' => 'Phone No',
        ];
    }

    public static function assignmentLabel(DriverProfile $driver): string
    {
        $assignment = TripAssignment::with(['trip', 'vehicle'])
            ->where('driver_profile_id', $driver->id)
            ->whereDate('from_date', '<=', today())
            ->whereDate('to_date', '>=', today())
            ->latest('from_date')
            ->first();

        if (! $assignment) {
            return 'Not Assigned';
        }

        return trim('Assigned - ' . ($assignment->trip?->trip_title ?: $assignment->trip?->code ?: 'Trip'));
    }
}
