<?php

namespace App\Http\Controllers;

use App\Exports\CompletedTripSheetExport;
use App\Exports\TripExport;
use App\Exports\TripReportExport;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\DorAccountResponsible;
use App\Models\DorKilometerLossReason;
use App\Models\DriverProfile;
use App\Models\Oem;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripAssignment;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\TripSheetEntryDor;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            new Middleware(PermissionMiddleware::using('trips.view'), ['index', 'show', 'export', 'completedTrips', 'completedTripsExport', 'completedTripView', 'completedTripPdf', 'tripReport', 'downloadTripReport']),
            new Middleware(PermissionMiddleware::using('trips.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('trips.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('trips.sheet'), ['sheet', 'createSheetEntry', 'editSheetEntry', 'duplicateSheetEntry', 'storeSheet', 'destroySheetEntry', 'sheetView', 'importSheetForm', 'importSheet', 'sampleSheetCsv', 'dorForm', 'storeDor', 'dorPreview']),
            new Middleware(PermissionMiddleware::using('trips.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = $this->filteredQuery();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('service_type_name', fn($row) => $row->serviceType?->name ?? '')
                ->addColumn('route_name', fn($row) => $row->route?->route_name ?? '')
                ->addColumn('trip_title', fn($row) => $row->trip_title ?: '-')
                ->addColumn('from_location', fn($row) => $row->route?->startPoint?->name ?? '-')
                ->addColumn('to_location', fn($row) => $row->route?->endPoint?->name ?? '-')
                ->addColumn('halt_time', fn($row) => $this->timeToMinutes($row->halt_time) ?? '-')
                ->addColumn('trip_side', fn($row) => Trip::TRIP_SIDES[$row->trip_side] ?? '-')
                ->addColumn('status', fn($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn($row) => view('trip.partials.action', compact('row'))->render())
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
                ->addColumn('trip_code', fn($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('title', fn($entry) => $entry->sheet?->trip?->trip_title ?: '-')
                ->addColumn('trip_date', fn($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('from_location', fn($entry) => $entry->sheet?->trip?->route?->startPoint?->name ?: '-')
                ->addColumn('to_location', fn($entry) => $entry->sheet?->trip?->route?->endPoint?->name ?: '-')
                ->addColumn('driver_name', fn($entry) => $this->entryDriverName($entry))
                ->addColumn('status', fn($entry) => $this->sheetStatusBadge($entry->sheet?->status))
                ->addColumn('action', fn($entry) => '<div class="action-btns"><a href="' . e(route('trips.completed.view', $entry->id)) . '" class="btn-view" title="View"><i class="fa-solid fa-eye"></i></a></div>')
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('trip.completed', [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
            'controllers' => ControllerProfile::with('user')->whereHas('user', fn($query) => $query->where('is_active', true))->get(),
            'supervisors' => SupervisorProfile::with('user')->whereHas('user', fn($query) => $query->where('is_active', true))->get(),
        ]);
    }

    public function completedTripsExport(Request $request)
    {
        return Excel::download(
            new CompletedTripSheetExport($this->completedTripEntriesQuery($request)),
            'completed-trips.xlsx'
        );
    }

    public function tripReport(Request $request)
    {
        return view('trip.report', [
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function downloadTripReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $from = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->format('Ymd') : 'all';
        $to = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->format('Ymd') : 'all';
        $fileName = "trip-report-{$from}-{$to}.xlsx";

        return Excel::download(new TripReportExport($this->tripReportEntriesQuery($request)), $fileName);
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
                ->addColumn('trip_date', fn($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('code', fn($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('status', fn($entry) => $this->sheetStatusBadge($entry->sheet?->status))
                ->editColumn('side', fn($entry) => ucfirst((string) $entry->side))
                ->editColumn('actual_start_time', fn($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('driver_name', fn($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle_no', fn($entry) => $this->entryVehicleNo($entry))
                ->editColumn('trip_order_sequence_no', fn($entry) => $entry->trip_order_sequence_no ?? '-')
                ->editColumn('starting_km', fn($entry) => $entry->starting_km ?? '-')
                ->editColumn('starting_electric_charge', fn($entry) => $entry->starting_electric_charge !== null ? $entry->starting_electric_charge . '%' : '-')
                ->editColumn('is_vehicle_verified', fn($entry) => $this->yesNoBadge((bool) $entry->is_vehicle_verified))
                ->editColumn('is_driver_verified', fn($entry) => $this->yesNoBadge((bool) $entry->is_driver_verified))
                ->addColumn('action', fn($entry) => $this->sheetEntryActionButtons($trip, $entry))
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

    public function dorForm(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        $entry = $this->dorEntry($trip, $tripSheetEntry);
        $canCompleteDor = $this->canCompleteDor();
        $dorReadOnly = $this->dorIsLockedForUser($entry);

        return view('trip.dor-form', [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'depot']),
            'entry' => $entry,
            'dor' => $entry->dor,
            'fields' => $this->dorFields($entry),
            'canCompleteDor' => $canCompleteDor,
            'dorReadOnly' => $dorReadOnly,
            'accountResponsibles' => DorAccountResponsible::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'kilometerLossReasons' => DorKilometerLossReason::where('is_active', true)->orderBy('name')->get(['id', 'dor_account_responsible_id', 'name']),
            'odometerImages' => $this->dorImageUrls($entry->dor),
        ]);
    }

    public function storeDor(Request $request, Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        $entry = $this->dorEntry($trip, $tripSheetEntry);

        if ($this->dorIsLockedForUser($entry)) {
            return redirect()
                ->route('trips.sheet.entries.dor', [$trip->id, $entry->id])
                ->with('error', 'This DOR is marked as complete and can only be edited by a Super Admin.');
        }

        $validated = $request->validate($this->dorRules($this->canCompleteDor()));
        $this->validateDorReasonAccount($validated);
        $payload = $this->dorPayload($entry, $validated);
        $payload['is_completed'] = $this->canCompleteDor()
            ? (bool) ($validated['is_completed'] ?? false)
            : (bool) ($entry->dor?->is_completed ?? false);

        $dor = $entry->dor;
        $payload += $this->dorImagePayload($request, $entry, $dor);

        if ($dor) {
            $dor->update($payload + ['updated_by' => auth()->id()]);
            $message = 'DOR updated successfully.';
        } else {
            $entry->dor()->create($payload + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
            $message = 'DOR created successfully.';
        }

        return redirect()
            ->route('trips.sheet.view', $trip->id)
            ->with('success', $message);
    }

    public function dorPreview(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        $entry = $this->dorEntry($trip, $tripSheetEntry);
        abort_unless($entry->dor, 404);

        return view('trip.dor-preview', [
            'record' => $trip,
            'entry' => $entry,
            'dor' => $entry->dor,
            'groups' => $this->dorPreviewGroups($entry->dor),
            'odometerImages' => $this->dorImageUrls($entry->dor),
        ]);
    }

    public function sheetView(Request $request, Trip $trip)
    {
        $trip->load([
            'route.startPoint.state',
            'route.endPoint',
            'route.stops',
            'depot.state',
            'createdBy',
            'assignments.driverProfile.user',
            'assignments.vehicle.oem',
            'assignments.vehicle.branch',
        ]);
        $query = TripSheetEntry::query()
            ->with(['sheet.trip.route.startPoint', 'sheet.trip.route.endPoint', 'sheet.trip.assignments.driverProfile.user', 'driverProfile.user', 'dor'])
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
                    'Trip Code',
                    'Starting From',
                    'Destination Point',
                    'Start Time',
                    'Actual Start Time',
                    'Reach Time',
                    'Actual Reach Time',
                    'Shift',
                    'Driver',
                    'Vehicle',
                    'Delay',
                ]);

                foreach ($entries as $index => $entry) {
                    fputcsv($handle, [
                        $index + 1,
                        $entry->sheet?->date?->format('d-m-Y'),
                        $entry->sheet?->code,
                        $this->entryStartingPoint($entry),
                        $this->entryDestinationPoint($entry),
                        $this->formatSheetTime($entry->departure_time),
                        $this->formatSheetTime($entry->actual_start_time),
                        $this->formatSheetTime($entry->arrival_time),
                        $this->formatSheetTime($entry->actual_reach_time),
                        ucfirst((string) $entry->side),
                        $this->entryDriverName($entry),
                        $this->entryVehicleNo($entry),
                        $this->sheetStartDelay($entry->departure_time, $entry->actual_start_time),
                    ]);
                }

                fclose($handle);
            }, $fileName, ['Content-Type' => 'text/csv']);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('trip_date', fn($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('trip_code', fn($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('starting_from', fn($entry) => $this->entryStartingPoint($entry))
                ->addColumn('destination_point', fn($entry) => $this->entryDestinationPoint($entry))
                ->editColumn('departure_time', fn($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('actual_start_time', fn($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('arrival_time', fn($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_reach_time', fn($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('shift', fn($entry) => ucfirst((string) $entry->side))
                ->addColumn('driver', fn($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle', fn($entry) => $this->entryVehicleNo($entry))
                ->addColumn('delay', fn($entry) => $this->sheetStartDelay($entry->departure_time, $entry->actual_start_time))
                ->addColumn('action', fn($entry) => $this->sheetViewDorButtons($trip, $entry))
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('trip.sheet-view', [
            'record' => $trip,
            'entries' => $query->get(),
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
                ->map(fn(Carbon $date) => $date->toDateString())
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
                '1',
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
            'trip_order_sequence_no' => ['nullable', 'integer', 'min:0'],
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
            'is_verified_by_controller' => ['nullable', 'boolean'],
            'verified_by_controller' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'verified_by_controller_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
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
                    ->whereHas('sheet', fn($query) => $query->where('trip_id', $trip->id))
                    ->firstOrFail();
            }

            $duplicate = $sheet->entries()
                ->where('side', $validated['side'])
                ->when($entry, fn($query) => $query->whereKeyNot($entry->id))
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
            ->first(fn(TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function filteredQuery()
    {
        $query = Trip::with(['serviceType', 'route.startPoint', 'route.endPoint', 'depot', 'state', 'assignments.vehicle.oem'])
            ->select('trips.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhereHas('route', fn($routeQuery) => $routeQuery->where('route_name', 'like', '%' . $search . '%'));
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
            $query->whereHas('assignments.vehicle', fn($vehicleQuery) => $vehicleQuery->where('oem_id', request('oem_id')));
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

    private function tripReportEntriesQuery(Request $request)
    {
        $query = TripSheetEntry::query()
            ->with([
                'dor',
                'sheet.trip.route.startPoint',
                'sheet.trip.route.endPoint',
                'sheet.trip.depot',
                'sheet.trip.assignments.vehicle',
                'sheet.trip.assignments.driverProfile.user',
                'driverProfile.user',
                'vehicle',
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

        return $query->orderBy('trip_sheets.date')
            ->orderBy('trip_sheets.code')
            ->orderBy('trip_sheet_entries.side');
    }

    private function whereVerifierName($query, string $name): void
    {
        $query->where(function ($subQuery) use ($name) {
            $subQuery->where('trip_sheet_entries.vehicle_verified_by', $name)
                ->orWhere('trip_sheet_entries.driver_verified_by', $name)
                ->orWhere('trip_sheet_entries.verified_by_supervisor', $name)
                ->orWhere('trip_sheet_entries.verified_by_controller', $name);
        });
    }

    private function sheetEntriesQuery(Trip $trip)
    {
        return TripSheetEntry::query()
            ->with(['sheet.trip.assignments.driverProfile.user', 'sheet.trip.assignments.vehicle', 'driverProfile.user', 'vehicle'])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->orderByDesc('trip_sheet_entries.id')
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
            'trip_order_sequence_no' => $entry->trip_order_sequence_no,
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
            'is_verified_by_controller' => $entry->is_verified_by_controller,
            'verified_by_controller' => $entry->verified_by_controller,
            'verified_by_controller_at' => $entry->verified_by_controller_at?->format('Y-m-d\TH:i'),
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

    private function dorEntry(Trip $trip, TripSheetEntry $entry): TripSheetEntry
    {
        abort_unless($entry->sheet?->trip_id === $trip->id, 404);

        return $entry->load([
            'dor',
            'rosters',
            'sheet.trip.route.startPoint',
            'sheet.trip.route.endPoint',
            'sheet.trip.depot',
            'sheet.trip.assignments.driverProfile.user',
            'sheet.trip.assignments.vehicle',
            'driverProfile.user',
            'vehicle',
        ]);
    }

    private function canCompleteDor(): bool
    {
        return (bool) auth()->user()?->hasRole('Super Admin');
    }

    private function dorIsLockedForUser(TripSheetEntry $entry): bool
    {
        return (bool) $entry->dor?->is_completed && ! $this->canCompleteDor();
    }

    private function dorRules(bool $canCompleteDor = false): array
    {
        $rules = [
            'duty' => ['nullable', 'string', 'max:255'],
            'schedule_km' => ['nullable', 'numeric', 'min:0'],
            'route_km_loss' => ['nullable', 'numeric', 'min:0'],
            'actual_route_km' => ['nullable', 'numeric', 'min:0'],
            'schedule_trip' => ['nullable', 'integer', 'min:0'],
            'actual_trip' => ['nullable', 'integer', 'min:0'],
            'miss_trip' => ['nullable', 'integer', 'min:0'],
            'odometer_start_reading' => ['nullable', 'numeric', 'min:0'],
            'odometer_start_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'odometer_end_reading' => ['nullable', 'numeric', 'min:0'],
            'odometer_end_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'odometer_diff_km' => ['nullable', 'numeric', 'min:0'],
            'difference' => ['nullable', 'numeric'],
            'dor_account_responsible_id' => ['nullable', 'integer', 'exists:dor_account_responsibles,id'],
            'account_responsible' => ['nullable', 'string', 'max:255'],
            'dor_kilometer_loss_reason_id' => ['nullable', 'integer', 'exists:dor_kilometer_loss_reasons,id'],
            'reason_for_kilometer_loss' => ['nullable', 'string'],
            'after_sales_reason' => ['nullable', 'string'],
            'penalty_infraction' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'route_start_soc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'route_start_soc_percent_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'route_end_soc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'route_end_soc_percent_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'soc_consumption_on_route_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'soc_per_km' => ['nullable', 'numeric', 'min:0'],
            'run_kilometer_per_soc' => ['nullable', 'numeric', 'min:0'],
            'dor_kwh_per_km_odo' => ['nullable', 'numeric', 'min:0'],
            'dor_kwh_per_km_act' => ['nullable', 'numeric', 'min:0'],
            'dor_kwh' => ['nullable', 'numeric', 'min:0'],
            'dcr_kwh_per_km_odo' => ['nullable', 'numeric', 'min:0'],
            'dcr_kwh_per_km_act' => ['nullable', 'numeric', 'min:0'],
            'dcr_kwh' => ['nullable', 'numeric', 'min:0'],
            'dcr_charged_soc' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'energy_absorption' => ['nullable', 'numeric', 'min:0'],
            'battery_size_kwh' => ['nullable', 'numeric', 'min:0'],
            'vp1' => ['nullable', 'numeric', 'min:0'],
            'vp2' => ['nullable', 'numeric', 'min:0'],
            'dp' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
            'model_9m_12m' => ['nullable', 'string', 'max:255'],
        ];

        if ($canCompleteDor) {
            $rules['is_completed'] = ['required', 'boolean'];
        }

        return $rules;
    }

    private function validateDorReasonAccount(array $data): void
    {
        if (empty($data['dor_kilometer_loss_reason_id']) || empty($data['dor_account_responsible_id'])) {
            return;
        }

        $matches = DorKilometerLossReason::whereKey($data['dor_kilometer_loss_reason_id'])
            ->where('dor_account_responsible_id', $data['dor_account_responsible_id'])
            ->exists();

        if (! $matches) {
            throw ValidationException::withMessages([
                'dor_kilometer_loss_reason_id' => 'The selected reason does not belong to the selected account responsible.',
            ]);
        }
    }

    private function dorPayload(TripSheetEntry $entry, array $data): array
    {
        $assignment = self::assignmentForCompletedEntry($entry);
        $trip = $entry->sheet?->trip;
        $driver = $entry->driverProfile ?: $assignment?->driverProfile;
        $vehicle = $entry->vehicle ?: $assignment?->vehicle;
        $account = ! empty($data['dor_account_responsible_id'])
            ? DorAccountResponsible::find($data['dor_account_responsible_id'])
            : null;
        $reason = ! empty($data['dor_kilometer_loss_reason_id'])
            ? DorKilometerLossReason::find($data['dor_kilometer_loss_reason_id'])
            : null;
        $scheduleKm = $this->nullableFloat($trip?->schedule_km ?? ($data['schedule_km'] ?? null));
        $routeKmLoss = $this->nullableFloat($data['route_km_loss'] ?? null);
        $actualRouteKm = $this->calculatedActualRouteKm($scheduleKm, $routeKmLoss, $data['actual_route_km'] ?? null);
        $scheduleTrip = $this->nullableInt($data['schedule_trip'] ?? null);
        $actualTrip = $this->nullableInt($data['actual_trip'] ?? null);
        $odometerStart = $this->nullableFloat($data['odometer_start_reading'] ?? null);
        $odometerEnd = $this->nullableFloat($data['odometer_end_reading'] ?? null);
        $odometerDiff = $this->calculatedOdometerDiff($odometerStart, $odometerEnd, $data['odometer_diff_km'] ?? null);
        $routeStartSoc = $this->nullableFloat($data['route_start_soc_percent'] ?? null);
        $routeEndSoc = $this->nullableFloat($data['route_end_soc_percent'] ?? null);
        $socConsumption = $this->calculatedSocConsumption($routeStartSoc, $routeEndSoc, $data['soc_consumption_on_route_percent'] ?? null);
        $dcrKwh = $this->nullableFloat($data['dcr_kwh'] ?? null);
        $dcrChargedSoc = $this->nullableFloat($data['dcr_charged_soc'] ?? null);
        $batterySizeKwh = $this->nullableFloat($data['battery_size_kwh'] ?? null);
        $dorKwh = $this->calculatedDorKwh($socConsumption, $batterySizeKwh);

        return [
            'depot_name' => $trip?->depot?->name,
            'dor_date' => $entry->sheet?->date?->format('Y-m-d'),
            'bus_no' => $vehicle?->vehicle_no,
            'route_no' => $trip?->route?->route_code ?: $trip?->route?->code,
            'duty' => $trip?->trip_title,
            'shift' => ucfirst((string) $entry->side),
            'driver_badge_no' => $driver?->badge_number ?: $driver?->user?->code,
            'schedule_start_time' => $this->formatSheetTime($entry->departure_time) ?: null,
            'schedule_end_time' => $this->formatSheetTime($entry->arrival_time) ?: null,
            'actual_start_time' => $this->formatSheetTime($entry->actual_start_time) ?: null,
            'actual_end_time' => $this->formatSheetTime($entry->actual_reach_time) ?: null,
            'start_punc' => $this->sheetStartDelay($entry->departure_time, $entry->actual_start_time),
            'route_completion_time' => $this->formatSheetTime($entry->actual_reach_time ?: $entry->arrival_time) ?: null,
            'schedule_km' => $scheduleKm,
            'route_km_loss' => $routeKmLoss,
            'actual_route_km' => $actualRouteKm,
            'schedule_trip' => $scheduleTrip,
            'actual_trip' => $actualTrip,
            'miss_trip' => $this->calculatedMissTrip($scheduleTrip, $actualTrip, $data['miss_trip'] ?? null),
            'odometer_start_reading' => $odometerStart,
            'odometer_end_reading' => $odometerEnd,
            'odometer_diff_km' => $odometerDiff,
            'difference' => $this->calculatedDifference($actualRouteKm, $odometerDiff, $data['difference'] ?? null),
            'dor_account_responsible_id' => $account?->id,
            'account_responsible' => $account?->name ?: ($data['account_responsible'] ?? null),
            'dor_kilometer_loss_reason_id' => $reason?->id,
            'reason_for_kilometer_loss' => $reason?->name ?: ($data['reason_for_kilometer_loss'] ?? null),
            'after_sales_reason' => $data['after_sales_reason'] ?? null,
            'penalty_infraction' => $data['penalty_infraction'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'route_start_soc_percent' => $routeStartSoc,
            'route_end_soc_percent' => $routeEndSoc,
            'soc_consumption_on_route_percent' => $socConsumption,
            'soc_per_km' => $this->calculatedSocPerKm($socConsumption, $actualRouteKm, $data['soc_per_km'] ?? null),
            'run_kilometer_per_soc' => $this->calculatedRunKmPerSoc($actualRouteKm, $socConsumption, $data['run_kilometer_per_soc'] ?? null),
            'dor_kwh_per_km_odo' => $this->calculatedDorKwhPerKmOdo($dcrChargedSoc, $socConsumption, $odometerDiff),
            'dor_kwh_per_km_act' => $this->calculatedDorKwhPerKmAct($dcrKwh, $actualRouteKm),
            'dor_kwh' => $dorKwh,
            'dcr_kwh_per_km_odo' => $this->nullableFloat($data['dcr_kwh_per_km_odo'] ?? null),
            'dcr_kwh_per_km_act' => $this->nullableFloat($data['dcr_kwh_per_km_act'] ?? null),
            'dcr_kwh' => $dcrKwh,
            'dcr_charged_soc' => $dcrChargedSoc,
            'energy_absorption' => $this->nullableFloat($data['energy_absorption'] ?? null),
            'battery_size_kwh' => $batterySizeKwh,
            'vp1' => $this->nullableFloat($data['vp1'] ?? null),
            'vp2' => $this->nullableFloat($data['vp2'] ?? null),
            'dp' => $this->nullableFloat($data['dp'] ?? null),
            'penalty' => $this->nullableFloat($data['penalty'] ?? null),
            'model_9m_12m' => $data['model_9m_12m'] ?? null,
        ];
    }

    private function dorImagePayload(Request $request, TripSheetEntry $entry, ?TripSheetEntryDor $dor): array
    {
        $payload = [];
        $directory = 'trip-dor-odometer/' . $entry->id;

        foreach ([
            'odometer_start_image' => 'odometer_start_image_path',
            'odometer_end_image' => 'odometer_end_image_path',
            'route_start_soc_percent_image' => 'route_start_soc_percent_image',
            'route_end_soc_percent_image' => 'route_end_soc_percent_image',
        ] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            if ($dor?->{$column}) {
                Storage::disk('public')->delete($dor->{$column});
            }

            $payload[$column] = $request->file($input)->store($directory, 'public');
        }

        return $payload;
    }

    private function dorImageUrls(?TripSheetEntryDor $dor): array
    {
        return [
            'odometer_start_image' => $dor?->odometer_start_image_path
                ? Storage::disk('public')->url($dor->odometer_start_image_path)
                : null,
            'odometer_end_image' => $dor?->odometer_end_image_path
                ? Storage::disk('public')->url($dor->odometer_end_image_path)
                : null,
            'route_start_soc_percent_image' => $dor?->route_start_soc_percent_image
                ? Storage::disk('public')->url($dor->route_start_soc_percent_image)
                : null,
            'route_end_soc_percent_image' => $dor?->route_end_soc_percent_image
                ? Storage::disk('public')->url($dor->route_end_soc_percent_image)
                : null,
        ];
    }

    private function dorFields(TripSheetEntry $entry): array
    {
        $saved = $entry->dor;
        $savedAccountId = $saved?->dor_account_responsible_id
            ?: ($saved?->account_responsible ? DorAccountResponsible::where('name', $saved->account_responsible)->value('id') : null);
        $savedReasonId = $saved?->dor_kilometer_loss_reason_id;

        if (! $savedReasonId && $saved?->reason_for_kilometer_loss) {
            $savedReasonId = DorKilometerLossReason::where('name', $saved->reason_for_kilometer_loss)
                ->when($savedAccountId, fn ($query) => $query->where('dor_account_responsible_id', $savedAccountId))
                ->value('id');
        }
        $values = $this->dorPayload($entry, [
            'schedule_km' => $saved?->schedule_km,
            'route_km_loss' => $saved?->route_km_loss,
            'actual_route_km' => $saved?->actual_route_km,
            'schedule_trip' => $saved?->schedule_trip,
            'actual_trip' => $saved?->actual_trip,
            'miss_trip' => $saved?->miss_trip,
            'odometer_start_reading' => $saved?->odometer_start_reading,
            'odometer_end_reading' => $saved?->odometer_end_reading,
            'odometer_diff_km' => $saved?->odometer_diff_km,
            'difference' => $saved?->difference,
            'dor_account_responsible_id' => $savedAccountId,
            'account_responsible' => $saved?->account_responsible,
            'dor_kilometer_loss_reason_id' => $savedReasonId,
            'reason_for_kilometer_loss' => $saved?->reason_for_kilometer_loss,
            'after_sales_reason' => $saved?->after_sales_reason,
            'penalty_infraction' => $saved?->penalty_infraction,
            'remarks' => $saved?->remarks,
            'route_start_soc_percent' => $saved?->route_start_soc_percent,
            'route_end_soc_percent' => $saved?->route_end_soc_percent,
            'soc_consumption_on_route_percent' => $saved?->soc_consumption_on_route_percent,
            'soc_per_km' => $saved?->soc_per_km,
            'run_kilometer_per_soc' => $saved?->run_kilometer_per_soc,
            'dor_kwh_per_km_odo' => $saved?->dor_kwh_per_km_odo,
            'dor_kwh_per_km_act' => $saved?->dor_kwh_per_km_act,
            'dor_kwh' => $saved?->dor_kwh,
            'dcr_kwh_per_km_odo' => $saved?->dcr_kwh_per_km_odo,
            'dcr_kwh_per_km_act' => $saved?->dcr_kwh_per_km_act,
            'dcr_kwh' => $saved?->dcr_kwh,
            'dcr_charged_soc' => $saved?->dcr_charged_soc,
            'energy_absorption' => $saved?->energy_absorption,
            'battery_size_kwh' => $saved?->battery_size_kwh,
            'vp1' => $saved?->vp1,
            'vp2' => $saved?->vp2,
            'dp' => $saved?->dp,
            'penalty' => $saved?->penalty,
            'model_9m_12m' => $saved?->model_9m_12m,
        ]);

        return [
            ['label' => 'Depot Name', 'name' => 'depot_name', 'type' => 'text', 'disabled' => true, 'value' => $values['depot_name']],
            ['label' => 'Date', 'name' => 'dor_date', 'type' => 'text', 'disabled' => true, 'value' => $entry->sheet?->date?->format('d-m-Y')],
            ['label' => 'Bus No. (With full registration)', 'name' => 'bus_no', 'type' => 'text', 'disabled' => true, 'value' => $values['bus_no']],
            ['label' => 'Route No', 'name' => 'route_no', 'type' => 'text', 'disabled' => true, 'value' => $values['route_no']],
            ['label' => 'Duty', 'name' => 'duty', 'type' => 'text', 'disabled' => true, 'value' => $values['duty']],
            ['label' => 'Shift', 'name' => 'shift', 'type' => 'text', 'disabled' => true, 'value' => $values['shift']],
            ['label' => 'Driver ID/Badge No.', 'name' => 'driver_badge_no', 'type' => 'text', 'disabled' => true, 'value' => $values['driver_badge_no']],
            ['label' => 'Schedule Start Time', 'name' => 'schedule_start_time', 'type' => 'time', 'disabled' => true, 'value' => $values['schedule_start_time']],
            ['label' => 'Schedule End Time', 'name' => 'schedule_end_time', 'type' => 'time', 'disabled' => true, 'value' => $values['schedule_end_time']],
            ['label' => 'Actual Start Time', 'name' => 'actual_start_time', 'type' => 'time', 'disabled' => true, 'value' => $values['actual_start_time']],
            ['label' => 'Actual End Time', 'name' => 'actual_end_time', 'type' => 'time', 'disabled' => true, 'value' => $values['actual_end_time']],
            ['label' => 'Start Punc.', 'name' => 'start_punc', 'type' => 'text', 'disabled' => true, 'value' => $values['start_punc']],
            ['label' => 'Route Completion Time', 'name' => 'route_completion_time', 'type' => 'time', 'disabled' => true, 'value' => $values['route_completion_time']],
            ['label' => 'Schedule Km', 'name' => 'schedule_km', 'type' => 'number', 'disabled' => true, 'value' => $values['schedule_km']],
            ['label' => 'Route Km Loss', 'name' => 'route_km_loss', 'type' => 'number', 'value' => $values['route_km_loss']],
            ['label' => 'Act. Route Km', 'name' => 'actual_route_km', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['actual_route_km']],
            ['label' => 'Schedule Trip', 'name' => 'schedule_trip', 'type' => 'number', 'value' => $values['schedule_trip']],
            ['label' => 'Actual Trip', 'name' => 'actual_trip', 'type' => 'number', 'value' => $values['actual_trip']],
            ['label' => 'Miss Trip', 'name' => 'miss_trip', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['miss_trip']],
            ['label' => 'Odometer Start Reading (A)', 'name' => 'odometer_start_reading', 'type' => 'number', 'value' => $saved?->odometer_start_reading],
            ['label' => 'Upload Start Odometer Image', 'name' => 'odometer_start_image', 'type' => 'file', 'target' => 'odometer_start_reading', 'image_url' => $this->dorImageUrls($saved)['odometer_start_image']],
            ['label' => 'Odometer End Reading (B)', 'name' => 'odometer_end_reading', 'type' => 'number', 'value' => $saved?->odometer_end_reading],
            ['label' => 'Upload End Odometer Image', 'name' => 'odometer_end_image', 'type' => 'file', 'target' => 'odometer_end_reading', 'image_url' => $this->dorImageUrls($saved)['odometer_end_image']],
            ['label' => 'Odometer Diff. Km', 'name' => 'odometer_diff_km', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['odometer_diff_km']],
            ['label' => 'Difference', 'name' => 'difference', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['difference']],
            ['label' => 'Account Responsible', 'name' => 'dor_account_responsible_id', 'type' => 'account_responsible', 'value' => $values['dor_account_responsible_id']],
            ['label' => 'Reason For Kilometer Loss', 'name' => 'dor_kilometer_loss_reason_id', 'type' => 'kilometer_loss_reason', 'value' => $values['dor_kilometer_loss_reason_id']],
            ['label' => 'After Sales Reason', 'name' => 'after_sales_reason', 'type' => 'text', 'value' => $saved?->after_sales_reason],
            ['label' => 'Penalty Infraction', 'name' => 'penalty_infraction', 'type' => 'text', 'value' => $saved?->penalty_infraction],
            ['label' => 'Remarks', 'name' => 'remarks', 'type' => 'textarea', 'value' => $saved?->remarks],
            ['label' => 'Route Start SOC %', 'name' => 'route_start_soc_percent', 'type' => 'number', 'value' => $saved?->route_start_soc_percent],
            ['label' => 'Upload Route Start SOC Image', 'name' => 'route_start_soc_percent_image', 'type' => 'file', 'target' => 'route_start_soc_percent', 'image_url' => $this->dorImageUrls($saved)['route_start_soc_percent_image']],
            ['label' => 'Route End SOC %', 'name' => 'route_end_soc_percent', 'type' => 'number', 'value' => $saved?->route_end_soc_percent],
            ['label' => 'Upload Route End SOC Image', 'name' => 'route_end_soc_percent_image', 'type' => 'file', 'target' => 'route_end_soc_percent', 'image_url' => $this->dorImageUrls($saved)['route_end_soc_percent_image']],
            ['label' => 'SOC Consumption On Route %', 'name' => 'soc_consumption_on_route_percent', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['soc_consumption_on_route_percent']],
            ['label' => 'SOC Per KM', 'name' => 'soc_per_km', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['soc_per_km']],
            ['label' => 'Run Kilometer Per SOC', 'name' => 'run_kilometer_per_soc', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['run_kilometer_per_soc']],
            ['label' => 'DOR KWh/km (odo)', 'name' => 'dor_kwh_per_km_odo', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['dor_kwh_per_km_odo']],
            ['label' => 'DOR KWH/KM (ACT)', 'name' => 'dor_kwh_per_km_act', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['dor_kwh_per_km_act']],
            ['label' => 'DCR KWh/km (odo)', 'name' => 'dcr_kwh_per_km_odo', 'type' => 'number', 'manual_formula' => true, 'value' => $values['dcr_kwh_per_km_odo']],
            ['label' => 'DCR KWH/KM (ACT)', 'name' => 'dcr_kwh_per_km_act', 'type' => 'number', 'manual_formula' => true, 'value' => $values['dcr_kwh_per_km_act']],
            ['label' => 'DOR KWH', 'name' => 'dor_kwh', 'type' => 'number', 'disabled' => true, 'calculated' => true, 'value' => $values['dor_kwh']],
            ['label' => 'DCR KWH', 'name' => 'dcr_kwh', 'type' => 'number', 'manual_formula' => true, 'value' => $saved?->dcr_kwh],
            ['label' => 'DCR Charged SOC', 'name' => 'dcr_charged_soc', 'type' => 'number', 'manual_formula' => true, 'value' => $saved?->dcr_charged_soc],
            ['label' => 'Energy Absorption', 'name' => 'energy_absorption', 'type' => 'number', 'manual_formula' => true, 'value' => $values['energy_absorption']],
            ['label' => 'Battery size In KWH', 'name' => 'battery_size_kwh', 'type' => 'number', 'value' => $saved?->battery_size_kwh],
            ['label' => 'VP1', 'name' => 'vp1', 'type' => 'number', 'manual_formula' => true, 'value' => $values['vp1']],
            ['label' => 'VP2', 'name' => 'vp2', 'type' => 'number', 'manual_formula' => true, 'value' => $values['vp2']],
            ['label' => 'DP', 'name' => 'dp', 'type' => 'number', 'manual_formula' => true, 'value' => $values['dp']],
            ['label' => 'Penalty', 'name' => 'penalty', 'type' => 'number', 'value' => $saved?->penalty],
            ['label' => 'Model 9M/12M', 'name' => 'model_9m_12m', 'type' => 'select', 'options' => ['9 meter' => '9 meter', '12 meter' => '12 meter'], 'value' => $saved?->model_9m_12m],
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function calculatedActualRouteKm(?float $scheduleKm, ?float $routeKmLoss, mixed $fallback): ?float
    {
        if ($scheduleKm !== null && $routeKmLoss !== null) {
            return max(0, $scheduleKm - $routeKmLoss);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedMissTrip(?int $scheduleTrip, ?int $actualTrip, mixed $fallback): ?int
    {
        if ($scheduleTrip !== null && $actualTrip !== null) {
            return max(0, $scheduleTrip - $actualTrip);
        }

        return $this->nullableInt($fallback);
    }

    private function calculatedOdometerDiff(?float $start, ?float $end, mixed $fallback): ?float
    {
        if ($start !== null && $end !== null) {
            return max(0, $end - $start);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedDifference(?float $actualRouteKm, ?float $odometerDiff, mixed $fallback): ?float
    {
        if ($actualRouteKm !== null && $odometerDiff !== null) {
            return $actualRouteKm - $odometerDiff;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedSocConsumption(?float $startSoc, ?float $endSoc, mixed $fallback): ?float
    {
        if ($startSoc !== null && $endSoc !== null) {
            return max(0, $startSoc - $endSoc);
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedSocPerKm(?float $socConsumption, ?float $actualRouteKm, mixed $fallback): ?float
    {
        if ($socConsumption !== null && $actualRouteKm && $actualRouteKm > 0) {
            return $socConsumption / $actualRouteKm;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedRunKmPerSoc(?float $actualRouteKm, ?float $socConsumption, mixed $fallback): ?float
    {
        if ($actualRouteKm !== null && $socConsumption && $socConsumption > 0) {
            return $actualRouteKm / $socConsumption;
        }

        return $this->nullableFloat($fallback);
    }

    private function calculatedDorKwhPerKmOdo(?float $dcrChargedSoc, ?float $socConsumption, ?float $odometerDiff): ?float
    {
        if ($dcrChargedSoc !== null && $socConsumption !== null && $odometerDiff !== null && $odometerDiff > 0) {
            return ($dcrChargedSoc * $socConsumption) / $odometerDiff / 100;
        }

        return null;
    }

    private function calculatedDorKwhPerKmAct(?float $dcrKwh, ?float $actualRouteKm): ?float
    {
        if ($dcrKwh !== null && $actualRouteKm !== null && $actualRouteKm > 0) {
            return $dcrKwh / $actualRouteKm;
        }

        return null;
    }

    private function calculatedDorKwh(?float $socConsumption, ?float $batterySizeKwh): ?float
    {
        if ($socConsumption !== null && $batterySizeKwh !== null) {
            return ($socConsumption * $batterySizeKwh) / 100;
        }

        return null;
    }

    private function dorPreviewGroups(TripSheetEntryDor $dor): array
    {
        return [
            'Basic Details' => [
                'Depot Name' => $this->dorDisplay($dor->depot_name),
                'Date' => $dor->dor_date?->format('d-M-Y') ?: '-',
                'Bus No' => $this->dorDisplay($dor->bus_no),
                'Route No' => $this->dorDisplay($dor->route_no),
                'Duty' => $this->dorDisplay($dor->duty),
                'Shift' => $this->dorDisplay($dor->shift),
                'Driver ID' => $this->dorDisplay($dor->driver_badge_no),
            ],
            'Time Details' => [
                'Schedule Start Time' => $this->dorTime($dor->schedule_start_time),
                'Schedule End Time' => $this->dorTime($dor->schedule_end_time),
                'Actual Start Time' => $this->dorTime($dor->actual_start_time),
                'Actual End Time' => $this->dorTime($dor->actual_end_time),
                'Start Punc.' => $this->dorDisplay($dor->start_punc),
                'Route Completion Time' => $this->dorTime($dor->route_completion_time),
            ],
            'Kilometer Details' => [
                'Schedule Km' => $this->dorDisplay($dor->schedule_km),
                'Route Km Loss' => $this->dorDisplay($dor->route_km_loss),
                'Act. Route Km' => $this->dorDisplay($dor->actual_route_km),
                'Schedule Trip' => $this->dorDisplay($dor->schedule_trip),
                'Actual Trip' => $this->dorDisplay($dor->actual_trip),
                'Miss Trip' => $this->dorDisplay($dor->miss_trip),
                'Odometer Start' => $this->dorDisplay($dor->odometer_start_reading),
                'Odometer End' => $this->dorDisplay($dor->odometer_end_reading),
                'Odometer Diff' => $this->dorDisplay($dor->odometer_diff_km),
                'Difference' => $this->dorDisplay($dor->difference),
            ],
            'Responsibility' => [
                'Account Responsible' => $this->dorDisplay($dor->account_responsible),
                'Reason For Kilometer Loss' => $this->dorDisplay($dor->reason_for_kilometer_loss),
                'After Sales Reason' => $this->dorDisplay($dor->after_sales_reason),
                'Penalty Infraction' => $this->dorDisplay($dor->penalty_infraction),
                'Remarks' => $this->dorDisplay($dor->remarks),
            ],
            'SOC / Energy' => [
                'Route Start SOC %' => $this->dorDisplay($dor->route_start_soc_percent),
                'Route End SOC %' => $this->dorDisplay($dor->route_end_soc_percent),
                'SOC Consumption' => $this->dorDisplay($dor->soc_consumption_on_route_percent),
                'SOC Per KM' => $this->dorDisplay($dor->soc_per_km),
                'Run KM per SOC' => $this->dorDisplay($dor->run_kilometer_per_soc),
                'DOR KWh/km' => $this->dorDisplay($dor->dor_kwh_per_km_odo),
                'DOR KWH/KM (ACT)' => $this->dorDisplay($dor->dor_kwh_per_km_act),
                'DCR KWh/km (odo)' => $this->dorDisplay($dor->dcr_kwh_per_km_odo),
                'DCR KWH/KM (ACT)' => $this->dorDisplay($dor->dcr_kwh_per_km_act),
                'DOR KWH' => $this->dorDisplay($dor->dor_kwh),
                'DCR KWH' => $this->dorDisplay($dor->dcr_kwh),
                'DCR Charged SOC' => $this->dorDisplay($dor->dcr_charged_soc),
                'Energy Absorption' => $this->dorDisplay($dor->energy_absorption),
                'Battery Size' => $this->dorDisplay($dor->battery_size_kwh),
            ],
            'Final Metrics' => [
                'VP1' => $this->dorDisplay($dor->vp1),
                'VP2' => $this->dorDisplay($dor->vp2),
                'DP' => $this->dorDisplay($dor->dp),
                'Penalty' => $this->dorDisplay($dor->penalty),
                'Model 9M/12M' => $this->dorDisplay($dor->model_9m_12m),
            ],
        ];
    }

    private function dorDisplay(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_numeric($value)) {
            $number = (float) $value;

            return floor($number) === $number
                ? (string) (int) $number
                : rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    private function dorTime(?string $value): string
    {
        return $value ? substr($value, 0, 5) : '-';
    }

    private function entryStartingPoint(TripSheetEntry $entry): string
    {
        $route = $entry->sheet?->trip?->route;

        return $entry->side === 'down'
            ? ($route?->endPoint?->name ?: '-')
            : ($route?->startPoint?->name ?: '-');
    }

    private function entryDestinationPoint(TripSheetEntry $entry): string
    {
        $route = $entry->sheet?->trip?->route;

        return $entry->side === 'down'
            ? ($route?->startPoint?->name ?: '-')
            : ($route?->endPoint?->name ?: '-');
    }

    private function sheetStartDelay(?string $startTime, ?string $actualStartTime): string
    {
        if (! $startTime || ! $actualStartTime) {
            return '-';
        }

        $scheduled = Carbon::createFromFormat('H:i', $this->formatSheetTime($startTime));
        $actual = Carbon::createFromFormat('H:i', $this->formatSheetTime($actualStartTime));
        $minutes = (int) round($scheduled->diffInMinutes($actual, false));

        $label = abs($minutes) === 1 ? 'min' : 'mins';

        return $minutes >= 0
            ? "{$minutes} {$label}"
            : abs($minutes) . " {$label} early";
    }

    private function sheetViewDorButtons(Trip $trip, TripSheetEntry $entry): string
    {
        $formUrl = route('trips.sheet.entries.dor', [$trip->id, $entry->id]);
        $previewUrl = $entry->dor
            ? route('trips.sheet.entries.dor.preview', [$trip->id, $entry->id])
            : null;

        if ($entry->dor?->is_completed && ! $this->canCompleteDor()) {
            return '<div class="d-flex justify-content-center gap-1">'
                . '<a href="' . e($previewUrl) . '" class="btn-edit btn-nowrap btn-cstm">View DOR</a>'
                . '</div>';
        }

        $primaryLabel = $entry->dor ? 'Edit DOR' : 'Create DOR';
        $previewButton = $previewUrl
            ? '<a href="' . e($previewUrl) . '" class="btn-edit btn-nowrap btn-cstm" style="background-color: #b23939;">View DOR</a>'
            : '<a href="#!" class="btn-edit btn-nowrap btn-cstm disabled" style="background-color: #b23939; opacity: .65; pointer-events: none;">View DOR</a>';

        return '<div class="d-flex justify-content-center gap-1">'
            . '<a href="' . e($formUrl) . '" class="btn-edit btn-nowrap btn-cstm">' . $primaryLabel . '</a>'
            . $previewButton
            . '</div>';
    }

    private function assignmentForDate(Trip $trip, ?string $date): ?TripAssignment
    {
        if (! $date) {
            return null;
        }

        $date = Carbon::parse($date);
        $trip->loadMissing(['assignments.driverProfile.user', 'assignments.vehicle']);

        return $trip->assignments
            ->first(fn(TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function formData(array $extra = []): array
    {
        return $extra + [
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'routes' => RouteModel::with(['startPoint', 'endPoint', 'stops'])->orderBy('route_name')->get(),
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'name']),
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
            'trip_order_sequence_no',
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
            'is_verified_by_controller',
            'verified_by_controller',
            'verified_by_controller_at',
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

        $header = array_map(fn($value) => Str::of((string) $value)->trim()->lower()->replace(' ', '_')->toString(), $header);
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
                ->map(fn($value) => is_string($value) ? trim($value) : $value)
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

            if (($data['trip_order_sequence_no'] ?? '') !== '' && (! ctype_digit((string) $data['trip_order_sequence_no']))) {
                $errors[] = "Row {$line}: trip_order_sequence_no must be a whole number.";
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

            foreach (['vehicle_verified_at', 'driver_verified_at', 'verified_by_supervisor_at', 'verified_by_controller_at'] as $field) {
                if (($data[$field] ?? '') !== '' && ! $this->csvDateTime($data[$field])) {
                    $errors[] = "Row {$line}: {$field} must be a valid date/time in DD-MM-YYYY HH:MM format.";
                }
            }

            foreach (['vehicle_verified_by', 'driver_verified_by', 'verified_by_supervisor', 'verified_by_controller'] as $field) {
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
                'trip_order_sequence_no' => ($data['trip_order_sequence_no'] ?? '') !== '' ? (int) $data['trip_order_sequence_no'] : null,
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
                'is_verified_by_controller' => $this->csvBoolean($data['is_verified_by_controller'] ?? null),
                'verified_by_controller' => ($data['verified_by_controller'] ?? '') ?: null,
                'verified_by_controller_at' => $this->csvDateTime($data['verified_by_controller_at'] ?? null),
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
            ->when($trip->depot_id, fn($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->get()
            ->pluck('user.name')
            ->filter();

        $supervisors = SupervisorProfile::with('user')
            ->when($trip->depot_id, fn($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn($query) => $query->where('is_active', true))
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
            'trip_order_sequence_no' => $data['trip_order_sequence_no'] ?? null,
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
            'is_verified_by_controller' => (bool) ($data['is_verified_by_controller'] ?? false),
            'verified_by_controller' => $data['verified_by_controller'] ?? null,
            'verified_by_controller_at' => $this->normalizeDateTime($data['verified_by_controller_at'] ?? null),
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

        $query = DriverProfile::whereHas('user', fn($userQuery) => $userQuery->where('is_active', true));

        if ($driverCode) {
            $driver = (clone $query)->whereHas('user', fn($userQuery) => $userQuery->where('code', $driverCode))->first();

            return $driver
                ? [$driver->id, null]
                : [null, 'active driver not found for driver_code.'];
        }

        $drivers = $query->whereHas('user', fn($userQuery) => $userQuery->where('name', $driverName))->limit(2)->get();

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
            ->when($trip->depot_id, fn($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn($query) => $query->where('is_active', true)->where('name', $name))
            ->exists();
    }

    private function supervisorNameExists(Trip $trip, string $name): bool
    {
        return SupervisorProfile::query()
            ->when($trip->depot_id, fn($query) => $query->where('depot_id', $trip->depot_id))
            ->whereHas('user', fn($query) => $query->where('is_active', true)->where('name', $name))
            ->exists();
    }

    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn($value) => trim((string) $value) === '');
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
            'Controller Verified' => $entry->is_verified_by_controller ? 'Yes' : 'No',
            'Controller Verified By' => $entry->verified_by_controller ?: '-',
        ], 165);

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
