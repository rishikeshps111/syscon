<?php

namespace App\Exports;

use App\Models\SalaryProcessingItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    private ?Collection $items = null;

    private ?Collection $componentNames = null;

    public function __construct(private $query)
    {
    }

    public function collection(): Collection
    {
        return $this->items()->values()->map(function (SalaryProcessingItem $item, int $index) {
            $processing = $item->salaryProcessing;
            $components = collect($item->salary_split ?: [])
                ->where('type', 'earning')
                ->keyBy('name');

            return array_merge([
                $index + 1,
                $item->user?->code,
                $item->user?->name,
                $item->aadhaar_no,
                $processing->role?->name,
                $processing->depot?->name,
                Carbon::create(null, $processing->month, 1)->format('F'),
                $processing->year,
                $processing->salary_date?->format('d-m-Y'),
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
                $processing->payment_method,
                $processing->status,
                $processing->approver?->name,
                $processing->approved_at?->format('d-m-Y h:i A'),
                $processing->remarks,
            ]);
        });
    }

    public function headings(): array
    {
        return array_merge([
            'SL No',
            'User Code',
            'User Name',
            'Aadhaar No',
            'User Type',
            'Depo',
            'Month',
            'Year',
            'Salary Date',
            'Total Leave Taken',
            'Unauthorized Leaves',
            'Total Shifts Completed',
            'Total Working Days',
        ], $this->componentNames()->all(), [
            'Gross Salary',
            'Incentive (Included)',
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

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function items(): Collection
    {
        return $this->items ??= (clone $this->query)->get();
    }

    private function componentNames(): Collection
    {
        return $this->componentNames ??= $this->items()
            ->flatMap(fn (SalaryProcessingItem $item) => collect($item->salary_split ?: [])->where('type', 'earning')->pluck('name'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
