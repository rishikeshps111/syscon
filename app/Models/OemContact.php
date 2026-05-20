<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('oem_contacts')]
#[Fillable([
    'oem_id',
    'contact_person',
    'designation',
    'phone',
    'phone_country_code',
    'alternate_phone',
    'alternate_phone_country_code',
    'email',
    'is_primary',
])]
class OemContact extends Model
{
    use HasFactory;

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

    public function getFullPhoneAttribute(): string
    {
        return trim(($this->phone_country_code ?? '') . ' ' . ($this->phone ?? ''));
    }

    public function getFullAlternatePhoneAttribute(): string
    {
        return trim(($this->alternate_phone_country_code ?? '') . ' ' . ($this->alternate_phone ?? ''));
    }
}
