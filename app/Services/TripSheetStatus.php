<?php

namespace App\Services;

use App\Models\TripSheet;
use Illuminate\Support\Facades\DB;

class TripSheetStatus
{
    public function sync(TripSheet $sheet): void
    {
        DB::transaction(function () use ($sheet): void {
            $locked = TripSheet::query()->lockForUpdate()->findOrFail($sheet->id);
            $statuses = $locked->entries()->lockForUpdate()->pluck('status');
            $remaining = $statuses->reject(fn ($status) => $status === 'cancelled');
            $status = match (true) {
                $statuses->isNotEmpty() && $remaining->isEmpty() => 'cancelled',
                $remaining->isNotEmpty() && $remaining->every(fn ($status) => $status === 'verification_completed') => 'verification_completed',
                $remaining->isNotEmpty() && $remaining->every(fn ($status) => in_array($status, ['initial_verification_completed', 'verification_completed'], true)) => 'initial_verification_completed',
                default => 'pending',
            };
            $locked->update(['status' => $status]);
            $sheet->status = $status;
        });
    }
}
