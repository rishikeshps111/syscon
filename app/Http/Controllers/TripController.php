<?php

namespace App\Http\Controllers;

use App\Exports\CompletedTripSheetExport;
use App\Exports\TripExport;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Oem;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripAssignment;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class TripController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trips.view'), ['index', 'show', 'export', 'completedTrips', 'completedTripsExport', 'completedTripView', 'completedTripPdf']),
            new Middleware(PermissionMiddleware::using('trips.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('trips.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('trips.sheet'), ['sheet', 'createSheetEntry', 'editSheetEntry', 'duplicateSheetEntry', 'storeSheet', 'destroySheetEntry', 'sheetView', 'importSheetForm', 'importSheet', 'sampleSheetCsv']),
            new Middleware(PermissionMiddleware::using('trips.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = $this->filteredQuery();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('service_type_name', fn ($row) => $row->serviceType?->name ?? '')
                ->addColumn('route_name', fn ($row) => $row->route?->route_name ?? '')
                ->addColumn('trip_title', fn ($row) => $row->trip_title ?: '-')
                ->addColumn('from_location', fn ($row) => $row->route?->startPoint?->name ?? '-')
                ->addColumn('to_location', fn ($row) => $row->route?->endPoint?->name ?? '-')
                ->addColumn('halt_time', fn ($row) => $this->timeToMinutes($row->halt_time) ?? '-')
                ->addColumn('trip_side', fn ($row) => Trip::TRIP_SIDES[$row->trip_side] ?? '-')
                ->addColumn('status', fn ($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn ($row) => view('trip.partials.action', compact('row'))->render())
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('trip.index', [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'oems' => Oem::orderBy('oem_name')->get(['id', 'oem_name']),
            'statuses' => Trip::STATUSES,
        ]);
    }

    public function completedTrips(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of($this->completedTripEntriesQuery($request))
                ->addIndexColumn()
                ->addColumn('trip_code', fn ($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('title', fn ($entry) => $entry->sheet?->trip?->trip_title ?: '-')
                ->addColumn('trip_date', fn ($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('from_location', fn ($entry) => $entry->sheet?->trip?->route?->startPoint?->name ?: '-')
                ->addColumn('to_location', fn ($entry) => $entry->sheet?->trip?->route?->endPoint?->name ?: '-')
                ->addColumn('driver_name', fn ($entry) => $this->entryDriverName($entry))
                ->addColumn('status', fn ($entry) => $this->sheetStatusBadge($entry->sheet?->status))
                ->addColumn('action', fn ($entry) => '<a href="' . e(route('trips.completed.view', $entry->id)) . '" class="btn-edit" title="View"><i class="fa-solid fa-eye"></i></a>')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('trip.completed', [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
            'controllers' => ControllerProfile::with('user')->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
            'supervisors' => SupervisorProfile::with('user')->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
        ]);
    }

    public function completedTripsExport(Request $request)
    {
        return Excel::download(
            new CompletedTripSheetExport($this->completedTripEntriesQuery($request)),
            'completed-trips.xlsx'
        );
    }

    public function completedTripView(TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->status === 'completed', 404);

        $tripSheetEntry->load([
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.route.stops',
            'sheet.trip.depot',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.assignments.vehicle',
            'sheet.trip.assignments.driverProfile.user',
        ]);

        return view('trip.completed-view', [
            'entry' => $tripSheetEntry,
            'assignment' => self::assignmentForCompletedEntry($tripSheetEntry),
        ]);
    }

    public function completedTripPdf(TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->status === 'completed', 404);

        $tripSheetEntry->load([
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.depot',
            'driverProfile.user',
            'vehicle',
            'sheet.trip.assignments.vehicle',
            'sheet.trip.assignments.driverProfile.user',
        ]);

        $pdf = $this->buildCompletedTripPdf($tripSheetEntry, self::assignmentForCompletedEntry($tripSheetEntry));
        $fileName = ($tripSheetEntry->sheet?->code ?: 'completed-trip') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function create(Request $request)
    {
        $generatedCode = $this->generateTripCode(((int) Trip::max('id')) + 1);

        return view('trip.create', $this->formData(['generatedCode' => $generatedCode]));
    }

    public function store(StoreTripRequest $request)
    {
        $data = $this->tripPayload($request->validated()) + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
        $trip = Trip::create($data);
        $trip->code = $this->generateTripCode($trip->id);
        $trip->save();

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->route('trips.index')->with('success', 'Trip saved successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip saved successfully.',
            'data' => $trip,
        ], 201);
    }

    public function show(Trip $trip) {}

    public function edit(Trip $trip)
    {
        return view('trip.edit', $this->formData(['record' => $trip]));
    }

    public function update(UpdateTripRequest $request, Trip $trip)
    {
        $trip->update($this->tripPayload($request->validated()) + ['updated_by' => auth()->id()]);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->route('trips.index')->with('success', 'Trip updated successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip updated successfully.',
            'data' => $trip->fresh(),
        ]);
    }

    private function tripPayload(array $data): array
    {
        if (array_key_exists('halt_time', $data)) {
            $haltTime = $data['halt_time'];
            $data['halt_time'] = $haltTime === null || $haltTime === ''
                ? null
                : sprintf('%02d:%02d', intdiv((int) $haltTime, 60), (int) $haltTime % 60);
        }

        return $data;
    }

    private function timeToMinutes(?string $time): ?int
    {
        if (! $time) {
            return null;
        }

        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    public function destroy(Trip $trip)
    {
        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery();

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new TripExport($query), 'trips.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:trips,id'],
            'status' => ['required', Rule::in(array_keys(Trip::STATUSES))],
            'cancellation_reason' => ['nullable', 'required_if:status,Cancelled', 'string'],
        ]);

        $trip = Trip::findOrFail($request->id);
        $trip->update([
            'status' => $validated['status'],
            'cancellation_reason' => $validated['cancellation_reason'] ?? $trip->cancellation_reason,
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function sheet(Trip $trip)
    {
        if (request()->ajax()) {
            return DataTables::of($this->sheetEntriesQuery($trip))
                ->addIndexColumn()
                ->addColumn('trip_date', fn ($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('code', fn ($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('status', fn ($entry) => $this->sheetStatusBadge($entry->sheet?->status))
                ->editColumn('side', fn ($entry) => ucfirst((string) $entry->side))
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('driver_name', fn ($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle_no', fn ($entry) => $this->entryVehicleNo($entry))
                ->editColumn('starting_km', fn ($entry) => $entry->starting_km ?? '-')
                ->editColumn('starting_electric_charge', fn ($entry) => $entry->starting_electric_charge !== null ? $entry->starting_electric_charge . '%' : '-')
                ->editColumn('is_vehicle_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_vehicle_verified))
                ->editColumn('is_driver_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_driver_verified))
                ->addColumn('action', fn ($entry) => $this->sheetEntryActionButtons($trip, $entry))
                ->rawColumns(['status', 'is_vehicle_verified', 'is_driver_verified', 'action'])
                ->make(true);
        }

        return view('trip.sheet', $this->assignmentData($trip) + [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'route.stops']),
        ]);
    }

    public function createSheetEntry(Trip $trip)
    {
        return view('trip.sheet-form', $this->sheetFormData($trip, null, 'create'));
    }

    public function editSheetEntry(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        return view('trip.sheet-form', $this->sheetFormData($trip, $tripSheetEntry->load('sheet'), 'edit'));
    }

    public function duplicateSheetEntry(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        return view('trip.sheet-form', $this->sheetFormData($trip, $tripSheetEntry->load('sheet'), 'duplicate'));
    }

    public function sheetView(Request $request, Trip $trip)
    {
        $trip->load(['route.startPoint', 'route.endPoint', 'route.stops', 'depot']);
        $query = TripSheetEntry::query()
            ->with('sheet')
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->select('trip_sheet_entries.*')
            ->orderBy('trip_sheets.date')
            ->orderBy('trip_sheet_entries.side');

        if ($request->filled('date_from')) {
            $query->whereDate('trip_sheets.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trip_sheets.date', '<=', $request->date_to);
        }

        if ($request->input('export') === 'csv') {
            $entries = $query->get();
            $fileName = ($trip->code ?: 'trip') . '-sheet.csv';

            return response()->streamDownload(function () use ($entries) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'SL No',
                    'Date',
                    'Code',
                    'Status',
                    'Side',
                    'Departure Time',
                    'Arrival Time',
                    'Actual Start Time',
                    'Actual Reach Time',
                    'Starting Km',
                    'Starting Electric Charge',
                    'Vehicle Condition',
                    'Vehicle Verified',
                    'Vehicle Verified By',
                    'Vehicle Verified Timestamp',
                    'Driver Verified',
                    'Driver Verified By',
                    'Driver Verified Timestamp',
                    'Supervisor Verified',
                    'Verified By Supervisor',
                    'Verified By Supervisor Timestamp',
                    'Driver Final Verified',
                    'Verified By Driver',
                    'Verified By Driver Timestamp',
                    'Notes',
                ]);

                foreach ($entries as $index => $entry) {
                    fputcsv($handle, [
                        $index + 1,
                        $entry->sheet?->date?->format('d-m-Y'),
                        $entry->sheet?->code,
                        TripSheet::STATUSES[$entry->sheet?->status] ?? $entry->sheet?->status,
                        ucfirst((string) $entry->side),
                        $this->formatSheetTime($entry->departure_time),
                        $this->formatSheetTime($entry->arrival_time),
                        $this->formatSheetTime($entry->actual_start_time),
                        $this->formatSheetTime($entry->actual_reach_time),
                        $entry->starting_km,
                        $entry->starting_electric_charge,
                        $entry->vehicle_condition,
                        $entry->is_vehicle_verified ? 'Yes' : 'No',
                        $entry->vehicle_verified_by,
                        $entry->vehicle_verified_at?->format('d-m-Y H:i'),
                        $entry->is_driver_verified ? 'Yes' : 'No',
                        $entry->driver_verified_by,
                        $entry->driver_verified_at?->format('d-m-Y H:i'),
                        $entry->is_verified_by_supervisor ? 'Yes' : 'No',
                        $entry->verified_by_supervisor,
                        $entry->verified_by_supervisor_at?->format('d-m-Y H:i'),
                        $entry->is_verified_by_driver ? 'Yes' : 'No',
                        $entry->verified_by_driver,
                        $entry->verified_by_driver_at?->format('d-m-Y H:i'),
                        $entry->notes,
                    ]);
                }

                fclose($handle);
            }, $fileName, ['Content-Type' => 'text/csv']);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('trip_date', fn ($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('code', fn ($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('status', fn ($entry) => $this->sheetStatusBadge($entry->sheet?->status))
                ->editColumn('side', fn ($entry) => ucfirst((string) $entry->side))
                ->editColumn('departure_time', fn ($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('arrival_time', fn ($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->editColumn('starting_electric_charge', fn ($entry) => $entry->starting_electric_charge !== null ? $entry->starting_electric_charge . '%' : '-')
                ->editColumn('is_vehicle_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_vehicle_verified))
                ->editColumn('is_driver_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_driver_verified))
                ->editColumn('is_verified_by_supervisor', fn ($entry) => $this->yesNoBadge((bool) $entry->is_verified_by_supervisor))
                ->editColumn('is_verified_by_driver', fn ($entry) => $this->yesNoBadge((bool) $entry->is_verified_by_driver))
                ->editColumn('notes', fn ($entry) => $entry->notes ?: '-')
                ->rawColumns(['status', 'is_vehicle_verified', 'is_driver_verified', 'is_verified_by_supervisor', 'is_verified_by_driver'])
                ->make(true);
        }

        return view('trip.sheet-view', [
            'record' => $trip,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function importSheetForm(Trip $trip)
    {
        return view('trip.sheet-import', [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'route.stops']),
            'headers' => $this->sheetCsvHeaders(),
        ]);
    }

    public function importSheet(Request $request, Trip $trip)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        [$rows, $errors] = $this->readSheetCsv($request->file('csv_file')->getRealPath());

        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => $errors]);
        }

        $validatedRows = $this->validateSheetCsvRows($trip, $rows);

        DB::transaction(function () use ($trip, $validatedRows) {
            $dates = collect($validatedRows)
                ->pluck('date')
                ->map(fn (Carbon $date) => $date->toDateString())
                ->unique()
                ->values();

            $trip->sheets()->whereIn('date', $dates)->delete();

            foreach ($validatedRows as $row) {
                $sheet = $this->sheetForDate($trip, $row['date']->toDateString(), $row['status']);
                $sheet->entries()->create($this->entryPayload($trip, $row));
            }
        });

        return redirect()
            ->route('trips.sheet.view', $trip->id)
            ->with('success', count($validatedRows) . ' trip sheet row(s) imported successfully.');
    }

    public function sampleSheetCsv(Trip $trip)
    {
        $tripDate = $trip->from_date ?: now();
        $side = $trip->trip_side === 'down' ? 'down' : 'up';

        return response()->streamDownload(function () use ($trip, $tripDate, $side) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->sheetCsvHeaders());
            fputcsv($handle, [
                $tripDate->format('d-m-Y'),
                'pending',
                $side,
                $this->formatSheetTime($trip->start_time) ?: '09:00',
                $this->formatSheetTime($trip->end_time) ?: '17:00',
                $side === 'up' ? ($this->formatSheetTime($trip->start_time) ?: '09:00') : '',
                $side === 'down' ? ($this->formatSheetTime($trip->end_time) ?: '17:00') : '',
                '1200',
                '85',
                'Good',
                'yes',
                '',
                now()->format('d-m-Y H:i'),
                'yes',
                '',
                now()->format('d-m-Y H:i'),
                'no',
                '',
                '',
                'no',
                '',
                '',
                'Sample trip sheet row',
            ]);
            fclose($handle);
        }, ($trip->code ?: 'trip') . '-sheet-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function storeSheet(Request $request, Trip $trip)
    {
        $verifierNames = $this->verifierNames($trip);

        $validated = $request->validate([
            'entry_id' => ['nullable', 'integer', 'exists:trip_sheet_entries,id'],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(TripSheet::STATUSES))],
            'side' => ['required', Rule::in(array_keys($this->sideOptions($trip)))],
            'departure_time' => ['nullable', 'date_format:H:i'],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'actual_start_time' => ['nullable', 'date_format:H:i'],
            'actual_reach_time' => ['nullable', 'date_format:H:i'],
            'driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'starting_km' => ['nullable', 'integer', 'min:0'],
            'starting_electric_charge' => ['nullable', 'integer', 'min:0', 'max:100'],
            'vehicle_condition' => ['nullable', 'string'],
            'is_vehicle_verified' => ['nullable', 'boolean'],
            'vehicle_verified_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'vehicle_verified_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_driver_verified' => ['nullable', 'boolean'],
            'driver_verified_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'driver_verified_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_verified_by_supervisor' => ['nullable', 'boolean'],
            'verified_by_supervisor' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'verified_by_supervisor_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_verified_by_driver' => ['nullable', 'boolean'],
            'verified_by_driver' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'verified_by_driver_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($validated['date']);

        if (($trip->from_date && $date->lt($trip->from_date)) || ($trip->to_date && $date->gt($trip->to_date))) {
            throw ValidationException::withMessages(['date' => 'Date must be within the trip date range.']);
        }

        DB::transaction(function () use ($trip, $validated) {
            $sheet = $this->sheetForDate($trip, $validated['date'], $validated['status']);
            $entry = null;

            if (! empty($validated['entry_id'])) {
                $entry = TripSheetEntry::whereKey($validated['entry_id'])
                    ->whereHas('sheet', fn ($query) => $query->where('trip_id', $trip->id))
                    ->firstOrFail();
            }

            $duplicate = $sheet->entries()
                ->where('side', $validated['side'])
                ->when($entry, fn ($query) => $query->whereKeyNot($entry->id))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['side' => 'This side already exists for the selected date.']);
            }

            $payload = $this->entryPayload($trip, $validated);

            if ($entry) {
                $oldSheet = $entry->sheet;
                $entry->update(['trip_sheet_id' => $sheet->id] + $payload);

                if ($oldSheet && $oldSheet->id !== $sheet->id && $oldSheet->entries()->count() === 0) {
                    $oldSheet->delete();
                }
            } else {
                $sheet->entries()->create($payload);
            }
        });

        return redirect()->route('trips.sheet', $trip->id)->with('success', 'Trip sheet entry saved successfully.');
    }

    public function destroySheetEntry(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        $sheet = $tripSheetEntry->sheet;
        $tripSheetEntry->delete();

        if ($sheet && $sheet->entries()->count() === 0) {
            $sheet->delete();
        }

        return redirect()->route('trips.sheet', $trip->id)->with('success', 'Trip sheet entry deleted successfully.');
    }

    public static function assignmentForCompletedEntry(TripSheetEntry $entry): ?TripAssignment
    {
        return self::assignmentForEntryDate($entry, $entry->sheet?->date?->format('Y-m-d'));
    }

    private static function assignmentForEntryDate(TripSheetEntry $entry, ?string $date): ?TripAssignment
    {
        $trip = $entry->sheet?->trip;
        $date = $date ? Carbon::parse($date) : $entry->sheet?->date;

        if (! $date || ! $trip) {
            return null;
        }

        return $trip->assignments
            ->first(fn (TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function filteredQuery()
    {
        $query = Trip::with(['serviceType', 'route.startPoint', 'route.endPoint', 'depot', 'assignments.vehicle.oem'])
            ->select('trips.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhereHas('route', fn ($routeQuery) => $routeQuery->where('route_name', 'like', '%' . $search . '%'));
            });
        }

        if (request()->filled('date_from')) {
            $query->whereDate('from_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('to_date', '<=', request('date_to'));
        }

        if (request()->filled('depot_id')) {
            $query->where('depot_id', request('depot_id'));
        }

        if (request()->filled('oem_id')) {
            $query->whereHas('assignments.vehicle', fn ($vehicleQuery) => $vehicleQuery->where('oem_id', request('oem_id')));
        }

        if (request()->filled('status')) {
            if (in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            } else {
                $query->where('status', request('status'));
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    private function completedTripEntriesQuery(Request $request)
    {
        $query = TripSheetEntry::query()
            ->with([
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.depot',
                'driverProfile.user',
                'vehicle',
                'sheet.trip.assignments.vehicle',
                'sheet.trip.assignments.driverProfile.user',
            ])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->join('trips', 'trip_sheets.trip_id', '=', 'trips.id')
            ->where('trip_sheets.status', 'completed')
            ->select('trip_sheet_entries.*');

        if ($request->filled('date_from')) {
            $query->whereDate('trip_sheets.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trip_sheets.date', '<=', $request->date_to);
        }

        if ($request->filled('depot_id')) {
            $query->where('trips.depot_id', $request->depot_id);
        }

        if ($request->filled('search_text')) {
            $search = $request->search_text;
            $query->where('trip_sheets.code', 'like', '%' . $search . '%');
        }

        if ($request->filled('vehicle_id')) {
            $query->where(function ($filterQuery) use ($request) {
                $filterQuery->where('trip_sheet_entries.vehicle_id', $request->vehicle_id)
                    ->orWhere(function ($fallbackQuery) use ($request) {
                        $fallbackQuery->whereNull('trip_sheet_entries.vehicle_id')
                            ->whereExists(function ($subQuery) use ($request) {
                                $subQuery->selectRaw('1')
                                    ->from('trip_assignments')
                                    ->whereColumn('trip_assignments.trip_id', 'trip_sheets.trip_id')
                                    ->whereColumn('trip_assignments.from_date', '<=', 'trip_sheets.date')
                                    ->whereColumn('trip_assignments.to_date', '>=', 'trip_sheets.date')
                                    ->where('trip_assignments.vehicle_id', $request->vehicle_id);
                            });
                    });
            });
        }

        if ($request->filled('driver_profile_id')) {
            $query->where(function ($filterQuery) use ($request) {
                $filterQuery->where('trip_sheet_entries.driver_profile_id', $request->driver_profile_id)
                    ->orWhere(function ($fallbackQuery) use ($request) {
                        $fallbackQuery->whereNull('trip_sheet_entries.driver_profile_id')
                            ->whereExists(function ($subQuery) use ($request) {
                                $subQuery->selectRaw('1')
                                    ->from('trip_assignments')
                                    ->whereColumn('trip_assignments.trip_id', 'trip_sheets.trip_id')
                                    ->whereColumn('trip_assignments.from_date', '<=', 'trip_sheets.date')
                                    ->whereColumn('trip_assignments.to_date', '>=', 'trip_sheets.date')
                                    ->where('trip_assignments.driver_profile_id', $request->driver_profile_id);
                            });
                    });
            });
        }

        if ($request->filled('controller_name')) {
            $this->whereVerifierName($query, $request->controller_name);
        }

        if ($request->filled('supervisor_name')) {
            $this->whereVerifierName($query, $request->supervisor_name);
        }

        return $query->orderByDesc('trip_sheets.date')
            ->orderBy('trip_sheets.code')
            ->orderBy('trip_sheet_entries.side');
    }

    private function whereVerifierName($query, string $name): void
    {
        $query->where(function ($subQuery) use ($name) {
            $subQuery->where('trip_sheet_entries.vehicle_verified_by', $name)
                ->orWhere('trip_sheet_entries.driver_verified_by', $name)
                ->orWhere('trip_sheet_entries.verified_by_supervisor', $name)
                ->orWhere('trip_sheet_entries.verified_by_driver', $name);
        });
    }

    private function sheetEntriesQuery(Trip $trip)
    {
        return TripSheetEntry::query()
            ->with(['sheet.trip.assignments.driverProfile.user', 'sheet.trip.assignments.vehicle', 'driverProfile.user', 'vehicle'])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->orderBy('trip_sheets.date')
            ->orderBy('trip_sheet_entries.side')
            ->select('trip_sheet_entries.*');
    }

    private function sheetEntryActionButtons(Trip $trip, TripSheetEntry $entry): string
    {
        $editUrl = route('trips.sheet.entries.edit', [$trip->id, $entry->id]);
        $duplicateUrl = route('trips.sheet.entries.duplicate', [$trip->id, $entry->id]);
        $deleteUrl = route('trips.sheet.entries.destroy', [$trip->id, $entry->id]);

        return '<div class="d-flex justify-content-center gap-1">'
            . '<a href="' . e($editUrl) . '" class="btn btn-sm btn-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>'
            . '<a href="' . e($duplicateUrl) . '" class="btn btn-sm btn-info text-white" title="Duplicate"><i class="fa-regular fa-copy"></i></a>'
            . '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline delete-sheet-entry">'
            . csrf_field()
            . method_field('DELETE')
            . '<button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>'
            . '</form>'
            . '</div>';
    }

    private function sheetEntryPayload(TripSheetEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date' => $entry->sheet?->date?->format('Y-m-d'),
            'status' => $entry->sheet?->status,
            'side' => $entry->side,
            'departure_time' => $this->formatSheetTime($entry->departure_time),
            'arrival_time' => $this->formatSheetTime($entry->arrival_time),
            'actual_start_time' => $this->formatSheetTime($entry->actual_start_time),
            'actual_reach_time' => $this->formatSheetTime($entry->actual_reach_time),
            'driver_profile_id' => $entry->driver_profile_id,
            'vehicle_id' => $entry->vehicle_id,
            'starting_km' => $entry->starting_km,
            'starting_electric_charge' => $entry->starting_electric_charge,
            'vehicle_condition' => $entry->vehicle_condition,
            'is_vehicle_verified' => $entry->is_vehicle_verified,
            'vehicle_verified_by' => $entry->vehicle_verified_by,
            'vehicle_verified_at' => $entry->vehicle_verified_at?->format('Y-m-d\TH:i'),
            'is_driver_verified' => $entry->is_driver_verified,
            'driver_verified_by' => $entry->driver_verified_by,
            'driver_verified_at' => $entry->driver_verified_at?->format('Y-m-d\TH:i'),
            'is_verified_by_supervisor' => $entry->is_verified_by_supervisor,
            'verified_by_supervisor' => $entry->verified_by_supervisor,
            'verified_by_supervisor_at' => $entry->verified_by_supervisor_at?->format('Y-m-d\TH:i'),
            'is_verified_by_driver' => $entry->is_verified_by_driver,
            'verified_by_driver' => $entry->verified_by_driver,
            'verified_by_driver_at' => $entry->verified_by_driver_at?->format('Y-m-d\TH:i'),
            'notes' => $entry->notes,
        ];
    }

    private function entryDriverName(TripSheetEntry $entry): string
    {
        $assignment = self::assignmentForCompletedEntry($entry);

        return $entry->driverProfile?->user?->name
            ?: $assignment?->driverProfile?->user?->name
            ?: '-';
    }

    private function entryVehicleNo(TripSheetEntry $entry): string
    {
        $assignment = self::assignmentForCompletedEntry($entry);

        return $entry->vehicle?->vehicle_no
            ?: $assignment?->vehicle?->vehicle_no
            ?: '-';
    }

    private function assignmentForDate(Trip $trip, ?string $date): ?TripAssignment
    {
        if (! $date) {
            return null;
        }

        $date = Carbon::parse($date);
        $trip->loadMissing(['assignments.driverProfile.user', 'assignments.vehicle']);

        return $trip->assignments
            ->first(fn (TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function formData(array $extra = []): array
    {
        return $extra + [
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'routes' => RouteModel::with(['startPoint', 'endPoint', 'stops'])->orderBy('route_name')->get(),
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'statuses' => collect(Trip::STATUSES)->only(['Active', 'Inactive', 'Cancelled'])->all(),
        ];
    }

    private function assignmentData(Trip $trip): array
    {
        return [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'assignments.vehicle', 'assignments.driverProfile.user']),
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no', 'vehicle_type']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
        ];
    }

    private function sheetFormData(Trip $trip, ?TripSheetEntry $entry, string $mode): array
    {
        return $this->assignmentData($trip) + [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'route.stops']),
            'entry' => $entry,
            'mode' => $mode,
            'statuses' => TripSheet::STATUSES,
            'sideOptions' => $this->sideOptions($trip),
            'verifiers' => $this->verifierNames($trip),
        ];
    }

    private function generateTripCode(int $id): string
    {
        return generate_code(Trip::PREFIX_MODULE, $id, 4);
    }

    private function formatSheetTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '';
    }

    private function sheetCsvHeaders(): array
    {
        return [
            'trip_date',
            'status',
            'side',
            'departure_time',
            'arrival_time',
            'actual_start_time',
            'actual_reach_time',
            'starting_km',
            'starting_electric_charge',
            'vehicle_condition',
            'is_vehicle_verified',
            'vehicle_verified_by',
            'vehicle_verified_at',
            'is_driver_verified',
            'driver_verified_by',
            'driver_verified_at',
            'is_verified_by_supervisor',
            'verified_by_supervisor',
            'verified_by_supervisor_at',
            'is_verified_by_driver',
            'verified_by_driver',
            'verified_by_driver_at',
            'notes',
        ];
    }

    private function readSheetCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [[], ['Unable to read the uploaded CSV file.']];
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return [[], ['CSV file is empty.']];
        }

        $header = array_map(fn ($value) => Str::of((string) $value)->trim()->lower()->replace(' ', '_')->toString(), $header);
        $missingHeaders = array_diff(['trip_date'], $header);

        if ($missingHeaders) {
            fclose($handle);
            return [[], ['Missing required column(s): ' . implode(', ', $missingHeaders) . '.']];
        }

        $rows = [];
        $errors = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->isEmptyCsvRow($data)) {
                continue;
            }

            if (count($data) > count($header)) {
                $errors[] = "Row {$line}: too many columns.";
                continue;
            }

            $data = array_pad($data, count($header), '');
            $rows[] = [
                'line' => $line,
                'data' => array_combine($header, $data),
            ];
        }

        fclose($handle);

        if (! $rows && ! $errors) {
            $errors[] = 'CSV file does not contain any trip sheet rows.';
        }

        return [$rows, $errors];
    }

    private function validateSheetCsvRows(Trip $trip, array $rows): array
    {
        $errors = [];
        $validatedRows = [];
        $seen = [];
        $sideOptions = array_keys($this->sideOptions($trip));
        $verifierNames = $this->verifierNames($trip);

        foreach ($rows as $row) {
            $line = $row['line'];
            $data = collect($row['data'])
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            $date = $this->csvDate($data['trip_date'] ?? null);
            $status = strtolower($data['status'] ?? 'pending') ?: 'pending';
            $side = strtolower($data['side'] ?? ($trip->trip_side === 'down' ? 'down' : 'up'));
            $departureTime = $this->csvTime($data['departure_time'] ?? null);
            $arrivalTime = $this->csvTime($data['arrival_time'] ?? null);
            $actualStartTime = $this->csvTime($data['actual_start_time'] ?? null) ?: ($side === 'up' ? $this->formatSheetTime($trip->start_time) : null);
            $actualReachTime = $this->csvTime($data['actual_reach_time'] ?? null) ?: ($side === 'down' ? $this->formatSheetTime($trip->end_time) : null);
            $notes = $data['notes'] ?? null;

            if (! $date) {
                $errors[] = "Row {$line}: trip_date must be a valid date in DD-MM-YYYY format.";
            } elseif (($trip->from_date && $date->lt($trip->from_date)) || ($trip->to_date && $date->gt($trip->to_date))) {
                $errors[] = "Row {$line}: trip_date must be within the trip date range.";
            }

            if (! array_key_exists($status, TripSheet::STATUSES)) {
                $errors[] = "Row {$line}: status must be pending, partial, completed, or cancelled.";
            }

            if (! in_array($side, $sideOptions, true)) {
                $errors[] = "Row {$line}: side is not allowed for this trip.";
            }

            foreach (['departure_time', 'arrival_time', 'actual_start_time', 'actual_reach_time'] as $field) {
                if (($data[$field] ?? '') !== '' && ! $this->csvTime($data[$field])) {
                    $errors[] = "Row {$line}: {$field} must be a valid time in HH:MM format.";
                }
            }

            if (($data['starting_km'] ?? '') !== '' && (! ctype_digit((string) $data['starting_km']))) {
                $errors[] = "Row {$line}: starting_km must be a whole number.";
            }

            if (($data['starting_electric_charge'] ?? '') !== '') {
                if (! ctype_digit((string) $data['starting_electric_charge']) || (int) $data['starting_electric_charge'] > 100) {
                    $errors[] = "Row {$line}: starting_electric_charge must be a whole number from 0 to 100.";
                }
            }

            if ($date) {
                $key = $date->toDateString() . '|' . $side;

                if (isset($seen[$key])) {
                    $errors[] = "Row {$line}: duplicate trip sheet date and side; first seen on row {$seen[$key]}.";
                }

                $seen[$key] = $line;
            }

            foreach (['vehicle_verified_at', 'driver_verified_at', 'verified_by_supervisor_at', 'verified_by_driver_at'] as $field) {
                if (($data[$field] ?? '') !== '' && ! $this->csvDateTime($data[$field])) {
                    $errors[] = "Row {$line}: {$field} must be a valid date/time in DD-MM-YYYY HH:MM format.";
                }
            }

            foreach (['vehicle_verified_by', 'driver_verified_by', 'verified_by_supervisor', 'verified_by_driver'] as $field) {
                if (($data[$field] ?? '') !== '' && ! in_array($data[$field], $verifierNames, true)) {
                    $errors[] = "Row {$line}: {$field} must be an active supervisor or controller name for this depot.";
                }
            }

            $validatedRows[] = [
                'date' => $date,
                'status' => $status,
                'side' => $side,
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'actual_start_time' => $actualStartTime,
                'actual_reach_time' => $actualReachTime,
                'starting_km' => ($data['starting_km'] ?? '') !== '' ? (int) $data['starting_km'] : null,
                'starting_electric_charge' => ($data['starting_electric_charge'] ?? '') !== '' ? (int) $data['starting_electric_charge'] : null,
                'vehicle_condition' => ($data['vehicle_condition'] ?? '') ?: null,
                'is_vehicle_verified' => $this->csvBoolean($data['is_vehicle_verified'] ?? null),
                'vehicle_verified_by' => ($data['vehicle_verified_by'] ?? '') ?: null,
                'vehicle_verified_at' => $this->csvDateTime($data['vehicle_verified_at'] ?? null),
                'is_driver_verified' => $this->csvBoolean($data['is_driver_verified'] ?? null),
                'driver_verified_by' => ($data['driver_verified_by'] ?? '') ?: null,
                'driver_verified_at' => $this->csvDateTime($data['driver_verified_at'] ?? null),
                'is_verified_by_supervisor' => $this->csvBoolean($data['is_verified_by_supervisor'] ?? null),
                'verified_by_supervisor' => ($data['verified_by_supervisor'] ?? '') ?: null,
                'verified_by_supervisor_at' => $this->csvDateTime($data['verified_by_supervisor_at'] ?? null),
                'is_verified_by_driver' => $this->csvBoolean($data['is_verified_by_driver'] ?? null),
                'verified_by_driver' => ($data['verified_by_driver'] ?? '') ?: null,
                'verified_by_driver_at' => $this->csvDateTime($data['verified_by_driver_at'] ?? null),
                'notes' => $notes ?: null,
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => array_slice($errors, 0, 20)]);
        }

        return $validatedRows;
    }

    private function sideOptions(Trip $trip): array
    {
        return match ($trip->trip_side) {
            'up' => ['up' => 'Up'],
            'down' => ['down' => 'Down'],
            default => ['up' => 'Up', 'down' => 'Down'],
        };
    }

    private function verifierNames(Trip $trip): array
    {
        $controllers = ControllerProfile::with('user')
            ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->pluck('user.name')
            ->filter();

        $supervisors = SupervisorProfile::with('user')
            ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->pluck('user.name')
            ->filter();

        return $controllers->merge($supervisors)->unique()->sort()->values()->all();
    }

    private function sheetForDate(Trip $trip, string $date, string $status): TripSheet
    {
        $sheet = $trip->sheets()->firstOrCreate(
            ['date' => $date],
            [
                'code' => $this->sheetCode($trip, $date),
                'status' => $status,
            ]
        );

        $sheet->update(['status' => $status]);

        if (! $sheet->code) {
            $sheet->update(['code' => $this->sheetCode($trip, $date)]);
        }

        return $sheet;
    }

    private function sheetCode(Trip $trip, string $date): string
    {
        return ($trip->code ?: 'TRIP-' . $trip->id) . '-' . str_replace('-', '', $date);
    }

    private function entryPayload(Trip $trip, array $data): array
    {
        $side = $data['side'];

        return [
            'side' => $side,
            'departure_time' => $data['departure_time'] ?? null,
            'arrival_time' => $data['arrival_time'] ?? null,
            'actual_start_time' => ($data['actual_start_time'] ?? null) ?: ($side === 'up' ? $this->formatSheetTime($trip->start_time) : null),
            'actual_reach_time' => ($data['actual_reach_time'] ?? null) ?: ($side === 'down' ? $this->formatSheetTime($trip->end_time) : null),
            'driver_profile_id' => ($data['driver_profile_id'] ?? null) ?: $this->assignmentForDate($trip, $data['date'] ?? null)?->driver_profile_id,
            'vehicle_id' => ($data['vehicle_id'] ?? null) ?: $this->assignmentForDate($trip, $data['date'] ?? null)?->vehicle_id,
            'starting_km' => $data['starting_km'] ?? null,
            'starting_electric_charge' => $data['starting_electric_charge'] ?? null,
            'vehicle_condition' => $data['vehicle_condition'] ?? null,
            'is_vehicle_verified' => (bool) ($data['is_vehicle_verified'] ?? false),
            'vehicle_verified_by' => $data['vehicle_verified_by'] ?? null,
            'vehicle_verified_at' => $this->normalizeDateTime($data['vehicle_verified_at'] ?? null),
            'is_driver_verified' => (bool) ($data['is_driver_verified'] ?? false),
            'driver_verified_by' => $data['driver_verified_by'] ?? null,
            'driver_verified_at' => $this->normalizeDateTime($data['driver_verified_at'] ?? null),
            'is_verified_by_supervisor' => (bool) ($data['is_verified_by_supervisor'] ?? false),
            'verified_by_supervisor' => $data['verified_by_supervisor'] ?? null,
            'verified_by_supervisor_at' => $this->normalizeDateTime($data['verified_by_supervisor_at'] ?? null),
            'is_verified_by_driver' => (bool) ($data['is_verified_by_driver'] ?? false),
            'verified_by_driver' => $data['verified_by_driver'] ?? null,
            'verified_by_driver_at' => $this->normalizeDateTime($data['verified_by_driver_at'] ?? null),
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function csvDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d-m-Y', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('d-m-Y') === $value ? $date : null;
    }

    private function csvTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            $time = Carbon::createFromFormat('H:i', $value);
        } catch (\Throwable) {
            return null;
        }

        return $time && $time->format('H:i') === $value ? $time->format('H:i') : null;
    }

    private function csvDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            $dateTime = Carbon::createFromFormat('d-m-Y H:i', $value);
        } catch (\Throwable) {
            return null;
        }

        return $dateTime && $dateTime->format('d-m-Y H:i') === $value
            ? $dateTime->format('Y-m-d H:i:s')
            : null;
    }

    private function csvBoolean(?string $value): bool
    {
        return in_array(Str::of((string) $value)->lower()->toString(), ['1', 'yes', 'true', 'y'], true);
    }

    private function csvDriverProfileId(?string $driverCode, ?string $driverName): array
    {
        if (! $driverCode && ! $driverName) {
            return [null, null];
        }

        $query = DriverProfile::whereHas('user', fn ($userQuery) => $userQuery->where('is_active', true));

        if ($driverCode) {
            $driver = (clone $query)->whereHas('user', fn ($userQuery) => $userQuery->where('code', $driverCode))->first();

            return $driver
                ? [$driver->id, null]
                : [null, 'active driver not found for driver_code.'];
        }

        $drivers = $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', $driverName))->limit(2)->get();

        if ($drivers->count() > 1) {
            return [null, 'multiple active drivers found with this name; use driver_code instead.'];
        }

        return $drivers->isNotEmpty()
            ? [$drivers->first()->id, null]
            : [null, 'active driver not found for driver_name.'];
    }

    private function controllerNameExists(Trip $trip, string $name): bool
    {
        return ControllerProfile::query()
            ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn ($query) => $query->where('is_active', true)->where('name', $name))
            ->exists();
    }

    private function supervisorNameExists(Trip $trip, string $name): bool
    {
        return SupervisorProfile::query()
            ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn ($query) => $query->where('is_active', true)->where('name', $name))
            ->exists();
    }

    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function buildCompletedTripPdf(TripSheetEntry $entry, ?TripAssignment $assignment): string
    {
        $trip = $entry->sheet?->trip;
        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'Completed Trip Sheet', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 420, 795, 10);

        $this->pdfSection($content, 'Trip Details', 40, 610, 515, [
            'Trip Sheet Code' => $entry->sheet?->code ?: '-',
            'Title' => $trip?->trip_title ?: '-',
            'Date' => $entry->sheet?->date?->format('d M Y') ?: '-',
            'Status' => 'Completed',
            'Side' => ucfirst((string) $entry->side),
            'Depot' => $trip?->depot?->name ?: '-',
        ], 135);

        $this->pdfSection($content, 'Route And Assignment', 40, 425, 515, [
            'From' => $trip?->route?->startPoint?->name ?: '-',
            'To' => $trip?->route?->endPoint?->name ?: '-',
            'Vehicle No' => $entry->vehicle?->vehicle_no ?: $assignment?->vehicle?->vehicle_no ?: '-',
            'Driver Name' => $entry->driverProfile?->user?->name ?: $assignment?->driverProfile?->user?->name ?: '-',
            'Departure Time' => $this->formatSheetTime($entry->departure_time) ?: '-',
            'Arrival Time' => $this->formatSheetTime($entry->arrival_time) ?: '-',
        ], 135);

        $this->pdfSection($content, 'Verification', 40, 240, 515, [
            'Vehicle Verified' => $entry->is_vehicle_verified ? 'Yes' : 'No',
            'Vehicle Verified By' => $entry->vehicle_verified_by ?: '-',
            'Driver Verified' => $entry->is_driver_verified ? 'Yes' : 'No',
            'Driver Verified By' => $entry->driver_verified_by ?: '-',
            'Supervisor Verified' => $entry->is_verified_by_supervisor ? 'Yes' : 'No',
            'Verified By Supervisor' => $entry->verified_by_supervisor ?: '-',
        ], 145);

        $this->pdfSection($content, 'Notes', 40, 110, 515, [
            'Notes' => $entry->notes ?: '-',
        ], 85);

        return $this->pdfDocument([$content]);
    }

    private function pdfDocument(array $contents): string
    {
        $pageCount = count($contents);
        $fontObject = 3 + ($pageCount * 2);
        $boldFontObject = $fontObject + 1;
        $objects = [];
        $pageObjectNumbers = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        foreach ($contents as $index => $content) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageObjectNumbers[] = $pageObject . ' 0 R';
            $objects[$pageObject] = $pageObject . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 " . $fontObject . " 0 R /F2 " . $boldFontObject . " 0 R >> >> /Contents " . $contentObject . " 0 R >>\nendobj\n";
            $objects[$contentObject] = $contentObject . " 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' ', $pageObjectNumbers) . '] /Count ' . count($pageObjectNumbers) . " >>\nendobj\n";
        $objects[$fontObject] = $fontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[$boldFontObject] = $boldFontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
    }

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 155): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label . ':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 150, $lineY, 9);
            $lineY -= 17;
        }
    }

    private function pdfCard(string &$content, int $x, int $y, int $width, int $height): void
    {
        $this->pdfFill($content, 1, 1, 1, $x, $y, $width, $height);
        $content .= "0.84 0.86 0.90 RG\n";
        $content .= $x . ' ' . $y . ' ' . $width . ' ' . $height . " re S\n";
    }

    private function pdfFill(string &$content, float $r, float $g, float $b, int $x, int $y, int $width, int $height): void
    {
        $content .= sprintf("%.2f %.2f %.2f rg\n%d %d %d %d re f\n", $r, $g, $b, $x, $y, $width, $height);
    }

    private function pdfText(string &$content, string $text, int $x, int $y, int $size = 10, string $font = 'F1'): void
    {
        $content .= "0.08 0.10 0.14 rg\n";
        $content .= "BT\n/" . $font . ' ' . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . $this->escapePdfText(substr($text, 0, 80)) . ") Tj\nET\n";
    }

    private function escapePdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function statusBadge(?string $status): string
    {
        return match ($status) {
            'Active' => '<span class="status-green">Active</span>',
            'Cancelled' => '<span class="status-red">Cancelled</span>',
            default => '<span class="status-orange">' . e($status ?: 'Inactive') . '</span>',
        };
    }

    private function sheetStatusBadge(?string $status): string
    {
        $label = TripSheet::STATUSES[$status] ?? Str::title($status ?: 'pending');
        $class = match ($status) {
            'completed' => 'bg-success',
            'partial' => 'bg-warning text-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };

        return '<span class="badge ' . $class . '">' . e($label) . '</span>';
    }

    private function yesNoBadge(bool $value): string
    {
        $class = $value ? 'bg-success' : 'bg-secondary';
        $label = $value ? 'Yes' : 'No';

        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}
