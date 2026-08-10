<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trip_sheet_entry_id', 'verification_stage', 'notified_at'])]
#[Table('trip_verification_completed_alerts')]
class TripVerificationCompletedAlert extends Model
{
    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tripSheetEntry(): BelongsTo
    {
        return $this->belongsTo(TripSheetEntry::class);
    }
}
