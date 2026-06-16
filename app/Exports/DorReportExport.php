<?php

namespace App\Exports;

use App\Http\Controllers\TripController;
use App\Models\TripSheetEntryDor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DorReportExport implements FromCollection, WithHeadings
{
    public function __construct(private $query)
    {
    }

    public function collection(): Collection
    {
        return $this->query->get()->values()->map(function (TripSheetEntryDor $dor, int $index) {
            $entry = $dor->tripSheetEntry;
            $assignment = $entry ? TripController::assignmentForCompletedEntry($entry) : null;
            $trip = $entry?->sheet?->trip;
            $driver = $entry?->driverProfile ?: $assignment?->driverProfile;
            $vehicle = $entry?->vehicle ?: $assignment?->vehicle;

            return [
                'SL No' => $index + 1,
                'Trip Sheet Code' => $entry?->sheet?->code,
                'Date' => $entry?->sheet?->date?->format('d-m-Y') ?: $dor->dor_date?->format('d-m-Y'),
                'Side' => ucfirst((string) ($entry?->side ?: $dor->shift)),
                'Depot Name' => $dor->depot_name ?: $trip?->depot?->name,
                'Vehicle No' => $dor->bus_no ?: $vehicle?->vehicle_no,
                'Driver' => $driver?->user?->name ?: $dor->driver_badge_no,
                'DOR ID' => $dor->id,
                'Trip Sheet Entry ID' => $dor->trip_sheet_entry_id,
                'DOR Depot Name' => $dor->depot_name,
                'DOR Date' => $dor->dor_date?->format('d-m-Y'),
                'Bus No' => $dor->bus_no,
                'Route No' => $dor->route_no,
                'Duty' => $dor->duty,
                'Shift' => $dor->shift,
                'Driver Badge No' => $dor->driver_badge_no,
                'Schedule Start Time' => $this->time($dor->schedule_start_time),
                'Schedule End Time' => $this->time($dor->schedule_end_time),
                'Actual Start Time' => $this->time($dor->actual_start_time),
                'Actual End Time' => $this->time($dor->actual_end_time),
                'Start Punc' => $dor->start_punc,
                'Route Completion Time' => $this->time($dor->route_completion_time),
                'Schedule Km' => $dor->schedule_km,
                'Route Km Loss' => $dor->route_km_loss,
                'Actual Route Km' => $dor->actual_route_km,
                'Schedule Trip' => $dor->schedule_trip,
                'Actual Trip' => $dor->actual_trip,
                'Miss Trip' => $dor->miss_trip,
                'Odometer Start Reading' => $dor->odometer_start_reading,
                'Odometer Start Image Path' => $dor->odometer_start_image_path,
                'Odometer End Reading' => $dor->odometer_end_reading,
                'Odometer End Image Path' => $dor->odometer_end_image_path,
                'Odometer Diff Km' => $dor->odometer_diff_km,
                'Difference' => $dor->difference,
                'DOR Account Responsible ID' => $dor->dor_account_responsible_id,
                'Account Responsible' => $dor->account_responsible,
                'DOR Kilometer Loss Reason ID' => $dor->dor_kilometer_loss_reason_id,
                'Reason For Kilometer Loss' => $dor->reason_for_kilometer_loss,
                'After Sales Reason' => $dor->after_sales_reason,
                'Penalty Infraction' => $dor->penalty_infraction,
                'Remarks' => $dor->remarks,
                'Route Start SOC Percent' => $dor->route_start_soc_percent,
                'Route End SOC Percent' => $dor->route_end_soc_percent,
                'SOC Consumption On Route Percent' => $dor->soc_consumption_on_route_percent,
                'SOC Per KM' => $dor->soc_per_km,
                'Run Kilometer Per SOC' => $dor->run_kilometer_per_soc,
                'DOR KWh Per KM Odo' => $dor->dor_kwh_per_km_odo,
                'DOR KWh Per KM Act' => $dor->dor_kwh_per_km_act,
                'DOR KWh' => $dor->dor_kwh,
                'DCR KWh Per KM Odo' => $dor->dcr_kwh_per_km_odo,
                'DCR KWh Per KM Act' => $dor->dcr_kwh_per_km_act,
                'DCR KWh' => $dor->dcr_kwh,
                'DCR Charged SOC' => $dor->dcr_charged_soc,
                'Energy Absorption' => $dor->energy_absorption,
                'Battery Size KWh' => $dor->battery_size_kwh,
                'VP1' => $dor->vp1,
                'VP2' => $dor->vp2,
                'DP' => $dor->dp,
                'Penalty' => $dor->penalty,
                'Model 9M/12M' => $dor->model_9m_12m,
                'Is Completed' => $dor->is_completed ? 'Yes' : 'No',
                'Created By' => $dor->created_by,
                'Updated By' => $dor->updated_by,
                'Created At' => $dor->created_at?->format('d-m-Y H:i:s'),
                'Updated At' => $dor->updated_at?->format('d-m-Y H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL No',
            'Trip Sheet Code',
            'Date',
            'Side',
            'Depot Name',
            'Vehicle No',
            'Driver',
            'DOR ID',
            'Trip Sheet Entry ID',
            'DOR Depot Name',
            'DOR Date',
            'Bus No',
            'Route No',
            'Duty',
            'Shift',
            'Driver Badge No',
            'Schedule Start Time',
            'Schedule End Time',
            'Actual Start Time',
            'Actual End Time',
            'Start Punc',
            'Route Completion Time',
            'Schedule Km',
            'Route Km Loss',
            'Actual Route Km',
            'Schedule Trip',
            'Actual Trip',
            'Miss Trip',
            'Odometer Start Reading',
            'Odometer Start Image Path',
            'Odometer End Reading',
            'Odometer End Image Path',
            'Odometer Diff Km',
            'Difference',
            'DOR Account Responsible ID',
            'Account Responsible',
            'DOR Kilometer Loss Reason ID',
            'Reason For Kilometer Loss',
            'After Sales Reason',
            'Penalty Infraction',
            'Remarks',
            'Route Start SOC Percent',
            'Route End SOC Percent',
            'SOC Consumption On Route Percent',
            'SOC Per KM',
            'Run Kilometer Per SOC',
            'DOR KWh Per KM Odo',
            'DOR KWh Per KM Act',
            'DOR KWh',
            'DCR KWh Per KM Odo',
            'DCR KWh Per KM Act',
            'DCR KWh',
            'DCR Charged SOC',
            'Energy Absorption',
            'Battery Size KWh',
            'VP1',
            'VP2',
            'DP',
            'Penalty',
            'Model 9M/12M',
            'Is Completed',
            'Created By',
            'Updated By',
            'Created At',
            'Updated At',
        ];
    }

    private function time(?string $value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }
}
