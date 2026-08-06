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
use App\Models\Route as RouteModel;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\TripAssignment;
use App\Models\TripNature;
use App\Models\TripSheet;
use App\Models\TripSheetEntry;
use App\Models\TripSheetEntryDor;
use App\Models\Vehicle;
use App\Models\VehicleClassification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            new Middleware(PermissionMiddleware::using('trips.sheet'), ['sheet', 'createSheetEntry', 'editSheetEntry', 'duplicateSheetEntry', 'storeSheet', 'destroySheetEntry', 'sheetView', 'importSheetForm', 'importSheet', 'sampleSheetExcel', 'dorForm', 'storeDor', 'dorPreview']),
            new Middleware(PermissionMiddleware::using('trips.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = $this->filteredQuery();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
                ->addColumn('route_name', fn ($row) => $row->route?->route_name ?? '')
                ->addColumn('trip_title', fn ($row) => $row->trip_title ?: '-')
                ->addColumn('from_location', fn ($row) => $row->route?->startPoint?->name ?? '-')
                ->addColumn('to_location', fn ($row) => $row->route?->endPoint?->name ?? '-')
                ->addColumn('state_name', fn ($row) => $row->state?->name ?? '-')
                ->addColumn('depot_name', fn ($row) => $row->depot?->name ?? '-')
                ->addColumn('classification_name', fn ($row) => $row->vehicleClassification?->title ?? '-')
                ->addColumn('nature_name', fn ($row) => $row->tripNature?->title ?? '-')
                ->addColumn('from_date_text', fn ($row) => $row->from_date?->format('d M Y') ?? '-')
                ->addColumn('to_date_text', fn ($row) => $row->to_date?->format('d M Y') ?? '-')
                ->addColumn('status', fn ($row) => $this->statusBadge($row->status))
                ->addColumn('action', fn ($row) => view('trip.partials.action', compact('row'))->render())
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('trip.index', [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'statuses' => collect(Trip::STATUSES)->only(['Active', 'Inactive'])->all(),
        ]);
    }

    public function completedTrips(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of($this->completedTripEntriesQuery($request))
                ->addIndexColumn()
                ->editColumn('code', fn ($entry) => $entry->code ?: '-')
                ->addColumn('trip_date', fn ($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->editColumn('service_code', fn ($entry) => $entry->service_code ?: '-')
                ->editColumn('round_no', fn ($entry) => $entry->round_no ?: '-')
                ->editColumn('trip_nature', fn ($entry) => $entry->trip_nature ?: '-')
                ->editColumn('schedule_km', fn ($entry) => $entry->schedule_km !== null ? $entry->schedule_km : '-')
                ->editColumn('departure_time', fn ($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('arrival_time', fn ($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('driver_name', fn ($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle_no', fn ($entry) => $this->entryVehicleNo($entry))
                ->editColumn('starting_km', fn ($entry) => $entry->starting_km ?? '-')
                ->editColumn('ending_km', fn ($entry) => $entry->ending_km ?? '-')
                ->editColumn('starting_electric_charge', fn ($entry) => $entry->starting_electric_charge !== null ? $entry->starting_electric_charge.'%' : '-')
                ->editColumn('ending_electric_charge', fn ($entry) => $entry->ending_electric_charge !== null ? $entry->ending_electric_charge.'%' : '-')
                ->editColumn('is_vehicle_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_vehicle_verified))
                ->editColumn('is_driver_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_driver_verified))
                ->addColumn('action', fn ($entry) => '<div class="action-btns"><a href="'.e(route('completed.trips.view', $entry->id)).'" class="btn-view" title="View"><i class="fa-solid fa-eye"></i></a></div>')
                ->rawColumns(['driver_name', 'vehicle_no', 'is_vehicle_verified', 'is_driver_verified', 'action'])
                ->make(true);
        }

        return view('trip.completed', [
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::orderBy('vehicle_no')->get(['id', 'vehicle_no']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
            'controllers' => ControllerProfile::with('user')->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
            'supervisors' => SupervisorProfile::with('user')->whereHas('user', fn ($query) => $query->where('is_active', true))->get(),
            'trips' => Trip::orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function completedTripsExport(Request $request)
    {
        return Excel::download(
            new CompletedTripSheetExport($this->completedTripEntriesQuery($request)),
            'completed-trip-sheet.xlsx'
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
        abort_unless(in_array($tripSheetEntry->status, ['verification_completed', 'trip_completed'], true), 404);

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
        abort_unless(in_array($tripSheetEntry->status, ['verification_completed', 'trip_completed'], true), 404);

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
        $fileName = ($tripSheetEntry->sheet?->code ?: 'completed-trip').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
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
        $route = RouteModel::findOrFail($data['route_id']);
        $data['title'] = $route->route_name;
        $data['trip_side'] = 'both';
        $data['from_depot_id'] = null;
        $data['to_depot_id'] = null;
        $data['status'] = $data['status'] ?? 'Active';
        $data['is_active'] = $data['status'] === 'Active';
        $data['cancellation_reason'] = null;

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
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ]);

        $trip = Trip::findOrFail($request->id);
        $trip->update([
            'status' => $validated['status'],
            'cancellation_reason' => null,
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
                ->editColumn('code', fn ($entry) => $entry->code ?: '-')
                ->editColumn('status', fn ($entry) => $this->sheetStatusBadge($entry->status))
                ->editColumn('service_code', fn ($entry) => $entry->service_code ?: '-')
                ->editColumn('round_no', fn ($entry) => $entry->round_no ?: '-')
                ->editColumn('trip_nature', fn ($entry) => $entry->trip_nature ?: '-')
                ->editColumn('schedule_km', fn ($entry) => $entry->schedule_km !== null ? $entry->schedule_km : '-')
                ->editColumn('departure_time', fn ($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('arrival_time', fn ($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('driver_name', fn ($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle_no', fn ($entry) => $this->entryVehicleNo($entry))
                ->editColumn('trip_order_sequence_no', fn ($entry) => $entry->trip_order_sequence_no ?? '-')
                ->editColumn('starting_km', fn ($entry) => $entry->starting_km ?? '-')
                ->editColumn('ending_km', fn ($entry) => $entry->ending_km ?? '-')
                ->editColumn('starting_electric_charge', fn ($entry) => $entry->starting_electric_charge !== null ? $entry->starting_electric_charge.'%' : '-')
                ->editColumn('ending_electric_charge', fn ($entry) => $entry->ending_electric_charge !== null ? $entry->ending_electric_charge.'%' : '-')
                ->editColumn('is_vehicle_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_vehicle_verified))
                ->editColumn('is_driver_verified', fn ($entry) => $this->yesNoBadge((bool) $entry->is_driver_verified))
                ->addColumn('action', fn ($entry) => $this->sheetEntryActionButtons($trip, $entry))
                ->rawColumns(['vehicle_no', 'driver_name', 'status', 'is_vehicle_verified', 'is_driver_verified', 'action'])
                ->make(true);
        }

        return view('trip.sheet', array_merge($this->assignmentData($trip), [
            'record' => $trip->load([
                'route.startPoint',
                'route.endPoint',
                'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
            ]),
        ]));
    }

    public function createSheetEntry(Trip $trip)
    {
        return view('trip.sheet-form', $this->sheetFormData($trip, null, 'create'));
    }

    public function editSheetEntry(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        return view('trip.sheet-form', $this->sheetFormData($trip, $tripSheetEntry->load(['sheet', 'scheduleStopTimes']), 'edit'));
    }

    public function duplicateSheetEntry(Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        return view('trip.sheet-form', $this->sheetFormData($trip, $tripSheetEntry->load(['sheet', 'scheduleStopTimes']), 'duplicate'));
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
            'fromDepot.state',
            'toDepot.state',
            'createdBy',
            'assignments.driverProfile.user',
            'assignments.vehicle.oem',
            'assignments.vehicle.branch',
        ]);
        $query = TripSheetEntry::query()
            ->with(['sheet.trip.route.startPoint', 'sheet.trip.route.endPoint', 'sheet.trip.assignments.driverProfile.user', 'driverProfile.user', 'vehicle', 'dor'])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->select('trip_sheet_entries.*')
            ->orderBy('trip_sheets.date', 'desc')
            ->orderBy('trip_sheet_entries.side');

        if ($request->filled('date_from')) {
            $query->whereDate('trip_sheets.date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trip_sheets.date', '<=', $request->date_to);
        }

        if ($request->filled('entry_date')) {
            $query->whereDate('trip_sheets.date', $request->entry_date);
        }

        if ($request->filled('ser_search')) {
            $query->where('trip_sheet_entries.service_code', 'like', '%'.trim((string) $request->ser_search).'%');
        }

        if ($request->input('export') === 'csv') {
            $entries = $query->get();
            $fileName = ($trip->code ?: 'trip').'-sheet.csv';

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
                        $entry->code,
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
                ->addColumn('trip_date', fn ($entry) => $entry->sheet?->date?->format('d M Y') ?: '-')
                ->addColumn('trip_code', fn ($entry) => $entry->sheet?->code ?: '-')
                ->addColumn('starting_from', fn ($entry) => $this->entryStartingPoint($entry))
                ->addColumn('destination_point', fn ($entry) => $this->entryDestinationPoint($entry))
                ->editColumn('departure_time', fn ($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('arrival_time', fn ($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->addColumn('shift', fn ($entry) => ucfirst((string) $entry->side))
                ->addColumn('driver', fn ($entry) => $this->entryDriverName($entry))
                ->addColumn('vehicle', fn ($entry) => $this->entryVehicleNo($entry))
                ->addColumn('delay', fn ($entry) => $this->sheetStartDelay($entry->departure_time, $entry->actual_start_time))
                ->addColumn('action', fn ($entry) => $this->sheetViewDorButtons($trip, $entry))
                ->rawColumns(['action'])
                ->make(true);
        }

        $perPage = in_array((int) $request->input('per_page'), [10, 15, 25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 15;

        return view('trip.sheet-view', [
            'record' => $trip,
            'entries' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['entry_date', 'ser_search', 'per_page']),
        ]);
    }

    public function importSheetForm(Trip $trip)
    {
        return view('trip.sheet-import', [
            'entryScheduleKm' => $this->tripSheetEntryScheduleKm($trip),
            'record' => $trip->load([
                'tripNature',
                'route.startPoint',
                'route.endPoint',
                'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
            ]),
        ]);
    }

    public function importSheet(Request $request, Trip $trip)
    {
        $request->validate([
            'sheet_file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ]);

        $rows = $this->readConfiguredTripSheet($trip, $request->file('sheet_file')->getRealPath());
        if (! $trip->from_date || ! $trip->to_date) {
            throw ValidationException::withMessages([
                'sheet_file' => 'The trip must have both a from date and a to date before importing.',
            ]);
        }

        $dates = CarbonPeriod::create($trip->from_date, $trip->to_date);
        $dateCount = (int) $trip->from_date->diffInDays($trip->to_date) + 1;
        $entriesPerDate = count($rows);
        $entryCount = $entriesPerDate * $dateCount;

        DB::transaction(function () use ($trip, $rows, $dates) {
            Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            DB::table('trip_sheet_entry_stop_times')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('trip_sheet_entries')
                        ->whereColumn('trip_sheet_entries.id', 'trip_sheet_entry_stop_times.trip_sheet_entry_id');
                })
                ->delete();
            $trip->sheets()
                ->whereBetween('date', [$trip->from_date->toDateString(), $trip->to_date->toDateString()])
                ->delete();
            $entryNumber = $this->lastTripSheetEntryNumber($trip);

            foreach ($dates as $date) {
                $sheet = $this->sheetForDate($trip, $date->toDateString(), 'pending');

                foreach ($rows as $row) {
                    $entry = $sheet->entries()->create([
                        'code' => $this->tripSheetEntryCode($trip, ++$entryNumber),
                        'status' => 'pending',
                        'side' => null,
                        'trip_order_sequence_no' => $row['sequence'],
                        'service_code' => $row['service_code'],
                        'round_no' => $row['round_no'],
                        'trip_nature' => $row['trip_nature'],
                        'schedule_km' => $row['schedule_km'],
                        'departure_time' => $row['departure_time'],
                        'arrival_time' => $row['arrival_time'],
                    ]);

                    $entry->scheduleStopTimes()->createMany(
                        collect($row['stop_times'])->map(fn (array $stopTime, int $index) => [
                            'location_id' => $stopTime['location_id'],
                            'route_stop_id' => $stopTime['route_stop_id'],
                            'sequence_no' => $index + 1,
                            'location_name' => $stopTime['location'],
                            'event' => $stopTime['event'],
                            'show_location' => $stopTime['show_location'],
                            'scheduled_time' => $stopTime['time'],
                        ])->all()
                    );
                }
            }
        });

        return redirect()
            ->route('trips.sheet.import.form', $trip->id)
            ->with('success', $entryCount.' trip sheet entries imported successfully.')
            ->with('import_summary', [
                'from_date' => $trip->from_date->format('d M Y'),
                'to_date' => $trip->to_date->format('d M Y'),
                'date_count' => $dateCount,
                'service_count' => max(1, (int) $trip->total_trips),
                'rounds_per_service' => max(1, (int) $trip->rounds_per_trip),
                'entries_per_date' => $entriesPerDate,
                'total_entries' => $entryCount,
            ]);
    }

    public function sampleSheetExcel(Trip $trip)
    {
        $spreadsheet = $this->configuredTripSheetTemplate($trip);

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            ($trip->code ?: 'trip').'-trip-sheet.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function storeSheet(Request $request, Trip $trip)
    {
        $verifierNames = $this->verifierNames($trip);

        $validated = $request->validate([
            'entry_id' => ['nullable', 'integer', 'exists:trip_sheet_entries,id'],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(TripSheet::STATUSES))],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'actual_start_time' => ['nullable', 'date_format:H:i'],
            'actual_reach_time' => ['nullable', 'date_format:H:i'],
            'driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'trip_order_sequence_no' => ['nullable', 'integer', 'min:0'],
            'service_code' => ['required', 'string', 'max:255'],
            'round_no' => ['required', 'integer', Rule::in(range(1, max(1, (int) $trip->rounds_per_trip)))],
            'trip_nature' => ['required', 'string', 'max:255'],
            'schedule_km' => ['required', 'numeric', 'min:0'],
            'stop_times' => ['required', 'array', 'min:2'],
            'stop_times.*.location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'stop_times.*.route_stop_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'stop_times.*.location_name' => ['required', 'string', 'max:255'],
            'stop_times.*.event' => ['required', Rule::in(['arrival', 'departure'])],
            'stop_times.*.show_location' => ['required', 'boolean'],
            'stop_times.*.scheduled_time' => ['required', 'date_format:H:i'],
            'starting_km' => ['nullable', 'integer', 'min:0'],
            'ending_km' => ['nullable', 'integer', 'min:0', 'gte:starting_km'],
            'starting_electric_charge' => ['nullable', 'integer', 'min:0', 'max:100'],
            'ending_electric_charge' => ['nullable', 'integer', 'min:0', 'max:100'],
            'vehicle_condition' => ['nullable', 'string'],
            'energy_status' => ['nullable', 'boolean'],
            'accident_status' => ['nullable', 'boolean'],
            'accident_remarks' => ['nullable', 'string'],
            'vehicle_breakdown' => ['nullable', 'boolean'],
            'medical_emergency' => ['nullable', 'boolean'],
            'passenger_issue' => ['nullable', 'boolean'],
            'security_threat' => ['nullable', 'boolean'],
            'is_vehicle_verified' => ['nullable', 'boolean'],
            'vehicle_verified_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'vehicle_verified_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_driver_verified' => ['nullable', 'boolean'],
            'driver_verified_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'driver_verified_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_initial_verified' => ['nullable', 'boolean'],
            'initial_verification_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'initial_verification_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'is_final_verified' => ['nullable', 'boolean'],
            'final_verification_by' => ['nullable', 'string', 'max:255', Rule::in($verifierNames)],
            'final_verification_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'notes' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($validated['date']);

        if (($trip->from_date && $date->lt($trip->from_date)) || ($trip->to_date && $date->gt($trip->to_date))) {
            throw ValidationException::withMessages(['date' => 'Date must be within the trip date range.']);
        }

        DB::transaction(function () use ($trip, $validated) {
            $sheet = $this->sheetForDate($trip, $validated['date']);
            $entry = null;
            $stopTimes = $validated['stop_times'];
            unset($validated['stop_times']);

            if (! empty($validated['entry_id'])) {
                $entry = TripSheetEntry::whereKey($validated['entry_id'])
                    ->whereHas('sheet', fn ($query) => $query->where('trip_id', $trip->id))
                    ->firstOrFail();
            }

            $payload = $this->entryPayload($trip, $validated);

            if ($entry) {
                $oldSheet = $entry->sheet;
                $entry->update(['trip_sheet_id' => $sheet->id] + $payload);

                if ($oldSheet && $oldSheet->id !== $sheet->id && $oldSheet->entries()->count() === 0) {
                    $oldSheet->delete();
                }
            } else {
                Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
                $payload['code'] = $this->tripSheetEntryCode($trip, $this->lastTripSheetEntryNumber($trip) + 1);
                $entry = $sheet->entries()->create($payload);
            }

            $entry->scheduleStopTimes()->delete();
            $entry->scheduleStopTimes()->createMany(
                collect($stopTimes)->values()->map(fn (array $stopTime, int $index) => [
                    'location_id' => $stopTime['location_id'] ?? null,
                    'route_stop_id' => $stopTime['route_stop_id'] ?? null,
                    'sequence_no' => $index + 1,
                    'location_name' => $stopTime['location_name'],
                    'event' => $stopTime['event'],
                    'show_location' => (bool) $stopTime['show_location'],
                    'scheduled_time' => $stopTime['scheduled_time'],
                ])->all()
            );
        });

        return redirect()->route('trips.sheet', $trip->id)->with('success', 'Trip sheet entry saved successfully.');
    }

    public function destroySheetEntry(Request $request, Trip $trip, TripSheetEntry $tripSheetEntry)
    {
        abort_unless($tripSheetEntry->sheet?->trip_id === $trip->id, 404);

        $sheet = $tripSheetEntry->sheet;
        $tripSheetEntry->delete();

        if ($sheet && $sheet->entries()->count() === 0) {
            $sheet->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trip sheet entry deleted successfully.',
            ]);
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
        $query = Trip::with(['route.startPoint', 'route.endPoint', 'depot', 'state', 'vehicleClassification', 'tripNature'])
            ->select('trips.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%'.$search.'%')
                    ->orWhere('title', 'like', '%'.$search.'%')
                    ->orWhereHas('route', fn ($routeQuery) => $routeQuery->where('route_name', 'like', '%'.$search.'%'));
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

        if (request()->filled('status') && in_array(request('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', request('status'));
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
            ->whereIn('trip_sheet_entries.status', ['verification_completed', 'trip_completed'])
            ->select('trip_sheet_entries.*');

        if ($request->filled('trip_id')) {
            $query->where('trips.id', $request->trip_id);
        }

        if ($request->filled('ser_search')) {
            $query->where('trip_sheet_entries.service_code', 'like', '%'.trim((string) $request->ser_search).'%');
        }

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
            $query->where('trip_sheet_entries.service_code', 'like', '%'.$search.'%');
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
            ->where('trip_sheets.status', 'verification_completed')
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
                ->orWhere('trip_sheet_entries.initial_verification_by', $name)
                ->orWhere('trip_sheet_entries.final_verification_by', $name);
        });
    }

    private function sheetEntriesQuery(Trip $trip)
    {
        $query = TripSheetEntry::query()
            ->with(['sheet.trip.assignments.driverProfile.user', 'sheet.trip.assignments.vehicle', 'driverProfile.user', 'vehicle'])
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->where('trip_sheets.trip_id', $trip->id)
            ->select('trip_sheet_entries.*');

        if (request()->filled('entry_date')) {
            $query->whereDate('trip_sheets.date', request('entry_date'));
        }

        if (request()->filled('ser_search')) {
            $query->where('trip_sheet_entries.service_code', 'like', '%'.trim((string) request('ser_search')).'%');
        }

        return $query->orderByDesc('trip_sheet_entries.id');
    }

    private function sheetEntryActionButtons(Trip $trip, TripSheetEntry $entry): string
    {
        $editUrl = route('trips.sheet.entries.edit', [$trip->id, $entry->id]);
        $duplicateUrl = route('trips.sheet.entries.duplicate', [$trip->id, $entry->id]);
        $deleteUrl = route('trips.sheet.entries.destroy', [$trip->id, $entry->id]);

        return '<div class="d-flex justify-content-center gap-1">'
            .'<a href="'.e($editUrl).'" class="btn btn-sm btn-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>'
            .'<a href="'.e($duplicateUrl).'" class="btn btn-sm btn-info text-white" title="Duplicate"><i class="fa-regular fa-copy"></i></a>'
            .'<form method="POST" action="'.e($deleteUrl).'" class="d-inline delete-sheet-entry">'
            .csrf_field()
            .method_field('DELETE')
            .'<button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>'
            .'</form>'
            .'</div>';
    }

    private function sheetEntryPayload(TripSheetEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'code' => $entry->code,
            'date' => $entry->sheet?->date?->format('Y-m-d'),
            'status' => $entry->status,
            'departure_time' => $this->formatSheetTime($entry->departure_time),
            'arrival_time' => $this->formatSheetTime($entry->arrival_time),
            'actual_start_time' => $this->formatSheetTime($entry->actual_start_time),
            'actual_reach_time' => $this->formatSheetTime($entry->actual_reach_time),
            'driver_profile_id' => $entry->driver_profile_id,
            'vehicle_id' => $entry->vehicle_id,
            'trip_order_sequence_no' => $entry->trip_order_sequence_no,
            'service_code' => $entry->service_code,
            'round_no' => $entry->round_no,
            'trip_nature' => $entry->trip_nature,
            'schedule_km' => $entry->schedule_km,
            'starting_km' => $entry->starting_km,
            'ending_km' => $entry->ending_km,
            'starting_electric_charge' => $entry->starting_electric_charge,
            'ending_electric_charge' => $entry->ending_electric_charge,
            'vehicle_condition' => $entry->vehicle_condition,
            'is_vehicle_verified' => $entry->is_vehicle_verified,
            'vehicle_verified_by' => $entry->vehicle_verified_by,
            'vehicle_verified_at' => $entry->vehicle_verified_at?->format('Y-m-d\TH:i'),
            'is_driver_verified' => $entry->is_driver_verified,
            'driver_verified_by' => $entry->driver_verified_by,
            'driver_verified_at' => $entry->driver_verified_at?->format('Y-m-d\TH:i'),
            'is_initial_verified' => $entry->is_initial_verified,
            'initial_verification_by' => $entry->initial_verification_by,
            'initial_verification_at' => $entry->initial_verification_at?->format('Y-m-d\TH:i'),
            'is_final_verified' => $entry->is_final_verified,
            'final_verification_by' => $entry->final_verification_by,
            'final_verification_at' => $entry->final_verification_at?->format('Y-m-d\TH:i'),
            'notes' => $entry->notes,
        ];
    }

    private function entryDriverName(TripSheetEntry $entry): string
    {
        $assignment = self::assignmentForCompletedEntry($entry);

        return $entry->driverProfile?->user?->name
            ?: $assignment?->driverProfile?->user?->name
            ?: '<span class="badge bg-danger">Not Assigned</span>';
    }

    private function entryVehicleNo(TripSheetEntry $entry): string
    {
        $assignment = self::assignmentForCompletedEntry($entry);

        return $entry->vehicle?->vehicle_no
            ?: $assignment?->vehicle?->vehicle_no
            ?: '<span class="badge bg-danger">Not Assigned</span>';
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
            'depot_name' => $trip?->depot?->name ?? $trip?->fromDepot?->name,
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
        $directory = 'trip-dor-odometer/'.$entry->id;

        foreach (
            [
                'odometer_start_image' => 'odometer_start_image_path',
                'odometer_end_image' => 'odometer_end_image_path',
                'route_start_soc_percent_image' => 'route_start_soc_percent_image',
                'route_end_soc_percent_image' => 'route_end_soc_percent_image',
            ] as $input => $column
        ) {
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
            ['label' => 'Shift', 'name' => 'shift', 'type' => 'text', 'value' => $values['shift']],
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
            : abs($minutes)." {$label} early";
    }

    private function sheetViewDorButtons(Trip $trip, TripSheetEntry $entry): string
    {
        $formUrl = route('trips.sheet.entries.dor', [$trip->id, $entry->id]);
        $previewUrl = $entry->dor
            ? route('trips.sheet.entries.dor.preview', [$trip->id, $entry->id])
            : null;

        if ($entry->dor?->is_completed && ! $this->canCompleteDor()) {
            return '<div class="d-flex justify-content-center gap-1">'
                .'<a href="'.e($previewUrl).'" class="btn-edit btn-nowrap btn-cstm">View DOR</a>'
                .'</div>';
        }

        $primaryLabel = $entry->dor ? 'Edit DOR' : 'Create DOR';
        $previewButton = $previewUrl
            ? '<a href="'.e($previewUrl).'" class="btn-edit btn-nowrap btn-cstm" style="background-color: #b23939;">View DOR</a>'
            : '<a href="#!" class="btn-edit btn-nowrap btn-cstm disabled" style="background-color: #b23939; opacity: .65; pointer-events: none;">View DOR</a>';

        return '<div class="d-flex justify-content-center gap-1">'
            .'<a href="'.e($formUrl).'" class="btn-edit btn-nowrap btn-cstm">'.$primaryLabel.'</a>'
            .$previewButton
            .'</div>';
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
            'routes' => RouteModel::with(['startPoint', 'endPoint', 'stops' => fn ($query) => $query->with('location')->orderBy('position')])->where('status', 'Active')->orderBy('route_name')->get(),
            'depots' => Depot::orderBy('name')->get(['id', 'name']),
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'vehicleClassifications' => VehicleClassification::where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'tripNatures' => TripNature::where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'statuses' => collect(Trip::STATUSES)->only(['Active', 'Inactive'])->all(),
        ];
    }

    private function assignmentData(Trip $trip): array
    {
        return [
            'record' => $trip->load([
                'route.startPoint',
                'route.endPoint',
                'depot',
                'fromDepot',
                'toDepot',
                'assignments.vehicle',
                'assignments.driverProfile.user',
            ]),
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_no', 'vehicle_type']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
        ];
    }

    private function sheetFormData(Trip $trip, ?TripSheetEntry $entry, string $mode): array
    {
        $trip->loadMissing([
            'tripNature',
            'route.startPoint',
            'route.endPoint',
            'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
        ]);

        $stopTimes = $entry?->scheduleStopTimes?->isNotEmpty()
            ? $entry->scheduleStopTimes->map(fn ($stopTime) => [
                'location_id' => $stopTime->location_id,
                'route_stop_id' => $stopTime->route_stop_id,
                'location_name' => $stopTime->location_name,
                'event' => $stopTime->event,
                'show_location' => $stopTime->show_location,
                'scheduled_time' => $this->formatSheetTime($stopTime->scheduled_time),
            ])->all()
            : collect($this->configuredTripSheetRows($trip))
                ->where('round_no', 1)
                ->map(fn (array $stopTime) => [
                    'location_id' => $stopTime['location_id'],
                    'route_stop_id' => $stopTime['route_stop_id'],
                    'location_name' => $stopTime['location'],
                    'event' => $stopTime['event'],
                    'show_location' => $stopTime['show_location'],
                    'scheduled_time' => null,
                ])->values()->all();

        if (! $entry && $stopTimes) {
            $stopTimes[0]['scheduled_time'] = $this->formatSheetTime($trip->start_time);
            $stopTimes[array_key_last($stopTimes)]['scheduled_time'] = $this->formatSheetTime($trip->end_time);
        }

        return $this->assignmentData($trip) + [
            'record' => $trip->load([
                'route.startPoint',
                'route.endPoint',
                'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
                'tripNature',
                'depot',
                'fromDepot',
                'toDepot',
            ]),
            'entry' => $entry,
            'mode' => $mode,
            'statuses' => TripSheet::STATUSES,
            'verifiers' => $this->verifierNames($trip),
            'roundOptions' => range(1, max(1, (int) $trip->rounds_per_trip)),
            'entryScheduleKm' => $this->tripSheetEntryScheduleKm($trip),
            'stopTimes' => $stopTimes,
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

    private function configuredTripSheetTemplate(Trip $trip): Spreadsheet
    {
        $trip->loadMissing([
            'tripNature',
            'route.startPoint',
            'route.endPoint',
            'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
        ]);
        $totalTrips = max(1, (int) $trip->total_trips);
        $lastColumn = Coordinate::stringFromColumnIndex($totalTrips + 1);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trip Sheet');

        $sheet->mergeCells("A1:{$lastColumn}1")->setCellValue('A1', $this->configuredTripSheetTitle($trip));
        $sheet->mergeCells("A2:{$lastColumn}2")->setCellValue('A2', 'Trip Code: '.($trip->code ?: '-'));
        $sheet->fromArray(['SL. NO', ...range(1, $totalTrips)], null, 'A4');
        $sheet->setCellValue('A5', 'SER');
        $sheet->setCellValue('A6', 'NAT');
        $sheet->setCellValue('A7', 'KMS');
        $entryScheduleKm = $this->tripSheetEntryScheduleKm($trip);

        for ($index = 1; $index <= $totalTrips; $index++) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}5", 'ED'.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
            $sheet->setCellValue("{$column}6", $trip->tripNature?->title ?: '');
            $sheet->setCellValue("{$column}7", $entryScheduleKm);
        }

        $configuredRows = $this->configuredTripSheetRows($trip);
        foreach ($configuredRows as $offset => $row) {
            $excelRow = 8 + $offset;
            $sheet->setCellValue("A{$excelRow}", $row['show_location'] ? $row['location'] : '');
            if ($row['show_location'] && isset($configuredRows[$offset + 1]) && ! $configuredRows[$offset + 1]['show_location']) {
                $sheet->mergeCells("A{$excelRow}:A".($excelRow + 1));
            }
            $sheet->getStyle("B{$excelRow}:{$lastColumn}{$excelRow}")->getNumberFormat()->setFormatCode('hh:mm');
        }

        $lastRow = 7 + count($configuredRows);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A1:{$lastColumn}2")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A1:{$lastColumn}7")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A4:{$lastColumn}7")->getFont()->setBold(true);
        $sheet->getStyle("A4:{$lastColumn}7")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAF7');
        $sheet->getColumnDimension('A')->setWidth(24);
        for ($index = 2; $index <= $totalTrips + 1; $index++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(13);
        }
        $sheet->freezePane('B8');

        return $spreadsheet;
    }

    private function configuredTripSheetRows(Trip $trip): array
    {
        $route = $trip->route;
        if (! $route?->startPoint || ! $route?->endPoint) {
            throw ValidationException::withMessages(['sheet_file' => 'The selected route must have starting and ending depots.']);
        }

        $outward = collect([[
            'point' => $route->startPoint,
            'location_id' => $route->startPoint->location_id,
            'route_stop_id' => null,
        ]])
            ->concat($route->stops->map(fn ($stop) => [
                'point' => $stop->location ?? $stop,
                'location_id' => $stop->location_id,
                'route_stop_id' => $stop->id,
            ]))
            ->push([
                'point' => $route->endPoint,
                'location_id' => $route->endPoint->location_id,
                'route_stop_id' => null,
            ])
            ->values();
        $rows = [];
        $roundPoints = $outward->concat($outward->reverse()->skip(1))->values();
        $lastIndex = $roundPoints->count() - 1;

        for ($roundNo = 1; $roundNo <= max(1, (int) $trip->rounds_per_trip); $roundNo++) {
            foreach ($roundPoints as $index => $routePoint) {
                $point = $routePoint['point'];
                $name = $point->short_name ?: $point->name;
                $details = [
                    'round_no' => $roundNo,
                    'location_id' => $routePoint['location_id'],
                    'route_stop_id' => $routePoint['route_stop_id'],
                ];
                if ($index === 0) {
                    $rows[] = $details + [
                        'location' => $name,
                        'event' => 'departure',
                        'show_location' => $roundNo === 1,
                    ];
                } elseif ($index === $lastIndex) {
                    $rows[] = $details + ['location' => $name, 'event' => 'arrival', 'show_location' => true];
                } else {
                    $rows[] = $details + ['location' => $name, 'event' => 'arrival', 'show_location' => true];
                    $rows[] = $details + ['location' => $name, 'event' => 'departure', 'show_location' => false];
                }
            }
        }

        return $rows;
    }

    private function configuredTripSheetTitle(Trip $trip): string
    {
        $title = $trip->trip_title ?: '-';
        $startCode = $trip->route?->startPoint?->short_name;
        $endCode = $trip->route?->endPoint?->short_name;

        return $startCode && $endCode
            ? "{$title} ({$startCode} TO {$endCode})"
            : $title;
    }

    private function readConfiguredTripSheet(Trip $trip, string $path): array
    {
        $trip->loadMissing([
            'tripNature',
            'route.startPoint',
            'route.endPoint',
            'route.stops' => fn ($query) => $query->with('location')->orderBy('position'),
        ]);
        $sheet = IOFactory::load($path)->getActiveSheet();
        $expectedRows = $this->configuredTripSheetRows($trip);
        $totalTrips = max(1, (int) $trip->total_trips);
        $errors = [];
        $result = [];
        $entryScheduleKm = $this->tripSheetEntryScheduleKm($trip);

        if (trim((string) $sheet->getCell('A1')->getValue()) !== $this->configuredTripSheetTitle($trip)) {
            $errors[] = 'The trip title does not match this trip configuration.';
        }
        if (! str_contains((string) $sheet->getCell('A2')->getValue(), (string) $trip->code)) {
            $errors[] = 'The trip code does not match this trip configuration.';
        }

        for ($tripIndex = 1; $tripIndex <= $totalTrips; $tripIndex++) {
            $column = Coordinate::stringFromColumnIndex($tripIndex + 1);
            $serviceCode = 'ED'.str_pad((string) $tripIndex, 2, '0', STR_PAD_LEFT);
            if ((int) $sheet->getCell("{$column}4")->getValue() !== $tripIndex) {
                $errors[] = "{$column}4 must contain serial number {$tripIndex}.";
            }
            if (trim((string) $sheet->getCell("{$column}5")->getValue()) !== $serviceCode) {
                $errors[] = "{$column}5 must contain {$serviceCode}.";
            }
            if (trim((string) $sheet->getCell("{$column}6")->getValue()) !== (string) ($trip->tripNature?->title ?: '')) {
                $errors[] = "{$column}6 must contain the configured trip nature.";
            }
            $times = [];
            foreach ($expectedRows as $offset => $configuration) {
                $excelRow = 8 + $offset;
                $cell = "{$column}{$excelRow}";
                if ($tripIndex === 1 && trim((string) $sheet->getCell("A{$excelRow}")->getValue()) !== ($configuration['show_location'] ? $configuration['location'] : '')) {
                    $errors[] = "A{$excelRow} does not match the configured route locations.";
                }
                $time = $this->configuredSheetTime($sheet->getCell($cell)->getValue());
                if ($time === null) {
                    $errors[] = "Enter a valid time in {$cell}.";
                }
                $times[] = $configuration + ['time' => $time];
            }

            foreach (collect($times)->groupBy('round_no') as $roundNo => $roundTimes) {
                $roundTimes = $roundTimes->values();
                $result[] = [
                    'sequence' => (($tripIndex - 1) * max(1, (int) $trip->rounds_per_trip)) + (int) $roundNo,
                    'service_code' => $serviceCode,
                    'round_no' => (int) $roundNo,
                    'trip_nature' => (string) ($trip->tripNature?->title ?: ''),
                    'schedule_km' => $entryScheduleKm,
                    'departure_time' => $roundTimes->first()['time'] ?? null,
                    'arrival_time' => $roundTimes->last()['time'] ?? null,
                    'stop_times' => $roundTimes->all(),
                ];
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['sheet_file' => array_slice($errors, 0, 25)]);
        }

        return $result;
    }

    private function configuredSheetTime(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('H:i');
        }

        $value = trim((string) $value);
        foreach (['H:i', 'H:i:s', 'g:i A', 'g:i a'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i');
            } catch (\Throwable) {
                // Try the next supported time format.
            }
        }

        return null;
    }

    private function sheetCsvHeaders(): array
    {
        return [
            'trip_date',
            'status',
            'departure_time',
            'arrival_time',
            'actual_start_time',
            'actual_reach_time',
            'trip_order_sequence_no',
            'starting_km',
            'ending_km',
            'starting_electric_charge',
            'ending_electric_charge',
            'vehicle_condition',
            'is_vehicle_verified',
            'vehicle_verified_by',
            'vehicle_verified_at',
            'is_driver_verified',
            'driver_verified_by',
            'driver_verified_at',
            'is_initial_verified',
            'initial_verification_by',
            'initial_verification_at',
            'is_final_verified',
            'final_verification_by',
            'final_verification_at',
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

            return [[], ['Missing required column(s): '.implode(', ', $missingHeaders).'.']];
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
        $verifierNames = $this->verifierNames($trip);

        foreach ($rows as $row) {
            $line = $row['line'];
            $data = collect($row['data'])
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            $date = $this->csvDate($data['trip_date'] ?? null);
            $status = strtolower($data['status'] ?? 'pending') ?: 'pending';
            $departureTime = $this->csvTime($data['departure_time'] ?? null);
            $arrivalTime = $this->csvTime($data['arrival_time'] ?? null);
            $actualStartTime = $this->csvTime($data['actual_start_time'] ?? null) ?: $this->formatSheetTime($trip->start_time);
            $actualReachTime = $this->csvTime($data['actual_reach_time'] ?? null) ?: $this->formatSheetTime($trip->end_time);
            $notes = $data['notes'] ?? null;

            if (! $date) {
                $errors[] = "Row {$line}: trip_date must be a valid date in DD-MM-YYYY format.";
            } elseif (($trip->from_date && $date->lt($trip->from_date)) || ($trip->to_date && $date->gt($trip->to_date))) {
                $errors[] = "Row {$line}: trip_date must be within the trip date range.";
            }

            if (! array_key_exists($status, TripSheet::STATUSES)) {
                $errors[] = "Row {$line}: status is not valid.";
            }

            foreach (['departure_time', 'arrival_time', 'actual_start_time', 'actual_reach_time'] as $field) {
                if (($data[$field] ?? '') !== '' && ! $this->csvTime($data[$field])) {
                    $errors[] = "Row {$line}: {$field} must be a valid time in HH:MM format.";
                }
            }

            if (($data['starting_km'] ?? '') !== '' && (! ctype_digit((string) $data['starting_km']))) {
                $errors[] = "Row {$line}: starting_km must be a whole number.";
            }
            if (($data['ending_km'] ?? '') !== '' && (! ctype_digit((string) $data['ending_km']))) {
                $errors[] = "Row {$line}: ending_km must be a whole number.";
            }

            if (($data['trip_order_sequence_no'] ?? '') !== '' && (! ctype_digit((string) $data['trip_order_sequence_no']))) {
                $errors[] = "Row {$line}: trip_order_sequence_no must be a whole number.";
            }

            if (($data['starting_electric_charge'] ?? '') !== '') {
                if (! ctype_digit((string) $data['starting_electric_charge']) || (int) $data['starting_electric_charge'] > 100) {
                    $errors[] = "Row {$line}: starting_electric_charge must be a whole number from 0 to 100.";
                }
            }
            if (($data['ending_electric_charge'] ?? '') !== '') {
                if (! ctype_digit((string) $data['ending_electric_charge']) || (int) $data['ending_electric_charge'] > 100) {
                    $errors[] = "Row {$line}: ending_electric_charge must be a whole number from 0 to 100.";
                }
            }

            if ($date) {
                $key = $date->toDateString();

                if (isset($seen[$key])) {
                    $errors[] = "Row {$line}: only one entry is allowed per trip date; first seen on row {$seen[$key]}.";
                }

                $seen[$key] = $line;
            }

            foreach (['vehicle_verified_at', 'driver_verified_at', 'initial_verification_at', 'final_verification_at'] as $field) {
                if (($data[$field] ?? '') !== '' && ! $this->csvDateTime($data[$field])) {
                    $errors[] = "Row {$line}: {$field} must be a valid date/time in DD-MM-YYYY HH:MM format.";
                }
            }

            foreach (['vehicle_verified_by', 'driver_verified_by', 'initial_verification_by', 'final_verification_by'] as $field) {
                if (($data[$field] ?? '') !== '' && ! in_array($data[$field], $verifierNames, true)) {
                    $errors[] = "Row {$line}: {$field} must be an active supervisor or controller name for this depot.";
                }
            }

            $validatedRows[] = [
                'date' => $date,
                'status' => $status,
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'actual_start_time' => $actualStartTime,
                'actual_reach_time' => $actualReachTime,
                'trip_order_sequence_no' => ($data['trip_order_sequence_no'] ?? '') !== '' ? (int) $data['trip_order_sequence_no'] : null,
                'starting_km' => ($data['starting_km'] ?? '') !== '' ? (int) $data['starting_km'] : null,
                'ending_km' => ($data['ending_km'] ?? '') !== '' ? (int) $data['ending_km'] : null,
                'starting_electric_charge' => ($data['starting_electric_charge'] ?? '') !== '' ? (int) $data['starting_electric_charge'] : null,
                'ending_electric_charge' => ($data['ending_electric_charge'] ?? '') !== '' ? (int) $data['ending_electric_charge'] : null,
                'vehicle_condition' => ($data['vehicle_condition'] ?? '') ?: null,
                'is_vehicle_verified' => $this->csvBoolean($data['is_vehicle_verified'] ?? null),
                'vehicle_verified_by' => ($data['vehicle_verified_by'] ?? '') ?: null,
                'vehicle_verified_at' => $this->csvDateTime($data['vehicle_verified_at'] ?? null),
                'is_driver_verified' => $this->csvBoolean($data['is_driver_verified'] ?? null),
                'driver_verified_by' => ($data['driver_verified_by'] ?? '') ?: null,
                'driver_verified_at' => $this->csvDateTime($data['driver_verified_at'] ?? null),
                'is_initial_verified' => $this->csvBoolean($data['is_initial_verified'] ?? null),
                'initial_verification_by' => ($data['initial_verification_by'] ?? '') ?: null,
                'initial_verification_at' => $this->csvDateTime($data['initial_verification_at'] ?? null),
                'is_final_verified' => $this->csvBoolean($data['is_final_verified'] ?? null),
                'final_verification_by' => ($data['final_verification_by'] ?? '') ?: null,
                'final_verification_at' => $this->csvDateTime($data['final_verification_at'] ?? null),
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

    private function sheetForDate(Trip $trip, string $date, ?string $status = null): TripSheet
    {
        $sheet = $trip->sheets()->firstOrCreate(
            ['date' => $date],
            [
                'code' => $this->sheetCode($trip, $date),
                'status' => $status ?? 'pending',
            ]
        );

        if ($status !== null && ! $sheet->wasRecentlyCreated) {
            $sheet->update(['status' => $status]);
        }

        if (! $sheet->code) {
            $sheet->update(['code' => $this->sheetCode($trip, $date)]);
        }

        return $sheet;
    }

    private function sheetCode(Trip $trip, string $date): string
    {
        return ($trip->code ?: 'TRIP-'.$trip->id).'-'.str_replace('-', '', $date);
    }

    private function tripSheetEntryCode(Trip $trip, int $number): string
    {
        return ($trip->code ?: 'TRIP-'.$trip->id).'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    private function lastTripSheetEntryNumber(Trip $trip): int
    {
        $prefix = ($trip->code ?: 'TRIP-'.$trip->id).'-';
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';

        return TripSheetEntry::query()
            ->whereHas('sheet', fn ($query) => $query->where('trip_id', $trip->id))
            ->pluck('code')
            ->reduce(function (int $highest, ?string $code) use ($pattern): int {
                return $code && preg_match($pattern, $code, $matches)
                    ? max($highest, (int) $matches[1])
                    : $highest;
            }, 0);
    }

    private function entryPayload(Trip $trip, array $data): array
    {
        return [
            'side' => null,
            'status' => $data['status'] ?? 'pending',
            'departure_time' => $data['departure_time'] ?? null,
            'arrival_time' => $data['arrival_time'] ?? null,
            'actual_start_time' => $this->normalizeOptionalTime($data['actual_start_time'] ?? null),
            'actual_reach_time' => $this->normalizeOptionalTime($data['actual_reach_time'] ?? null),
            'driver_profile_id' => ($data['driver_profile_id'] ?? null) ?: $this->assignmentForDate($trip, $data['date'] ?? null)?->driver_profile_id,
            'vehicle_id' => ($data['vehicle_id'] ?? null) ?: $this->assignmentForDate($trip, $data['date'] ?? null)?->vehicle_id,
            'trip_order_sequence_no' => $data['trip_order_sequence_no'] ?? null,
            'service_code' => $data['service_code'] ?? null,
            'round_no' => $data['round_no'] ?? null,
            'trip_nature' => $trip->tripNature?->title,
            'schedule_km' => $this->tripSheetEntryScheduleKm($trip),
            'starting_km' => $data['starting_km'] ?? null,
            'ending_km' => $data['ending_km'] ?? null,
            'starting_electric_charge' => $data['starting_electric_charge'] ?? null,
            'ending_electric_charge' => $data['ending_electric_charge'] ?? null,
            'vehicle_condition' => $data['vehicle_condition'] ?? null,
            'energy_status' => (bool) ($data['energy_status'] ?? false),
            'accident_status' => (bool) ($data['accident_status'] ?? false),
            'accident_remarks' => $data['accident_remarks'] ?? null,
            'vehicle_breakdown' => (bool) ($data['vehicle_breakdown'] ?? false),
            'medical_emergency' => (bool) ($data['medical_emergency'] ?? false),
            'passenger_issue' => (bool) ($data['passenger_issue'] ?? false),
            'security_threat' => (bool) ($data['security_threat'] ?? false),
            'is_vehicle_verified' => (bool) ($data['is_vehicle_verified'] ?? false),
            'vehicle_verified_by' => $data['vehicle_verified_by'] ?? null,
            'vehicle_verified_at' => $this->normalizeDateTime($data['vehicle_verified_at'] ?? null),
            'is_driver_verified' => (bool) ($data['is_driver_verified'] ?? false),
            'driver_verified_by' => $data['driver_verified_by'] ?? null,
            'driver_verified_at' => $this->normalizeDateTime($data['driver_verified_at'] ?? null),
            'is_initial_verified' => (bool) ($data['is_initial_verified'] ?? false),
            'initial_verification_by' => $data['initial_verification_by'] ?? null,
            'initial_verification_at' => $this->normalizeDateTime($data['initial_verification_at'] ?? null),
            'is_final_verified' => (bool) ($data['is_final_verified'] ?? false),
            'final_verification_by' => $data['final_verification_by'] ?? null,
            'final_verification_at' => $this->normalizeDateTime($data['final_verification_at'] ?? null),
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function tripSheetEntryScheduleKm(Trip $trip): float
    {
        return round((float) $trip->schedule_km / max(1, (int) $trip->rounds_per_trip), 2);
    }

    private function normalizeDateTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function normalizeOptionalTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, 5);
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
        $this->pdfText($content, 'Generated on '.now()->format('d-m-Y'), 420, 795, 10);

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
            'Initial Verification' => $entry->is_initial_verified ? 'Yes' : 'No',
            'Initial Verification By' => $entry->initial_verification_by ?: '-',
            'Final Verification' => $entry->is_final_verified ? 'Yes' : 'No',
            'Final Verification By' => $entry->final_verification_by ?: '-',
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
            $pageObjectNumbers[] = $pageObject.' 0 R';
            $objects[$pageObject] = $pageObject." 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ".$fontObject.' 0 R /F2 '.$boldFontObject.' 0 R >> >> /Contents '.$contentObject." 0 R >>\nendobj\n";
            $objects[$contentObject] = $contentObject." 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."endstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [".implode(' ', $pageObjectNumbers).'] /Count '.count($pageObjectNumbers)." >>\nendobj\n";
        $objects[$fontObject] = $fontObject." 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[$boldFontObject] = $boldFontObject." 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 155): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label.':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 150, $lineY, 9);
            $lineY -= 17;
        }
    }

    private function pdfCard(string &$content, int $x, int $y, int $width, int $height): void
    {
        $this->pdfFill($content, 1, 1, 1, $x, $y, $width, $height);
        $content .= "0.84 0.86 0.90 RG\n";
        $content .= $x.' '.$y.' '.$width.' '.$height." re S\n";
    }

    private function pdfFill(string &$content, float $r, float $g, float $b, int $x, int $y, int $width, int $height): void
    {
        $content .= sprintf("%.2f %.2f %.2f rg\n%d %d %d %d re f\n", $r, $g, $b, $x, $y, $width, $height);
    }

    private function pdfText(string &$content, string $text, int $x, int $y, int $size = 10, string $font = 'F1'): void
    {
        $content .= "0.08 0.10 0.14 rg\n";
        $content .= "BT\n/".$font.' '.$size." Tf\n".$x.' '.$y." Td\n(".$this->escapePdfText(substr($text, 0, 80)).") Tj\nET\n";
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
            default => '<span class="status-orange">'.e($status ?: 'Inactive').'</span>',
        };
    }

    private function sheetStatusBadge(?string $status): string
    {
        $status = Str::of($status ?? 'pending')
            ->trim()
            ->lower()
            ->replace(' ', '_')
            ->toString();

        $label = TripSheet::STATUSES[$status] ?? Str::headline($status);

        $class = match ($status) {
            'pending' => 'bg-warning',
            'initial_verification_completed' => 'bg-info',
            'verification_completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }

    private function yesNoBadge(bool $value): string
    {
        $class = $value ? 'bg-success' : 'bg-secondary';
        $label = $value ? 'Yes' : 'No';

        return '<span class="badge '.$class.'">'.$label.'</span>';
    }
}
