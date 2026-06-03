<?php

namespace Database\Seeders;

use App\Models\ControllerProfile;
use App\Models\Roster;
use App\Models\SupervisorProfile;
use App\Models\TripAssignment;
use App\Models\TripSheetEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RosterSeeder extends Seeder
{
    public function run(): void
    {
        $supervisor = SupervisorProfile::with('user')->first();
        $controller = ControllerProfile::with('user')->first();

        $entries = TripSheetEntry::with([
            'sheet.trip.depot',
            'sheet.trip.assignments.driverProfile.user',
            'sheet.trip.assignments.vehicle.oem',
            'sheet.trip.assignments.vehicle.state',
            'sheet.trip.assignments.vehicle.depot',
        ])
            ->whereHas('sheet.trip.assignments')
            ->limit(14)
            ->get();

        DB::transaction(function () use ($entries, $supervisor, $controller) {
            foreach ($entries as $index => $entry) {
                $date = $entry->sheet?->date;
                $trip = $entry->sheet?->trip;
                $assignment = $this->assignmentForEntry($entry);
                $vehicle = $assignment?->vehicle;
                $depot = $trip?->depot ?: $vehicle?->depot;
                $state = $vehicle?->state ?: $depot?->state ?: $vehicle?->oem?->state;
                $oem = $vehicle?->oem;

                if (! $date || ! $trip || ! $assignment || ! $vehicle || ! $depot || ! $state || ! $oem) {
                    continue;
                }

                $shift = $this->shiftForIndex($index);
                $roster = Roster::updateOrCreate(
                    [
                        'trip_sheet_entry_id' => $entry->id,
                        'shift_type' => $shift['type'],
                    ],
                    [
                        'state_id' => $state->id,
                        'oem_id' => $oem->id,
                        'depot_id' => $depot->id,
                        'duty_date' => $date->toDateString(),
                        'shift_start_time' => $shift['start'],
                        'shift_end_time' => $shift['end'],
                        'trip_assignment_id' => $assignment->id,
                        'driver_profile_id' => $assignment->driver_profile_id,
                        'vehicle_id' => $assignment->vehicle_id,
                        'supervisor_profile_id' => $supervisor?->id,
                        'controller_profile_id' => $controller?->id,
                        'reporting_time' => $shift['reporting'],
                        'reporting_to_time' => $shift['reporting_to'],
                        'remarks' => 'Seeded duty roster for ' . ($trip->trip_title ?: $trip->code),
                        'status' => $index % 4 === 0 ? 'in_progress' : 'assigned',
                        'attendance_status' => $index % 3 === 0 ? 'present' : null,
                    ]
                );

                if (! $roster->code) {
                    $roster->code = generate_code(Roster::PREFIX_MODULE, $roster->id, 4, 'RST');
                    $roster->save();
                }
            }
        });
    }

    private function assignmentForEntry(TripSheetEntry $entry): ?TripAssignment
    {
        $trip = $entry->sheet?->trip;
        $date = $entry->sheet?->date;

        if (! $trip || ! $date) {
            return null;
        }

        return $trip->assignments
            ->first(fn (TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function shiftForIndex(int $index): array
    {
        return match ($index % 3) {
            1 => [
                'type' => 'evening',
                'start' => '14:00',
                'end' => '22:00',
                'reporting' => '13:30',
                'reporting_to' => '21:45',
            ],
            2 => [
                'type' => 'night',
                'start' => '22:00',
                'end' => '06:00',
                'reporting' => '21:30',
                'reporting_to' => '05:45',
            ],
            default => [
                'type' => 'morning',
                'start' => '06:00',
                'end' => '14:00',
                'reporting' => '05:30',
                'reporting_to' => '13:45',
            ],
        };
    }
}
