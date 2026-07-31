<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    use HasFactory;

    public const PREFIX_MODULE = 'Salary Component Module';

    protected $fillable = [
        'code',
        'component_name',
        'type',
        'is_applicable',
        'calculation_type',
        'default_value',
        'is_editable_in_payroll',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'is_applicable' => 'boolean',
            'is_editable_in_payroll' => 'boolean',
            'is_mandatory' => 'boolean',
            'default_value' => 'decimal:2',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SalaryComponentAssignment::class);
    }

    public function templateItems(): HasMany
    {
        return $this->hasMany(SalaryTemplateItem::class);
    }
}
