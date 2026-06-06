<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class UserLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('user-logs.view'), ['index']),
        ];
    }

    public function index(Request $request)
    {
        UserLog::expireStaleOpenLogs();

        $query = UserLog::with(['user.staffProfile.designation', 'designation'])
            ->latest('login_at');

        if ($request->filled('date')) {
            $query->whereDate('login_at', $request->date);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }

        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('designation_name', function (UserLog $log) {
                    return $log->designation?->name ?? $log->user?->staffProfile?->designation?->name ?? '-';
                })
                ->addColumn('staff_name', function (UserLog $log) {
                    return trim(($log->user?->code ? $log->user->code . ' - ' : '') . ($log->user?->name ?? '-'));
                })
                ->addColumn('login_datetime', function (UserLog $log) {
                    return $log->login_at?->format('d M Y h:i A') ?? '-';
                })
                ->addColumn('logout_datetime', function (UserLog $log) {
                    if (! $log->logout_at) {
                        return '<span class="status-green">Active</span>';
                    }

                    $logout = $log->logout_at->format('d M Y h:i A');

                    if ($log->logout_reason === 'expired') {
                        $logout .= ' <span class="text-muted">(Session Expired)</span>';
                    }

                    return $logout;
                })
                ->rawColumns(['logout_datetime'])
                ->make(true);
        }

        $designations = Designation::orderBy('name')->get(['id', 'name']);
        $staff = User::role('Staff')
            ->with('staffProfile.designation')
            ->when($request->filled('designation_id'), function ($query) use ($request) {
                $query->whereHas('staffProfile', fn ($profileQuery) => $profileQuery->where('designation_id', $request->designation_id));
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('user-log.index', [
            'designations' => $designations,
            'staff' => $staff,
            'filters' => $request->only(['date', 'designation_id', 'staff_id']),
        ]);
    }
}
