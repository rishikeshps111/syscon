<?php

namespace App\Services;

use App\Models\{Roster, Trip, TripSheetEntry};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripServiceCodes
{
    public function prefix(Trip $trip): string
    {
        $trip->loadMissing('serviceCode');
        $prefix = trim((string) $trip->serviceCode?->title);
        if ($prefix === '') {
            throw ValidationException::withMessages(['service_code_id' => 'Select a trip service code before generating or importing duties.']);
        }
        return $prefix;
    }

    public function format(Trip $trip, int $number): string
    {
        if ($number < 1) throw ValidationException::withMessages(['service_code_id' => 'SER numbering must start at one.']);
        return $this->join($this->prefix($trip), str_pad((string) $number, 2, '0', STR_PAD_LEFT));
    }

    public function copiedCode(Trip $trip, TripSheetEntry $source, ?string $oldPrefix = null): string
    {
        $prefix = $this->prefix($trip);
        $suffix = $this->suffix((string) $source->service_code, [$prefix, $oldPrefix, 'ED']);
        if ($suffix !== null) return $this->join($prefix, $suffix);
        $sequence = (int) $source->trip_order_sequence_no;
        if ($sequence < 1) {
            throw ValidationException::withMessages(['service_code_id' => 'The saved schedule has an unrecognized SER code and no service sequence. Correct it before generating new dates.']);
        }
        return $this->format($trip, intdiv($sequence - 1, max(1, (int) $trip->rounds_per_trip)) + 1);
    }

    public function synchronize(Trip $trip, ?string $oldPrefix = null, bool $apply = true): array
    {
        return DB::transaction(function () use ($trip, $oldPrefix, $apply) {
            Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $prefix = $this->prefix($trip);
            $sheets = $trip->sheets()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $entries = TripSheetEntry::whereIn('trip_sheet_id', $sheets->keys())->orderBy('id')->lockForUpdate()->get();
            $summary = ['updated' => 0, 'protected' => 0, 'unrecognized' => 0];
            foreach ($entries as $entry) {
                $code = (string) $entry->service_code;
                $suffix = $this->suffix($code, [$prefix, $oldPrefix, 'ED']);
                if ($suffix === null) {
                    $summary['unrecognized']++;
                    continue;
                }
                $target = $this->join($prefix, $suffix);
                if ($target === $code) continue;
                $rosters = $entry->rosters()->orderBy('rosters.id')->lockForUpdate()->get();
                $protected = $entry->status !== 'pending' || $sheets[$entry->trip_sheet_id]->status !== 'pending'
                    || $entry->actual_start_time !== null || $entry->actual_reach_time !== null
                    || $entry->is_driver_verified || $entry->is_vehicle_verified || $entry->is_initial_verified || $entry->is_final_verified
                    || $entry->dor()->exists() || $rosters->contains(fn (Roster $r) => ! in_array($r->status, ['assigned', 'unassigned'], true)
                        || $r->attendance_status !== null);
                if ($protected) {
                    $summary['protected']++;
                    continue;
                }
                if ($apply) $entry->update(['service_code' => $target]);
                $summary['updated']++;
            }
            return $summary;
        }, 3);
    }

    private function suffix(string $code, array $prefixes): ?string
    {
        $prefixes = array_values(array_unique(array_filter($prefixes, fn ($p) => is_string($p) && $p !== '')));
        usort($prefixes, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($prefixes as $prefix) {
            if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/D', $code, $match) && (int) $match[1] > 0) return $match[1];
        }
        return null;
    }

    private function join(string $prefix, string $suffix): string
    {
        $code = $prefix.$suffix;
        if (mb_strlen($code) > 255) throw ValidationException::withMessages(['service_code_id' => 'The selected service-code title is too long for a numbered SER code.']);
        return $code;
    }
}
