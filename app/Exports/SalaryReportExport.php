<?php

namespace App\Exports;

use App\Models\SalaryProcessingItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryReportExport implements FromCollection, ShouldAutoSize, WithStyles
{
    public function __construct(private array $report)
    {
    }

    public function collection(): Collection
    {
        return collect([
            ['Salary Report'],
            ['Year', $this->report['year']],
            ['Month', $this->report['monthName']],
            ['Depo', $this->report['depot']?->name ?? '-'],
            ['Role', $this->report['roleLabel']],
            [],
            $this->headings(),
        ])->merge($this->rows());
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:D1');
        $sheet->freezePane('A8');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A7:' . $sheet->getHighestColumn() . '7')->getFont()->setBold(true);
        $sheet->setAutoFilter('A7:' . $sheet->getHighestColumn() . max(7, $sheet->getHighestDataRow()));

        return [];
    }

    private function headings(): array
    {
        $identityColumns = [
            'SL No',
            'User Code',
            'User Name',
        ];

        if ($this->report['showRoleColumns']) {
            $identityColumns = array_merge($identityColumns, ['Role', 'Designation']);
        }

        return array_merge($identityColumns, [
            'Aadhaar No',
            'Total Leave Taken',
            'Unauthorized Leaves',
            'Total Shifts Completed',
            'Total Working Days',
        ], $this->componentNames()->all(), [
            'Gross Salary',
            'Incentive',
            'Deduction',
            'LOP',
            'Net Salary',
            'Payment Method',
            'Status',
            'Approved By',
            'Approved At',
            'Remarks',
        ]);
    }

    private function rows(): Collection
    {
        return $this->items()->values()->map(function (SalaryProcessingItem $item, int $index) {
            $processing = $item->salaryProcessing;
            $components = collect($item->salary_split ?: [])->where('type', 'earning')->keyBy('name');

            $identityValues = [
                $index + 1,
                $item->user?->code,
                $item->user?->name,
            ];

            if ($this->report['showRoleColumns']) {
                $roleName = $processing?->role?->name ?: '-';
                $identityValues = array_merge($identityValues, [
                    $roleName,
                    $roleName === 'Staff' ? ($item->user?->staffProfile?->designation?->name ?: '-') : '-',
                ]);
            }

            return array_merge($identityValues, [
                $item->aadhaar_no,
                (float) $item->total_leave_taken,
                (float) $item->unauthorized_leaves,
                $item->total_shifts_completed,
                $item->total_working_days,
            ], $this->componentNames()->map(
                fn (string $name) => (float) ($components->get($name)['amount'] ?? 0)
            )->all(), [
                (float) $item->basic_salary,
                (float) $item->incentive,
                (float) $item->deduction,
                (float) $item->lop,
                (float) $item->net_salary,
                $processing?->payment_method,
                $processing?->status,
                $processing?->approver?->name,
                $processing?->approved_at?->format('d-m-Y h:i A'),
                $processing?->remarks,
            ]);
        });
    }

    private function items(): Collection
    {
        return $this->report['items'];
    }

    private function componentNames(): Collection
    {
        return $this->report['componentNames'];
    }
}
