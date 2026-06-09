<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('activity-logs.view'), ['index']),
        ];
    }

    public function index(Request $request)
    {
        $query = Activity::query()
            ->with('causer.roles')
            ->inLog('crud')
            ->latest('id');

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
}
