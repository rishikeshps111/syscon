<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assignable_type', 'assignable_id', 'depot_id', 'reporting_to', 'from_date', 'to_date', 'created_by', 'updated_by'])]
#[Table('depot_assignments')]
class DepotAssignment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assignable_id' => 'integer',
            'depot_id' => 'integer',
            'reporting_to' => 'integer',
            'from_date' => 'date',
            'to_date' => 'date',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_to');
    }

    public function getDateStatusAttribute(): string
    {
        $today = today();

        if ($this->from_date?->gt($today)) {
            return 'upcoming';
        }

        if ($this->to_date?->lt($today)) {
            return 'expired';
        }

        return 'active';
    }
}
