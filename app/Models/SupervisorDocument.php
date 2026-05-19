<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'hrms_document_type_id',
    'expiry_date',
    'file_path',
    'original_name',
    'is_verified',
])]
#[Table('supervisor_documents')]
class SupervisorDocument extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(HrmsDocumentType::class, 'hrms_document_type_id');
    }
}

