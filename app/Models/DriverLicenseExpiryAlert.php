<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'driver_profile_id', 'document_type', 'expiry_date', 'expired_count', 'notified_at'])]
#[Table('driver_license_expiry_alerts')]
class DriverLicenseExpiryAlert extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expired_count' => 'integer',
            'expiry_date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driverProfile(): BelongsTo
    {
        return $this->belongsTo(DriverProfile::class);
    }
}
