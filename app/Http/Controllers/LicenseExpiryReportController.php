<?php

namespace App\Http\Controllers;

use App\Exports\LicenseExpiryReportExport;
use App\Models\DriverProfile;
use App\Models\TripAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

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
        $filters = $this->validatedFilters($request);

        if ($request->ajax()) {
            return DataTables::of($this->query($filters))
                ->addIndexColumn()
                ->addColumn('driver_name', fn (DriverProfile $driver) => $driver->user?->name ?: '-')
                ->addColumn('assigned', fn (DriverProfile $driver) => $this->assignmentLabel($driver))
                ->addColumn('depot_name', fn (DriverProfile $driver) => $driver->depot?->name ?: '-')
                ->addColumn('license_no', fn (DriverProfile $driver) => $driver->license_number ?: '-')
                ->addColumn('badge_no', fn (DriverProfile $driver) => $driver->badge_number ?: '-')
                ->addColumn('license_expiry_date', fn (DriverProfile $driver) => $driver->expiry_date?->format('d M Y') ?: '-')
                ->addColumn('badge_expiry_date', fn (DriverProfile $driver) => $driver->badge_expiry_date?->format('d M Y') ?: '-')
                ->addColumn('phone_no', fn (DriverProfile $driver) => $driver->user?->full_phone ?: '-')
                ->addColumn('action', fn () => '<button type="button" class="btn btn-sm btn-secondary" disabled>Send Reminder</button>')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('reports.license-expiry', [
            'filters' => $filters,
            'expiryFilters' => $this->expiryFilters(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $filter = $filters['expiry_filter'] ?? '6_months';

        return Excel::download(
            new LicenseExpiryReportExport($this->query($filters)),
            "license-expiry-report-{$filter}.xlsx"
        );
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'expiry_filter' => ['nullable', 'in:6_months,3_months,1_month,expired'],
        ]);
    }

    private function query(array $filters): Builder
    {
        $filter = $filters['expiry_filter'] ?? '6_months';
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
