<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'expired_count', 'notified_at'])]
#[Table('driver_license_expiry_alerts')]
class DriverLicenseExpiryAlert extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expired_count' => 'integer',
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
