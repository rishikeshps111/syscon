<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'trip_sheet_entry_id',
    'location_id',
    'route_stop_id',
    'sequence_no',
    'location_name',
    'event',
    'show_location',
    'scheduled_time',
])]
#[Table('trip_sheet_entry_stop_times')]
class TripSheetEntryStopTime extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'show_location' => 'boolean',
        ];
    }

    public function tripSheetEntry(): BelongsTo
    {
        return $this->belongsTo(TripSheetEntry::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class);
    }
}
