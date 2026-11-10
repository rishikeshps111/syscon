<?php

namespace App\Services;

use App\Exceptions\RosterAssignmentConflict;
use App\Models\{DriverProfile, Trip, TripSheetEntry, User};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MonthlyRosterImport
{
    public const HEADERS = ['Trip Code', 'Date', 'Service Code', 'Driver Name', 'Current Vehicle'];

    public function read(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $workbook = $reader->load($path);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['roster_file' => 'Unable to read the Excel file.']);
        }
        try {
            $sheet = $workbook->getActiveSheet();
            $headers = [];
            foreach (self::HEADERS as $index => $header) {
                $headers[] = trim((string) $sheet->getCell([$index + 1, 1])->getValue());
            }
            if ($headers !== self::HEADERS) {
                throw ValidationException::withMessages(['roster_file' => 'Download a fresh sample with columns: '.implode(', ', self::HEADERS).'. The previous four-column format is no longer supported.']);
            }
            if ($sheet->getHighestDataRow() > 10001) {
                throw ValidationException::withMessages(['roster_file' => 'The file may contain at most 10,000 data rows.']);
            }
            $rows = [];
            $keys = [];
            for ($line = 2; $line <= $sheet->getHighestDataRow(); $line++) {
                $values = [];
                for ($col = 1; $col <= count(self::HEADERS); $col++) $values[] = $sheet->getCell([$col, $line])->getValue();
                if (! array_filter($values, fn ($value) => trim((string) $value) !== '')) continue;
                $code = trim((string) $values[0]);
                $date = $this->date($values[1]);
                $service = trim((string) $values[2]);
                $error = $code === '' ? 'Trip Code is required.' : (! $date ? 'Date must be dd-mm-yyyy or an Excel date.'
                    : ($service === '' ? 'Service Code is required.' : null));
                $key = mb_strtolower($code);
                $keys[$key] = ($keys[$key] ?? 0) + 1;
                $rows[] = ['line' => $line, 'trip_code' => $code, 'date' => $date?->toDateString(), 'display_date' => $date?->format('d-m-Y') ?? (string) $values[1], 'service' => $service,
                    'driver_name' => trim((string) $values[3]), 'key' => $key, 'error' => $error];
            }
            foreach ($rows as &$row) {
                if (! $row['error'] && $keys[$row['key']] > 1) $row['error'] = 'Duplicate Trip Code in the file; remove duplicate rows.';
            }
            unset($row);
            if (! $rows) throw ValidationException::withMessages(['roster_file' => 'The file contains no data rows.']);
            return $rows;
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    public function preview(User $user, Trip $trip, array $row): array
    {
        $preview = $row + ['status' => 'issue', 'reason' => '', 'conflicts' => [], 'current_driver' => '', 'proposed_driver' => $row['driver_name'], 'vehicle' => 'Not assigned'];
        $entries = $row['trip_code'] !== '' ? TripSheetEntry::where('code', $row['trip_code'])
            ->whereHas('sheet', fn ($query) => $query->where('trip_id', $trip->id))
            ->with(['driverProfile.user', 'vehicle', 'rosters.driverProfile.user', 'rosters.vehicle'])->limit(2)->get() : collect();
        if ($entries->count() === 1) {
            $entry = $entries->first();
            $roster = $entry->rosters->sortByDesc('id')->first();
            $preview['current_driver'] = $this->driverLabel($entry->driverProfile?->user ?? $roster?->driverProfile?->user);
            $preview['vehicle'] = $entry->vehicle?->vehicle_no ?? $roster?->vehicle?->vehicle_no ?? 'Not assigned';
            $preview['_entry_id'] = $entry->id;
            $preview['_service'] = (string) $entry->service_code;
            $preview['_vehicle_id'] = $entry->vehicle_id ?: $roster?->vehicle_id;
        }
        if ($row['error']) return array_replace($preview, ['reason' => $row['error']]);
        if ($entries->count() !== 1) return array_replace($preview, ['reason' => $entries->isEmpty()
            ? 'No trip-sheet entry with this Trip Code belongs to the opened trip.' : 'Trip Code matches multiple trip entries.']);
        if (! $entry->sheet->date) return array_replace($preview, ['reason' => 'Trip-sheet entry '.$entry->code.' has no duty date. Complete the trip sheet before importing.']);
        if ($entry->sheet->date?->toDateString() !== $row['date'] || (string) $entry->service_code !== $row['service']) {
            return array_replace($preview, ['reason' => 'Date or Service Code does not match Trip Code '.$row['trip_code'].'.']);
        }
        if ($row['driver_name'] === '') return array_replace($preview, ['status' => 'blank', 'reason' => 'Driver Name is blank; assignment retained.']);
        $drivers = DriverProfile::whereHas('user', fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($row['driver_name'])]))->with('user')->limit(2)->get();
        if ($drivers->count() !== 1) return array_replace($preview, ['reason' => $drivers->isEmpty()
            ? 'Driver Name was not found.' : 'Driver Name matches multiple drivers.']);
        $driver = $drivers->first();
        $preview['proposed_driver'] = $this->driverLabel($driver->user);
        $preview['_driver_id'] = $driver->id;
        if ($entry->departure_time && $entry->arrival_time) {
            $start = Carbon::parse($row['date'].' '.$entry->departure_time);
            $end = Carbon::parse($row['date'].' '.$entry->arrival_time);
            if ($end->lte($start)) $end->addDay();
            $preview['_start'] = $start->timestamp;
            $preview['_end'] = $end->timestamp;
        }
        try {
            $checked = app(TripRosterAssignment::class)->inspect($user, $trip, $entry->id, $driver->id);
            $preview['status'] = ['created' => 'ready_create', 'updated' => 'ready_update', 'unchanged' => 'unchanged'][$checked['status']];
            $preview['_snapshot'] = $checked['snapshot'];
            $preview['reason'] = $checked['status'] === 'unchanged' ? 'The same driver and vehicle are already assigned.' : '';
        } catch (ValidationException $exception) {
            $preview['reason'] = implode(' ', $exception->validator->errors()->all());
            if ($exception instanceof RosterAssignmentConflict) $preview['conflicts'] = $exception->conflicts;
        }
        return $preview;
    }

    public function flagUploadedConflicts(array &$previews, array &$reservations, int $index): void
    {
        $row = $previews[$index];
        if (! isset($row['_driver_id'], $row['_start'], $row['_end'])) return;
        $keys = ['driver:'.$row['_driver_id']];
        if ($row['_vehicle_id']) $keys[] = 'vehicle:'.$row['_vehicle_id'];
        $candidates = [];
        foreach ($keys as $key) foreach ($reservations[$key] ?? [] as $previous) $candidates[$previous] = true;
        foreach (array_keys($candidates) as $previous) {
            $other = $previews[$previous];
            if ($row['_start'] >= $other['_end'] || $row['_end'] <= $other['_start']) continue;
            foreach ([[$index, $previous], [$previous, $index]] as [$target, $conflicting]) {
                $label = $row['_driver_id'] === $other['_driver_id'] ? 'Driver' : 'Vehicle';
                $conflict = $previews[$conflicting];
                $previews[$target]['status'] = 'issue';
                $previews[$target]['reason'] = trim($previews[$target]['reason'].' '.$label.' overlaps uploaded row '.$conflict['line']
                    .' (Trip Code '.$conflict['trip_code'].', service '.$conflict['service'].' on '.$conflict['display_date'].').');
                $previews[$target]['conflicts'][] = [
                    'trip_code' => $conflict['trip_code'], 'parent_trip_code' => '', 'service_code' => $conflict['service'],
                    'date' => $conflict['display_date'], 'start_time' => Carbon::createFromTimestamp($conflict['_start'], config('app.timezone'))->format('H:i'),
                    'end_time' => Carbon::createFromTimestamp($conflict['_end'], config('app.timezone'))->format('H:i'),
                    'status' => 'proposed', 'source' => 'upload', 'resource' => strtolower($label), 'row' => $conflict['line'],
                ];
            }
        }
        foreach ($keys as $key) $reservations[$key][] = $index;
    }

    public function save(User $user, Trip $trip, array $preview): array
    {
        try {
            $status = app(TripRosterAssignment::class)->assign($user, $trip, $preview['_entry_id'], $preview['_driver_id'], null,
                ['code' => $preview['trip_code'], 'date' => $preview['date'], 'service' => $preview['_service'], 'snapshot' => $preview['_snapshot']]);
            return ['status' => $status, 'reason' => ''];
        } catch (ValidationException $exception) {
            return ['status' => 'skipped', 'reason' => implode(' ', $exception->validator->errors()->all()),
                'conflicts' => $exception instanceof RosterAssignmentConflict ? $exception->conflicts : []];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ['status' => 'skipped', 'reason' => 'The trip entry or assignment no longer exists.'];
        }
    }

    private function driverLabel(?User $user): string
    {
        return $user?->name ?? '';
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            if (is_numeric($value) && (float) $value > 0 && (float) $value < 2958466) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->startOfDay();
            }
            $value = trim((string) $value);
            $date = Carbon::createFromFormat('!d-m-Y', $value);
            return $date && $date->format('d-m-Y') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
