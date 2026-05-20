<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'code',
    'complaint_date',
    'reported_by_role',
    'reported_by_user_id',
    'against_role',
    'against_user_id',
    'complaint_category_id',
    'description',
    'attachment_paths',
    'severity',
    'status',
    'assigned_to',
    'action_taken',
    'action_date',
    'remarks',
])]
#[Table('complaints')]
class Complaint extends Model
{
    use HasFactory;

    public const REPORTED_BY_ROLES = [
        'controller' => 'Controller',
        'supervisor' => 'Supervisor',
    ];

    public const AGAINST_ROLES = [
        'driver' => 'Driver',
        'controller' => 'Controller',
    ];

    public const SEVERITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'action_taken' => 'Action Taken',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
    ];

    public const ASSIGNED_TO_OPTIONS = [
        'admin' => 'Admin',
        'hr' => 'HR',
        'manager' => 'Manager',
    ];

    public const ACTION_TAKEN_OPTIONS = [
        'warning' => 'Warning',
        'suspension' => 'Suspension',
        'fine' => 'Fine',
    ];

    protected function casts(): array
    {
        return [
            'complaint_date' => 'date',
            'action_date' => 'date',
            'attachment_paths' => 'array',
        ];
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function againstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'complaint_category_id');
    }

    public function getReportedByRoleLabelAttribute(): string
    {
        return self::REPORTED_BY_ROLES[$this->reported_by_role] ?? '';
    }

    public function getAgainstRoleLabelAttribute(): string
    {
        return self::AGAINST_ROLES[$this->against_role] ?? '';
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getAssignedToLabelAttribute(): string
    {
        return self::ASSIGNED_TO_OPTIONS[$this->assigned_to] ?? '';
    }

    public function getActionTakenLabelAttribute(): string
    {
        return self::ACTION_TAKEN_OPTIONS[$this->action_taken] ?? '';
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        $firstAttachment = collect($this->attachment_paths)->first();

        return $firstAttachment ? Storage::disk('public')->url($firstAttachment) : null;
    }

    public function getAttachmentUrlsAttribute(): array
    {
        return collect($this->attachment_paths)
            ->filter()
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->values()
            ->all();
    }
}
