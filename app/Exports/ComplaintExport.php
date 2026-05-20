<?php

namespace App\Exports;

use App\Models\Complaint;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComplaintExport implements FromCollection, WithHeadings
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?: Complaint::with(['reportedBy', 'againstUser', 'category']);
    }

    public function collection()
    {
        return $this->query->get()->map(function (Complaint $complaint) {
            return [
                'Complaint ID' => $complaint->code,
                'Date' => $complaint->complaint_date?->format('d M Y'),
                'Reported By Role' => $complaint->reported_by_role_label,
                'Reported By' => trim(($complaint->reportedBy?->code ? $complaint->reportedBy->code . ' - ' : '') . ($complaint->reportedBy?->name ?? '')),
                'Against Role' => $complaint->against_role_label,
                'Against' => trim(($complaint->againstUser?->code ? $complaint->againstUser->code . ' - ' : '') . ($complaint->againstUser?->name ?? '')),
                'Category' => $complaint->category?->name,
                'Severity' => $complaint->severity_label,
                'Status' => $complaint->status_label,
                'Assigned To' => $complaint->assigned_to_label,
                'Action Taken' => $complaint->action_taken_label,
                'Action Date' => $complaint->action_date?->format('d M Y'),
                'Attachments' => count($complaint->attachment_paths ?? []),
                'Remarks' => $complaint->remarks,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Complaint ID',
            'Date',
            'Reported By Role',
            'Reported By',
            'Against Role',
            'Against',
            'Category',
            'Severity',
            'Status',
            'Assigned To',
            'Action Taken',
            'Action Date',
            'Attachments',
            'Remarks',
        ];
    }
}
