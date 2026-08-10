<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'driver_profile_id',
    'recipient_user_id',
    'recipient_type',
    'document_type',
    'expiry_date',
    'reminder_date',
    'expiry_status',
    'sent_count',
    'status',
    'error',
    'sent_at',
])]
#[Table('driver_document_expiry_notification_logs')]
class DriverDocumentExpiryNotificationLog extends Model
{
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'reminder_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }
}
