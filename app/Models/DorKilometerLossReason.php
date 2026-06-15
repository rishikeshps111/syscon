<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dor_account_responsible_id', 'code', 'name', 'is_active'])]
#[Table('dor_kilometer_loss_reasons')]
class DorKilometerLossReason extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accountResponsible(): BelongsTo
    {
        return $this->belongsTo(DorAccountResponsible::class, 'dor_account_responsible_id');
    }
}
