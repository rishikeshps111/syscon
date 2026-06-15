<?php

namespace App\Exports;

use App\Http\Controllers\TripController;
use App\Models\TripSheetEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TripReportExport implements FromCollection, WithHeadings, WithEvents, WithTitle, WithColumnFormatting
{
    public function __construct(private $query)
    {
    }

    public function collection(): Collection
    {
        return $this->query->get()->values()->map(function (TripSheetEntry $entry, int $index) {
            return $this->row($entry, $index + 1);
        });
    }

    public function headings(): array
    {
        return [
            'S No.',
            'Depot Name',
            "Date\n(dd-mm-yyyy)",
            'Bus No. (With full registration)',
            'Route No',
            'Duty',
            'Shift',
            'Driver ID/Badge No.',
            "Schedule Start Time\n(hh:mm)",
            "Schedule End Time\n(hh:mm)",
            "Actual Start Time\n(hh:mm)",
            "Actual End Time\n(hh:mm)",
            'Start Punc.',
            "Route Completion Time\n(hh:mm)",
            'Schedule Km',
            'Route Km Loss',
            'Act. Route Km ',
            'Schedule Trip',
            'Actual Trip',
            'Miss Trip',
            'Odometer Start Reading (A)',
            'Odometer End Reading (B)',
            "Odometer  Diff. Km\n(B-A)",
            'Diffrence',
            'Account Responsible',
            'Reason For Kilometer Loss',
            'After Sales Reason',
            'Penalty Infraction',
            'Remarks',
            "Route Start SOC %\n(C)",
            "Route End SOC %\n(D)",
            "SOC Consumption On Route %\n(C-D)",
            "SOC \nPer KM",
            "Run Kilometer \nPer \nSOC",
            "DOR\nKWh/km (odo)",
            'DOR KWH/KM (ACT)',
            'DCR KWh/km (odo)',
            'DCR KWH/KM (ACT)',
            'DOR KWH',
            'DCR KWH',
            'DCR Charged SOC',
            'Energy Absorption',
            'Battery size In KWH',
            'VP1',
            'VP2',
            'DP',
            'Penalty',
            'Model 9M/12M',
        ];
    }

    public function title(): string
    {
        return 'DOR';
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach (range(1, 48) as $index) {
            $formats[Coordinate::stringFromColumnIndex($index)] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = max(1, $sheet->getHighestDataRow());
                $highestColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $range = "A1:{$highestColumn}{$highestRow}";

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
                $sheet->freezePane('H2');
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");
                $sheet->getRowDimension(1)->setRowHeight(43.5);
                $sheet->getStyle($range)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FF000000');
                $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setName('Calibri')->setBold(true);

                foreach ($this->headerStyleMap() as $column => $style) {
                    $cell = "{$column}1";
                    $sheet->getStyle($cell)->getFont()
                        ->setSize($style['font_size'])
                        ->getColor()->setARGB($style['font_color']);
                    $sheet->getStyle($cell)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($style['fill']);
                }

                if ($highestRow > 1) {
                    foreach (range(2, $highestRow) as $row) {
                        $sheet->getRowDimension($row)->setRowHeight(15);
                    }
                }

                $sheet->setSelectedCell('A1');
            },
        ];
    }

    private function row(TripSheetEntry $entry, int $serial): array
    {
        $dor = $entry->dor;
        $trip = $entry->sheet?->trip;
        $route = $trip?->route;
        $assignment = TripController::assignmentForCompletedEntry($entry);
        $vehicle = $entry->vehicle ?: $assignment?->vehicle;
        $driver = $entry->driverProfile ?: $assignment?->driverProfile;

        return [
            $serial,
            $this->cell($dor?->depot_name ?: $trip?->depot?->name),
            $this->cell($dor?->dor_date?->format('d-m-Y') ?: $entry->sheet?->date?->format('d-m-Y')),
            $this->cell($dor?->bus_no ?: $vehicle?->vehicle_no),
            $this->cell($dor?->route_no ?: $route?->route_code),
            $this->cell($dor?->duty ?: $trip?->trip_title),
            $this->cell($dor?->shift ?: Str::title((string) $entry->side)),
            $this->cell($dor?->driver_badge_no ?: $driver?->badge_number ?: $driver?->user?->code),
            $this->cell($dor?->schedule_start_time ?: $this->time($entry->departure_time)),
            $this->cell($dor?->schedule_end_time ?: $this->time($entry->arrival_time)),
            $this->cell($dor?->actual_start_time ?: $this->time($entry->actual_start_time)),
            $this->cell($dor?->actual_end_time ?: $this->time($entry->actual_reach_time)),
            $this->cell($dor?->start_punc ?: $this->startDelay($entry->departure_time, $entry->actual_start_time)),
            $this->cell($dor?->route_completion_time ?: $this->time($entry->actual_reach_time ?: $entry->arrival_time)),
            $this->cell($dor?->schedule_km ?: $trip?->schedule_km ?: $route?->distance),
            $this->cell($dor?->route_km_loss),
            $this->cell($dor?->actual_route_km),
            $this->cell($dor?->schedule_trip ?: 1),
            $this->cell($dor?->actual_trip),
            $this->cell($dor?->miss_trip),
            $this->cell($dor?->odometer_start_reading),
            $this->cell($dor?->odometer_end_reading),
            $this->cell($dor?->odometer_diff_km),
            $this->cell($dor?->difference),
            $this->cell($dor?->account_responsible),
            $this->cell($dor?->reason_for_kilometer_loss),
            $this->cell($dor?->after_sales_reason),
            $this->cell($dor?->penalty_infraction),
            $this->cell($dor?->remarks ?: $entry->notes),
            $this->cell($dor?->route_start_soc_percent ?: $entry->starting_electric_charge),
            $this->cell($dor?->route_end_soc_percent),
            $this->cell($dor?->soc_consumption_on_route_percent),
            $this->cell($dor?->soc_per_km),
            $this->cell($dor?->run_kilometer_per_soc),
            $this->cell($dor?->dor_kwh_per_km_odo),
            $this->cell($dor?->dor_kwh_per_km_act),
            $this->cell($dor?->dcr_kwh_per_km_odo),
            $this->cell($dor?->dcr_kwh_per_km_act),
            $this->cell($dor?->dor_kwh),
            $this->cell($dor?->dcr_kwh),
            $this->cell($dor?->dcr_charged_soc),
            $this->cell($dor?->energy_absorption),
            $this->cell($dor?->battery_size_kwh ?: $vehicle?->battery_capacity),
            $this->cell($dor?->vp1),
            $this->cell($dor?->vp2),
            $this->cell($dor?->dp),
            $this->cell($dor?->penalty),
            $this->cell($dor?->model_9m_12m ?: $vehicle?->model),
        ];
    }

    private function headerStyleMap(): array
    {
        $styles = [];

        foreach (range(1, 48) as $index) {
            $styles[Coordinate::stringFromColumnIndex($index)] = [
                'fill' => 'FFFFFF66',
                'font_color' => 'FF000000',
                'font_size' => 10,
            ];
        }

        foreach (['N', 'O', 'P', 'Q', 'R', 'S', 'T', 'W', 'X', 'AF', 'AG', 'AH'] as $column) {
            $styles[$column]['fill'] = 'FFE46C0A';
        }

        foreach (['N', 'P', 'Q', 'S', 'W', 'X', 'AF', 'AG', 'AH'] as $column) {
            $styles[$column]['font_color'] = 'FFFFFFFF';
        }

        foreach (['R', 'S', 'T'] as $column) {
            $styles[$column]['font_size'] = 8;
        }

        foreach (['AI', 'AK'] as $column) {
            $styles[$column]['fill'] = 'FFFFCC66';
        }

        foreach (['AJ', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV'] as $column) {
            $styles[$column]['fill'] = 'FFFFFF00';
        }

        return $styles;
    }

    private function cell(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) $value;
    }

    private function time(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '';
    }

    private function startDelay(?string $startTime, ?string $actualStartTime): string
    {
        if (! $startTime || ! $actualStartTime) {
            return '';
        }

        try {
            $start = Carbon::createFromFormat('H:i:s', strlen($startTime) === 5 ? $startTime . ':00' : $startTime);
            $actual = Carbon::createFromFormat('H:i:s', strlen($actualStartTime) === 5 ? $actualStartTime . ':00' : $actualStartTime);
        } catch (\Throwable) {
            return '';
        }

        if ($actual->lt($start)) {
            $actual->addDay();
        }

        return (string) $start->diffInMinutes($actual);
    }
}
