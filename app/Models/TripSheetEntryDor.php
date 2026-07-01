<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'trip_sheet_entry_id',
    'depot_name',
    'dor_date',
    'bus_no',
    'route_no',
    'duty',
    'shift',
    'driver_badge_no',
    'schedule_start_time',
    'schedule_end_time',
    'actual_start_time',
    'actual_end_time',
    'start_punc',
    'route_completion_time',
    'schedule_km',
    'route_km_loss',
    'actual_route_km',
    'schedule_trip',
    'actual_trip',
    'miss_trip',
    'odometer_start_reading',
    'odometer_start_image_path',
    'odometer_end_reading',
    'odometer_end_image_path',
    'odometer_diff_km',
    'difference',
    'dor_account_responsible_id',
    'account_responsible',
    'dor_kilometer_loss_reason_id',
    'reason_for_kilometer_loss',
    'after_sales_reason',
    'penalty_infraction',
    'remarks',
    'route_start_soc_percent',
    'route_start_soc_percent_image',
    'route_end_soc_percent',
    'route_end_soc_percent_image',
    'soc_consumption_on_route_percent',
    'soc_per_km',
    'run_kilometer_per_soc',
    'dor_kwh_per_km_odo',
    'dor_kwh_per_km_act',
    'dor_kwh',
    'dcr_kwh_per_km_odo',
    'dcr_kwh_per_km_act',
    'dcr_kwh',
    'dcr_charged_soc',
    'energy_absorption',
    'battery_size_kwh',
    'vp1',
    'vp2',
    'dp',
    'penalty',
    'model_9m_12m',
    'is_completed',
    'created_by',
    'updated_by',
])]
#[Table('trip_sheet_entry_dors')]
class TripSheetEntryDor extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (self $dor): void {
            Storage::disk('public')->delete(array_filter([
                $dor->odometer_start_image_path,
                $dor->odometer_end_image_path,
                $dor->route_start_soc_percent_image,
                $dor->route_end_soc_percent_image,
            ]));
        });
    }

    protected function casts(): array
    {
        return [
            'dor_date' => 'date',
            'schedule_km' => 'decimal:2',
            'route_km_loss' => 'decimal:2',
            'actual_route_km' => 'decimal:2',
            'odometer_start_reading' => 'decimal:2',
            'odometer_end_reading' => 'decimal:2',
            'odometer_diff_km' => 'decimal:2',
            'difference' => 'decimal:2',
            'dor_account_responsible_id' => 'integer',
            'dor_kilometer_loss_reason_id' => 'integer',
            'route_start_soc_percent' => 'decimal:2',
            'route_end_soc_percent' => 'decimal:2',
            'soc_consumption_on_route_percent' => 'decimal:2',
            'soc_per_km' => 'decimal:4',
            'run_kilometer_per_soc' => 'decimal:4',
            'dor_kwh_per_km_odo' => 'decimal:4',
            'dor_kwh_per_km_act' => 'decimal:4',
            'dor_kwh' => 'decimal:2',
            'dcr_kwh_per_km_odo' => 'decimal:4',
            'dcr_kwh_per_km_act' => 'decimal:4',
            'dcr_kwh' => 'decimal:2',
            'dcr_charged_soc' => 'decimal:2',
            'energy_absorption' => 'decimal:4',
            'battery_size_kwh' => 'decimal:2',
            'vp1' => 'decimal:4',
            'vp2' => 'decimal:4',
            'dp' => 'decimal:4',
            'penalty' => 'decimal:2',
            'is_completed' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function tripSheetEntry(): BelongsTo
    {
        return $this->belongsTo(TripSheetEntry::class);
    }

    public function accountResponsible(): BelongsTo
    {
        return $this->belongsTo(DorAccountResponsible::class, 'dor_account_responsible_id');
    }

    public function kilometerLossReason(): BelongsTo
    {
        return $this->belongsTo(DorKilometerLossReason::class, 'dor_kilometer_loss_reason_id');
    }
}
