<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['controller_profile_id', 'trip_date', 'trip_count', 'sent_count', 'status', 'error', 'sent_at'])]
#[Table('controller_trip_notification_logs')]
class ControllerTripNotificationLog extends Model
{
    protected function casts(): array
    {
        return ['trip_date' => 'date', 'sent_at' => 'datetime'];
    }
}
