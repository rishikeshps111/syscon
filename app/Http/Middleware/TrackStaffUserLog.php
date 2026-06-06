<?php

namespace App\Http\Middleware;

use App\Models\UserLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackStaffUserLog
{
    public function handle(Request $request, Closure $next): Response
    {
        UserLog::expireStaleOpenLogs();

        if ($request->user()?->hasRole('Staff')) {
            $log = $this->currentLog($request);
            $request->session()->put('user_log_id', $log->id);
            $log->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }

    private function currentLog(Request $request): UserLog
    {
        $user = $request->user();
        $logId = $request->session()->get('user_log_id');

        if ($logId) {
            $log = UserLog::query()
                ->whereKey($logId)
                ->where('user_id', $user->id)
                ->open()
                ->first();

            if ($log) {
                return $log;
            }
        }

        $profile = $user->staffProfile;

        return UserLog::create([
            'user_id' => $user->id,
            'designation_id' => $profile?->designation_id,
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
