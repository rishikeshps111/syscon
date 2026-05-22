<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id',
    'document_type_id',
    'expiry_date',
    'file_path',
    'original_name',
    'is_verified',
])]
#[Table('vehicle_documents')]
class VehicleDocument extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
