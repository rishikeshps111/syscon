<?php

namespace App\Exports;

use App\Models\SalaryProcessingItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryReportExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(private array $report)
    {
    }

    public function array(): array
    {
        $earningNames = $this->componentNames('earning');
        $deductionNames = $this->componentNames('deduction');
        $identity = $this->identityHeadings();
        $earnings = [...$earningNames->all(), 'Incentive', 'Gross Earnings'];
        $deductions = [...$deductionNames->all(), 'Other Deduction', 'LOP', 'Total Deduction'];
        $payment = ['Net Salary Paid', 'Payment Method', 'Status', 'Approved By', 'Approved At', 'Remarks'];
        $rows = [
            [$this->report['monthName'] . ' ' . $this->report['year'] . ' - ATTENDANCE AND WAGE REGISTER'],
            ['SYSCON FUNCTIONAL NETWORKS PRIVATE LIMITED'],
            ['Depot: ' . ($this->report['depot']?->name ?? '-') . ' | Role: ' . $this->report['roleLabel']],
            array_merge(
                ['EMPLOYEE DETAILS'], array_fill(0, count($identity) - 1, ''),
                [''],
                ['EARNINGS'], array_fill(0, count($earnings) - 1, ''),
                [''],
                ['DEDUCTIONS'], array_fill(0, count($deductions) - 1, ''),
                [''],
                ['PAYMENT DETAILS'], array_fill(0, count($payment) - 1, ''),
            ),
            array_merge($identity, [''], $earnings, [''], $deductions, [''], $payment),
        ];

        foreach ($this->items()->values() as $index => $item) {
            $rows[] = $this->itemRow($item, $index, $earningNames, $deductionNames);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $identityCount = count($this->identityHeadings());
        $earningCount = $this->componentNames('earning')->count() + 2;
        $deductionCount = $this->componentNames('deduction')->count() + 3;
        $paymentCount = 6;
        $lastColumn = Coordinate::stringFromColumnIndex($identityCount + 1 + $earningCount + 1 + $deductionCount + 1 + $paymentCount);
        $lastRow = $sheet->getHighestDataRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");

        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('A9D18E');
        $sheet->getStyle("A2:{$lastColumn}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('BDD7EE');

        $start = 1;
        foreach ([
            [$identityCount, 1, '92D050'],
            [$earningCount, 1, 'BDD7EE'],
            [$deductionCount, 1, 'FFE699'],
            [$paymentCount, 0, '92D050'],
        ] as [$length, $spacing, $color]) {
            $end = $start + $length - 1;
            $from = Coordinate::stringFromColumnIndex($start);
            $to = Coordinate::stringFromColumnIndex($end);
            $sheet->mergeCells("{$from}4:{$to}4");
            $sheet->getStyle("{$from}4:{$to}5")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
            $start = $end + $spacing + 1;
        }

        $sheet->freezePane('A6');
        $sheet->setAutoFilter("A5:{$lastColumn}{$lastRow}");
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.25)->setRight(0.25)->setBottom(0.25)->setLeft(0.25);
        $sheet->getStyle("A1:{$lastColumn}5")->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(20);
        $sheet->getStyle('A2')->getFont()->setSize(18);
        $sheet->getStyle("A1:{$lastColumn}5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(25.8);
        $sheet->getRowDimension(2)->setRowHeight(23.4);
        $sheet->getRowDimension(5)->setRowHeight(43.2);
        $sheet->getStyle("A4:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('B7B7B7');
        $sheet->getStyle("A6:{$lastColumn}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ([$identityCount + 1, $identityCount + $earningCount + 2, $identityCount + $earningCount + $deductionCount + 3] as $spacerColumn) {
            $letter = Coordinate::stringFromColumnIndex($spacerColumn);
            $sheet->getColumnDimension($letter)->setAutoSize(false)->setWidth(3);
            $sheet->getStyle("{$letter}4:{$letter}{$lastRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF');
        }

        for ($column = $identityCount + 2; $column <= Coordinate::columnIndexFromString($lastColumn) - 5; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getStyle("{$letter}6:{$letter}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        return [];
    }

    private function identityHeadings(): array
    {
        return ['Sl No', 'Employee Code', 'Name', "Father's Name", 'DOJ', 'DOB', 'Aadhaar', 'PAN', 'Location', 'UAN', 'ESIC / WC', 'Bank Account Number', 'IFSC', 'Role', 'Designation', 'Days in Month', 'Shifts Worked', 'Leave Taken', 'Unauthorized Leave'];
    }

    private function itemRow(SalaryProcessingItem $item, int $index, Collection $earningNames, Collection $deductionNames): array
    {
        $role = $item->salaryProcessing?->role?->name ?: '-';
        $profile = $this->profile($item, $role);
        $components = collect($item->salary_split ?: [])->filter(fn ($component) => (bool) ($component['selected'] ?? true));
        $earnings = $components->where('type', 'earning')->keyBy('name');
        $deductions = $components->where('type', 'deduction')->keyBy('name');
        $deductionTotal = (float) $item->deduction + (float) $item->lop;

        return array_merge([
            $index + 1,
            $item->user?->code ?: '-',
            $item->user?->name ?: '-',
            $profile?->father_name ?: '-',
            ($profile?->date_of_joining ?? $profile?->joining_date)?->format('d-m-Y') ?: '-',
            $profile?->date_of_birth?->format('d-m-Y') ?: '-',
            $item->aadhaar_no ?: ($profile?->aadhaar_number ?: '-'),
            $profile?->pan_number ?: '-',
            $profile?->location?->name ?: '-',
            $profile?->uan ?: '-',
            $profile?->esic_wc ?: '-',
            $profile?->bank_account_number ?? $profile?->account_number ?? '-',
            $profile?->ifsc_code ?: '-',
            $role,
            $role === 'Staff' ? ($item->user?->staffProfile?->designation?->name ?: '-') : $role,
            31,
            31,
            (float) ($item->total_leave_taken ?? 0),
            (float) ($item->unauthorized_leaves ?? 0),
        ], [''],
            $earningNames->map(fn ($name) => (float) ($earnings->get($name)['amount'] ?? 0))->all(), [
                (float) $item->incentive,
                (float) $item->basic_salary + (float) $item->incentive,
            ], [''], $deductionNames->map(fn ($name) => (float) ($deductions->get($name)['amount'] ?? 0))->all(), [
                (float) $item->deduction,
                (float) $item->lop,
                $deductionTotal,
            ], [''], [
                (float) $item->net_salary,
                $item->salaryProcessing?->payment_method ?: '-',
                $item->salaryProcessing?->status ?: '-',
                $item->salaryProcessing?->approver?->name ?: '-',
                $item->salaryProcessing?->approved_at?->format('d-m-Y h:i A') ?: '-',
                $item->salaryProcessing?->remarks ?: '-',
            ]);
    }

    private function profile(SalaryProcessingItem $item, string $role): mixed
    {
        return match ($role) {
            'Driver' => $item->user?->driverProfile,
            'Housekeeping' => $item->user?->housekeepingProfile,
            'Controller' => $item->user?->controllerProfile,
            'Supervisor' => $item->user?->supervisorProfile,
            default => $item->user?->staffProfile,
        };
    }

    private function componentNames(string $type): Collection
    {
        return $this->items()->flatMap(fn (SalaryProcessingItem $item) => collect($item->salary_split ?: []))
            ->filter(fn ($component) => ($component['type'] ?? null) === $type && (bool) ($component['selected'] ?? true))
            ->pluck('name')->filter()->unique()->sort()->values();
    }

    private function items(): Collection
    {
        return $this->report['items'];
    }
}
