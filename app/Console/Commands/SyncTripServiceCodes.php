<?php

namespace App\Console\Commands;

use App\Models\{Trip, User};
use App\Services\TripServiceCodes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncTripServiceCodes extends Command
{
    protected $signature = 'trips:sync-service-codes {trip? : Exact trip code; omit for all trips} {--actor= : Active Super Admin user ID required when applying} {--apply : Apply corrections; otherwise preview only}';

    protected $description = 'Preview or correct numbered SER prefixes on eligible pending trip-sheet entries';

    public function handle(TripServiceCodes $codes): int
    {
        $actor = $this->option('actor') ? User::find($this->option('actor')) : null;
        if (($this->option('apply') || $this->option('actor')) && (! $actor || ! $actor->is_active || ! $actor->hasRole('Super Admin'))) {
            $this->error('Provide --actor with an active Super Admin user ID to apply corrections.');
            return self::FAILURE;
        }
        $query = Trip::query()->when($this->argument('trip'), fn ($q, $code) => $q->where('code', $code));
        if (! (clone $query)->exists()) {
            $this->error('No matching trips found.');
            return self::FAILURE;
        }
        $failed = false;
        $this->info($this->option('apply') ? 'Applying eligible SER corrections.' : 'Preview only; no records will change.');
        $query->chunkById(100, function ($trips) use ($codes, $actor, &$failed) {
            foreach ($trips as $trip) {
                try {
                    $summary = DB::transaction(function () use ($trip, $codes, $actor) {
                        $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
                        $summary = $codes->synchronize($trip, null, (bool) $this->option('apply'));
                        if ($this->option('apply') && $summary['updated']) $trip->update(['updated_by' => $actor->id]);
                        return $summary;
                    }, 3);
                    $this->line($trip->code.': '.json_encode($summary));
                } catch (ValidationException $exception) {
                    $failed = true;
                    $this->error($trip->code.': '.implode(' ', \Illuminate\Support\Arr::flatten($exception->errors())));
                }
            }
        });
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
