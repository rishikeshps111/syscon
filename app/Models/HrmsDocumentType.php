<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'category',
    'applicable_for',
    'allowed_file_types',
    'is_active',
    'is_mandatory',
    'is_expiry_required',
    'description',
])]
#[Table('hrms_document_types')]
class HrmsDocumentType extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Identity Proof',
        'Address Proof',
        'Financial',
        'Educational',
        'Legal',
    ];

    public const APPLICABLE_FOR = [
        'all' => 'All',
        'driver' => 'Driver',
        'controller' => 'Controller',
        'supervisor' => 'Supervisor',
        'staff' => 'Staff'
    ];

    public const ALLOWED_FILE_TYPES = [
        'pdf' => 'PDF',
        'jpg' => 'JPG',
        'png' => 'PNG',
        'doc' => 'DOC',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_mandatory' => 'boolean',
            'is_expiry_required' => 'boolean',
        ];
    }

    public function staffDocuments(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function controllerDocuments(): HasMany
    {
        return $this->hasMany(ControllerDocument::class);
    }

    public function supervisorDocuments(): HasMany
    {
        return $this->hasMany(SupervisorDocument::class);
    }
}
