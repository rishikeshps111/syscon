<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'designation_id', 'login_at', 'last_activity_at', 'logout_at', 'logout_reason'])]
#[Table('user_logs')]
class UserLog extends Model
{
    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'logout_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('logout_at');
    }

    public static function expireStaleOpenLogs(?int $userId = null): void
    {
        $lifetime = (int) config('session.lifetime', 120);
        $cutoff = now()->subMinutes($lifetime);

        static::query()
            ->open()
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<=', $cutoff)
            ->when($userId, fn (Builder $query) => $query->where('user_id', $userId))
            ->get()
            ->each(function (UserLog $log) use ($lifetime) {
                $logoutAt = $log->last_activity_at?->copy()->addMinutes($lifetime) ?? now();

                $log->update([
                    'logout_at' => $logoutAt->lessThanOrEqualTo(now()) ? $logoutAt : now(),
                    'logout_reason' => 'expired',
                ]);
            });
    }
}
