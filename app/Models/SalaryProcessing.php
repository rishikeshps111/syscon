<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class SalaryProcessing extends Model
{
    use HasFactory;

    public const PAYMENT_METHODS = [
        'Cheque' => 'Cheque',
        'Treasury' => 'Treasury',
        'Bank Transfer' => 'Bank Transfer',
    ];

    protected $fillable = [
        'year',
        'month',
        'depot_id',
        'role_id',
        'salary_date',
        'payment_method',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'depot_id' => 'integer',
            'role_id' => 'integer',
            'salary_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalaryProcessingItem::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
