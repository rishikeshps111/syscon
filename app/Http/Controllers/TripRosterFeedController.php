<?php

namespace App\Http\Controllers;

use App\Models\{DriverProfile, Trip, TripSheetEntry, Vehicle};
use App\Services\TripRosterAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class TripRosterFeedController extends Controller
{
    public function rows(Request $request, Trip $trip, TripRosterAssignment $assignments)
    {
        $assignments->authorize($request->user(), $trip);
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_merge(['nullable', 'date_format:Y-m-d'], $request->filled('date_from') ? ['after_or_equal:date_from'] : []),
            'ser' => ['nullable', 'string', 'max:255'],
            'cursor' => ['nullable', 'string', 'max:4096'],
        ]);
        $filters = ['date_from' => $data['date_from'] ?? '', 'date_to' => $data['date_to'] ?? '', 'ser' => trim($data['ser'] ?? '')];
        $context = hash('sha256', json_encode([$trip->id, $filters]));
        $query = TripSheetEntry::query()
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->select('trip_sheet_entries.*')->selectRaw('DATE(trip_sheets.date) as roster_date')
            ->selectRaw('COALESCE(trip_sheet_entries.trip_order_sequence_no, 0) as roster_sequence')
            ->with(['driverProfile.user:id,name,code', 'vehicle:id,vehicle_no',
                'rosters' => fn ($q) => $q->orderByDesc('rosters.id'),
                'rosters.driverProfile.user:id,name,code', 'rosters.vehicle:id,vehicle_no'])
            ->when($filters['date_from'], fn ($q, $date) => $q->whereDate('trip_sheets.date', '>=', $date))
            ->when($filters['date_to'], fn ($q, $date) => $q->whereDate('trip_sheets.date', '<=', $date))
            ->when($filters['ser'] !== '', fn ($q) => $q->where('trip_sheet_entries.service_code', 'like', '%'.$filters['ser'].'%'));
        if (! empty($data['cursor'])) {
            try {
                $cursor = json_decode(Crypt::decryptString($data['cursor']), true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($cursor) || ($cursor['context'] ?? null) !== $context
                    || ! is_string($cursor['date'] ?? null) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $cursor['date'])
                    || ! is_int($cursor['sequence'] ?? null) || ! is_int($cursor['id'] ?? null) || $cursor['id'] < 1) {
                    throw new \UnexpectedValueException;
                }
            } catch (\Throwable) {
                throw ValidationException::withMessages(['cursor' => 'The roster cursor is invalid or filters changed. Reload the duties.']);
            }
            $query->where(fn ($q) => $q->whereDate('trip_sheets.date', '<', $cursor['date'])
                ->orWhere(fn ($q) => $q->whereDate('trip_sheets.date', $cursor['date'])
                    ->whereRaw('COALESCE(trip_sheet_entries.trip_order_sequence_no, 0) > ?', [$cursor['sequence']]))
                ->orWhere(fn ($q) => $q->whereDate('trip_sheets.date', $cursor['date'])
                    ->whereRaw('COALESCE(trip_sheet_entries.trip_order_sequence_no, 0) = ?', [$cursor['sequence']])
                    ->where('trip_sheet_entries.id', '>', $cursor['id'])));
        }
        $entries = $query->orderByRaw('DATE(trip_sheets.date) DESC')->orderByRaw('COALESCE(trip_sheet_entries.trip_order_sequence_no, 0) ASC')
            ->orderBy('trip_sheet_entries.id')->limit(26)->get();
        $more = $entries->count() > 25;
        $entries = $entries->take(25)->values();
        $last = $entries->last();
        return response()->json(['rows' => $entries->map(function ($entry) use ($trip) {
            $roster = $entry->rosters->first();
            $driver = $entry->driverProfile ?: $roster?->driverProfile;
            $vehicle = $entry->vehicle ?: $roster?->vehicle;
            return ['id' => $entry->id, 'date' => $entry->roster_date,
                'date_text' => Carbon::parse($entry->roster_date)->format('d M Y'),
                'trip_code' => $entry->code ?: $trip->code ?: '-', 'ser' => $entry->service_code ?: '-',
                'driver' => $driver ? ['id' => $driver->id, 'text' => trim(($driver->user?->code ?? '').' - '.($driver->user?->name ?? ''))] : null,
                'vehicle' => $vehicle ? ['id' => $vehicle->id, 'text' => $vehicle->vehicle_no] : null];
        }), 'next_cursor' => $more ? Crypt::encryptString(json_encode(['context' => $context, 'date' => $last->roster_date,
            'sequence' => (int) $last->roster_sequence, 'id' => $last->id])) : null]);
    }

    public function drivers(Request $request, Trip $trip, TripRosterAssignment $assignments)
    {
        $assignments->authorize($request->user(), $trip);
        $data = $this->lookupInput($request);
        $drivers = DriverProfile::where('depot_id', $trip->depot_id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true)
                ->when($data['q'] !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$data['q'].'%')
                    ->orWhere('code', 'like', '%'.$data['q'].'%'))))
            ->with('user:id,name,code')->orderBy('id')->offset(($data['page'] - 1) * 25)->limit(26)->get();
        return response()->json(['results' => $drivers->take(25)->map(fn ($d) => ['id' => $d->id, 'text' => $d->user->code.' - '.$d->user->name])->values(),
            'pagination' => ['more' => $drivers->count() > 25]]);
    }

    public function vehicles(Request $request, Trip $trip, TripRosterAssignment $assignments)
    {
        $assignments->authorize($request->user(), $trip);
        $data = $this->lookupInput($request);
        $vehicles = Vehicle::where('status', 'Active')->where('depot_id', $trip->depot_id)
            ->where('vehicle_classification_id', $trip->vehicle_classification_id)
            ->when($data['q'] !== '', fn ($q) => $q->where('vehicle_no', 'like', '%'.$data['q'].'%'))
            ->orderBy('vehicle_no')->orderBy('id')->offset(($data['page'] - 1) * 25)->limit(26)->get(['id', 'vehicle_no']);
        return response()->json(['results' => $vehicles->take(25)->map(fn ($v) => ['id' => $v->id, 'text' => $v->vehicle_no])->values(),
            'pagination' => ['more' => $vehicles->count() > 25]]);
    }

    private function lookupInput(Request $request): array
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        return ['q' => trim($data['q'] ?? ''), 'page' => (int) ($data['page'] ?? 1)];
    }
}
