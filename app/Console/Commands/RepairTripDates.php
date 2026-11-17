<?php

namespace App\Console\Commands;

use App\Models\{Trip, User};
use App\Services\TripDateRescheduler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class RepairTripDates extends Command
{
    protected $signature = 'trips:repair-dates {trip : Exact trip code} {--from= : Original from date (YYYY-MM-DD)} {--to= : Original to date (YYYY-MM-DD)} {--actor= : Administrator user ID, required to apply} {--apply : Apply the previewed repair}';

    protected $description = 'Preview or repair trip sheets left on original dates after a trip date edit';

    public function handle(TripDateRescheduler $rescheduler): int
    {
        foreach (['from', 'to'] as $option) {
            $value = $this->option($option);
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
                || ! Carbon::hasFormatWithModifiers($value, 'Y-m-d') || ! Carbon::canBeCreatedFromFormat($value, 'Y-m-d')
                || Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d') !== $value) {
                $this->error('--'.$option.' must be a valid date in YYYY-MM-DD format.');
                return self::FAILURE;
            }
        }
        $trip = Trip::where('code', $this->argument('trip'))->first();
        if (! $trip) {
            $this->error('Trip code was not found.');
            return self::FAILURE;
        }
        $actor = $this->option('actor') ? User::find($this->option('actor')) : null;
        if (($this->option('apply') || $this->option('actor')) && (! $actor || ! $actor->is_active || ! $actor->hasRole('Super Admin'))) {
            $this->error('Provide --actor with an active Super Admin user ID to apply a repair.');
            return self::FAILURE;
        }
        $this->line($trip->code.': '.$this->option('from').' to '.$this->option('to')
            .' -> '.$trip->from_date?->toDateString().' to '.$trip->to_date?->toDateString());
        try {
            $summary = $rescheduler->repair($trip, $this->option('from'), $this->option('to'), $actor?->id, (bool) $this->option('apply'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) foreach ($messages as $message) $this->error($message);
            return self::FAILURE;
        }
        $this->info(($this->option('apply') ? 'Applied: ' : 'Preview only (no changes saved): ').json_encode($summary));
        return self::SUCCESS;
    }
}
