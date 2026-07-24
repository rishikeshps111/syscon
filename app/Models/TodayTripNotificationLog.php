<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trip_date', 'trip_count', 'sent_count', 'status', 'error', 'sent_at'])]
#[Table('today_trip_notification_logs')]
class TodayTripNotificationLog extends Model
{
    protected function casts(): array
    {
        return ['trip_date' => 'date', 'sent_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
