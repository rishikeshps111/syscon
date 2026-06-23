<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryProcessingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_processing_id',
        'user_id',
        'aadhaar_no',
        'total_leave_taken',
        'total_shifts_completed',
        'total_working_days',
        'lop',
        'basic_salary',
        'deduction',
        'incentive',
        'unauthorized_leaves',
        'net_salary',
        'salary_split',
    ];

    protected function casts(): array
    {
        return [
            'total_leave_taken' => 'decimal:2',
            'total_shifts_completed' => 'integer',
            'total_working_days' => 'integer',
            'lop' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'deduction' => 'decimal:2',
            'incentive' => 'decimal:2',
            'unauthorized_leaves' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'salary_split' => 'array',
        ];
    }

    public function salaryProcessing(): BelongsTo
    {
        return $this->belongsTo(SalaryProcessing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
