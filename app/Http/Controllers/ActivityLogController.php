<?php

namespace App\Http\Controllers;

use App\Exports\ActivityLogExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('activity-logs.view'), ['index', 'export']),
        ];
    }

    public function index(Request $request)
    {
        $this->validateFilters($request);

        $query = $this->filteredQuery($request);

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('module', fn (Activity $activity) => $activity->getExtraProperty('module', '-'))
                ->addColumn('event_name', fn (Activity $activity) => ucfirst((string) ($activity->event ?? $activity->description)))
                ->addColumn('user_name', function (Activity $activity) {
                    $user = $activity->causer;

                    if (! $user) {
                        return '-';
                    }

                    return trim(($user->code ? $user->code . ' - ' : '') . ($user->name ?? '-'));
                })
                ->addColumn('role_name', fn (Activity $activity) => $activity->causer?->roles?->pluck('name')->join(', ') ?: '-')
                ->addColumn('created_datetime', fn (Activity $activity) => $activity->created_at?->format('d M Y h:i A') ?? '-')
                ->make(true);
        }

        return view('activity-log.index');
    }

    public function export(Request $request)
    {
        $this->validateFilters($request);

        $query = $this->filteredQuery($request);

        return Excel::download(new ActivityLogExport($query), 'activity-logs.xlsx');
    }

    private function filteredQuery(Request $request)
    {
        return Activity::query()
            ->with('causer.roles')
            ->inLog('crud')
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->to_date))
            ->latest('id');
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }
}
