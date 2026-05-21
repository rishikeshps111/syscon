<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oem_bank_details')]
#[Fillable([
    'oem_id',
    'account_name',
    'account_number',
    'bank_name',
    'branch',
    'ifsc_code',
    'is_primary',
])]
class OemBankDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'oem_id' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function oem(): BelongsTo
    {
        return $this->belongsTo(Oem::class);
    }
}
