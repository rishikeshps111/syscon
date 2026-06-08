<?php

namespace App\Http\Controllers;

use App\Exports\RosterExport;
use App\Http\Requests\StoreRosterRequest;
use App\Http\Requests\UpdateRosterRequest;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Oem;
use App\Models\Roster;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\TripAssignment;
use App\Models\TripSheetEntry;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class RosterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('rosters.view'), ['index', 'show', 'export', 'downloadPdf', 'tripEntries', 'tripEntryDetails']),
            new Middleware(PermissionMiddleware::using('rosters.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('rosters.edit'), ['edit', 'update', 'status', 'reassignDriver', 'reassignVehicle', 'attendance']),
            new Middleware(PermissionMiddleware::using('rosters.delete'), ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('date', fn ($row) => $row->duty_date?->format('d M Y') ?: '-')
                ->addColumn('shift_type_label', fn ($row) => Roster::SHIFT_TYPES[$row->shift_type] ?? '-')
                ->addColumn('driver_name', fn ($row) => $row->driverProfile?->user?->name ?: '-')
                ->addColumn('vehicle_no', fn ($row) => $row->vehicle?->vehicle_no ?: '-')
                ->addColumn('trip_code', fn ($row) => $row->tripSheetEntry?->sheet?->code ?: '-')
                ->addColumn('reporting_time_label', fn ($row) => $this->time($row->reporting_time) ?: '-')
                ->addColumn('status', fn ($row) => $this->statusBadge($row->status))
                ->addColumn('attendance_status', fn ($row) => $this->attendanceBadge($row->attendance_status))
                ->addColumn('action', fn ($row) => view('roster.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status', 'attendance_status', 'action'])
                ->make(true);
        }

        return view('roster.index', $this->lookupData() + [
            'statuses' => Roster::STATUSES,
            'attendanceStatuses' => Roster::ATTENDANCE_STATUSES,
            'shiftTypes' => Roster::SHIFT_TYPES,
        ]);
    }

    public function create()
    {
        return view('roster.create', $this->formData([
            'generatedCode' => $this->generateRosterCode(((int) Roster::max('id')) + 1),
        ]));
    }

    public function store(StoreRosterRequest $request)
    {
        $validated = $request->validated();
        $entryIds = $this->selectedTripEntryIds($validated);

        DB::transaction(function () use ($validated, $entryIds) {
            foreach ($entryIds as $entryId) {
                $data = $this->payload($validated + ['trip_sheet_entry_id' => $entryId]);
                $roster = Roster::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
                $roster->update(['code' => $this->generateRosterCode($roster->id)]);
                $this->syncTripSheetEntry($roster);
            }
        });

        return redirect()->route('rosters.index')->with('success', 'Roaster saved successfully.');
    }

    public function show(Roster $roster)
    {
        return view('roster.show', [
            'record' => $roster->load($this->relations()),
        ]);
    }

    public function downloadPdf(Roster $roster)
    {
        $record = $roster->load($this->relations());
        $pdf = $this->buildRosterPdf($record);
        $fileName = ($record->code ?: 'roaster-details') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function edit(Roster $roster)
    {
        $roster->load($this->relations());

        return view('roster.edit', $this->formData([
            'record' => $roster,
            'selectedTrips' => $this->selectedTripsForRoster($roster),
        ]));
    }

    public function update(UpdateRosterRequest $request, Roster $roster)
    {
        DB::transaction(function () use ($request, $roster) {
            $validated = $request->validated();
            $entryId = $this->selectedTripEntryIds($validated)[0] ?? $validated['trip_sheet_entry_id'];
            $roster->update($this->payload($validated + ['trip_sheet_entry_id' => $entryId], $roster) + ['updated_by' => auth()->id()]);
            $this->syncTripSheetEntry($roster);
        });

        return redirect()->route('rosters.index')->with('success', 'Roaster updated successfully.');
    }

    public function destroy(Roster $roster)
    {
        $roster->delete();

        return response()->json(['success' => true, 'message' => 'Roaster deleted successfully.']);
    }

    public function export(Request $request)
    {
        $query = $this->filteredQuery();

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        return Excel::download(new RosterExport($query), 'roasters.xlsx');
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:rosters,id'],
            'status' => ['required', Rule::in(array_keys(Roster::STATUSES))],
        ]);

        Roster::whereKey($validated['id'])->update([
            'status' => $validated['status'],
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Roaster status updated successfully.']);
    }

    public function attendance(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:rosters,id'],
            'attendance_status' => ['required', Rule::in(array_keys(Roster::ATTENDANCE_STATUSES))],
        ]);

        Roster::whereKey($validated['id'])->update([
            'attendance_status' => $validated['attendance_status'],
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Attendance marked successfully.']);
    }

    public function reassignDriver(Request $request, Roster $roster)
    {
        $validated = $request->validate([
            'driver_profile_id' => ['required', 'integer', 'exists:driver_profiles,id'],
        ]);

        DB::transaction(function () use ($roster, $validated) {
            $this->ensureDriverCanBeAssigned((int) $validated['driver_profile_id'], $roster);
            $roster->update($validated + ['updated_by' => auth()->id()]);
            $this->syncTripSheetEntry($roster);
        });

        return response()->json(['success' => true, 'message' => 'Driver reassigned successfully.']);
    }

    public function reassignVehicle(Request $request, Roster $roster)
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        DB::transaction(function () use ($roster, $validated) {
            $this->ensureVehicleCanBeAssigned((int) $validated['vehicle_id'], $roster);
            $roster->update($validated + ['updated_by' => auth()->id()]);
            $this->syncTripSheetEntry($roster);
        });

        return response()->json(['success' => true, 'message' => 'Vehicle reassigned successfully.']);
    }

    public function tripEntries(Request $request)
    {
        $date = $request->input('duty_date');
        $search = trim((string) $request->input('q'));
        $selectedIds = array_filter(array_map('intval', (array) $request->input('selected_ids', [])));

        $entries = TripSheetEntry::query()
            ->with(['driverProfile.user', 'vehicle', 'sheet.trip.assignments.driverProfile.user', 'sheet.trip.assignments.vehicle'])
            ->whereHas('sheet', function ($query) use ($date, $search) {
                if ($date) {
                    $query->whereDate('date', $date);
                }

                if ($search !== '') {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('code', 'like', '%' . $search . '%')
                            ->orWhereHas('trip', fn ($tripQuery) => $tripQuery
                                ->where('code', 'like', '%' . $search . '%')
                                ->orWhere('title', 'like', '%' . $search . '%'));
                    });
                }
            })
            ->when($selectedIds, fn ($query) => $query->orWhereIn('id', $selectedIds))
            ->limit(30 + count($selectedIds))
            ->get();

        return response()->json($entries->map(fn (TripSheetEntry $entry) => $this->entryPayload($entry, $date))->values());
    }

    public function tripEntryDetails(Request $request, TripSheetEntry $tripSheetEntry)
    {
        $tripSheetEntry->load(['driverProfile.user', 'vehicle', 'sheet.trip.assignments.driverProfile.user', 'sheet.trip.assignments.vehicle']);

        return response()->json($this->entryPayload($tripSheetEntry, $request->input('duty_date')));
    }

    private function filteredQuery()
    {
        $query = Roster::with($this->relations())->select('rosters.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('code', 'like', '%' . $search . '%')
                    ->orWhereHas('driverProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('vehicle_no', 'like', '%' . $search . '%'))
                    ->orWhereHas('tripSheetEntry.sheet', fn ($sheetQuery) => $sheetQuery->where('code', 'like', '%' . $search . '%'))
                    ->orWhereHas('tripSheetEntry.sheet.trip', fn ($tripQuery) => $tripQuery->where('title', 'like', '%' . $search . '%'));
            });
        }

        foreach (['state_id', 'oem_id', 'depot_id', 'shift_type', 'status', 'driver_profile_id'] as $field) {
            if (request()->filled($field)) {
                $query->where($field, request($field));
            }
        }

        if (request()->filled('date_from')) {
            $query->whereDate('duty_date', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('duty_date', '<=', request('date_to'));
        }

        return $query->latest('id');
    }

    private function payload(array $data, ?Roster $currentRoster = null): array
    {
        $entry = TripSheetEntry::with('sheet.trip.assignments')->findOrFail($data['trip_sheet_entry_id']);
        $assignment = $this->assignmentForEntry($entry, $data['duty_date']);

        $data['trip_assignment_id'] = $assignment?->id;
        $data['driver_profile_id'] = ($data['driver_profile_id'] ?? null) ?: $assignment?->driver_profile_id;
        $data['vehicle_id'] = ($data['vehicle_id'] ?? null) ?: $assignment?->vehicle_id;
        unset($data['trip_sheet_entry_ids']);

        if ($data['driver_profile_id'] ?? null) {
            $this->ensureDriverCanBeAssigned((int) $data['driver_profile_id'], $currentRoster);
        }

        if ($data['vehicle_id'] ?? null) {
            $this->ensureVehicleCanBeAssigned((int) $data['vehicle_id'], $currentRoster);
        }

        return $data;
    }

    private function selectedTripEntryIds(array $data): array
    {
        $ids = $data['trip_sheet_entry_ids'] ?? [$data['trip_sheet_entry_id'] ?? null];

        return array_values(array_unique(array_filter(array_map('intval', (array) $ids))));
    }

    private function entryPayload(TripSheetEntry $entry, ?string $date): array
    {
        $assignment = $this->assignmentForEntry($entry, $date ?: $entry->sheet?->date?->format('Y-m-d'));

        return [
            'id' => $entry->id,
            'sheet_code' => $entry->sheet?->code,
            'trip_code' => $entry->sheet?->trip?->code,
            'trip_title' => $entry->sheet?->trip?->trip_title,
            'date' => $entry->sheet?->date?->format('Y-m-d'),
            'side' => ucfirst((string) $entry->side),
            'driver_profile_id' => $entry->driver_profile_id ?: $assignment?->driver_profile_id,
            'driver_name' => $entry->driverProfile?->user?->name ?: $assignment?->driverProfile?->user?->name,
            'vehicle_id' => $entry->vehicle_id ?: $assignment?->vehicle_id,
            'vehicle_no' => $entry->vehicle?->vehicle_no ?: $assignment?->vehicle?->vehicle_no,
        ];
    }

    private function selectedTripsForRoster(Roster $roster): array
    {
        $entry = $roster->tripSheetEntry;

        if (! $entry && $roster->trip_sheet_entry_id) {
            $entry = TripSheetEntry::with(['driverProfile.user', 'vehicle', 'sheet.trip'])->find($roster->trip_sheet_entry_id);
        }

        if (! $entry) {
            return [];
        }

        return [[
            'id' => $entry->id,
            'label' => trim(($entry->sheet?->code ?: '') . ' - ' . ($entry->sheet?->trip?->trip_title ?: '')),
            'side' => ucfirst((string) $entry->side),
            'driver' => $roster->driver_profile_id ?: $entry->driver_profile_id,
            'vehicle' => $roster->vehicle_id ?: $entry->vehicle_id,
        ]];
    }

    private function assignmentForEntry(TripSheetEntry $entry, ?string $date): ?TripAssignment
    {
        $trip = $entry->sheet?->trip;
        $date = $date ? \Carbon\Carbon::parse($date) : $entry->sheet?->date;

        if (! $trip || ! $date) {
            return null;
        }

        return $trip->assignments
            ->first(fn (TripAssignment $assignment) => $assignment->from_date?->lte($date) && $assignment->to_date?->gte($date));
    }

    private function syncTripSheetEntry(Roster $roster): void
    {
        $roster->loadMissing('tripSheetEntry');

        $entry = $roster->tripSheetEntry;

        if (! $entry) {
            return;
        }

        $this->syncTripSheetEntryColumns($entry, $roster);
    }

    private function syncTripSheetEntryColumns(TripSheetEntry $entry, Roster $roster): void
    {
        $updates = [];

        if (Schema::hasColumn('trip_sheet_entries', 'driver_profile_id')) {
            $updates['driver_profile_id'] = $roster->driver_profile_id;
        }

        if (Schema::hasColumn('trip_sheet_entries', 'vehicle_id')) {
            $updates['vehicle_id'] = $roster->vehicle_id;
        }

        if ($updates) {
            $entry->update($updates);
        }
    }

    private function ensureDriverCanBeAssigned(int $driverProfileId, ?Roster $currentRoster = null): void
    {
        $driver = DriverProfile::findOrFail($driverProfileId);

        if (! $driver->expiry_date || $driver->expiry_date->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'driver_profile_id' => 'Licence expired driver cannot be selected.',
            ]);
        }

        $exists = Roster::where('driver_profile_id', $driverProfileId)
            ->when($currentRoster, fn ($query) => $query->whereKeyNot($currentRoster->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'driver_profile_id' => 'Driver already associated with another roaster.',
            ]);
        }
    }

    private function ensureVehicleCanBeAssigned(int $vehicleId, ?Roster $currentRoster = null): void
    {
        $exists = Roster::where('vehicle_id', $vehicleId)
            ->when($currentRoster, fn ($query) => $query->whereKeyNot($currentRoster->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Vehicle already associated with another roaster.',
            ]);
        }
    }

    private function formData(array $extra = []): array
    {
        return $extra + $this->lookupData() + [
            'shiftTypes' => Roster::SHIFT_TYPES,
            'statuses' => Roster::STATUSES,
            'attendanceStatuses' => Roster::ATTENDANCE_STATUSES,
        ];
    }

    private function lookupData(): array
    {
        $assignedDriverIds = Roster::whereNotNull('driver_profile_id')->pluck('driver_profile_id')->all();
        $assignedVehicleIds = Roster::whereNotNull('vehicle_id')->pluck('vehicle_id')->all();

        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'oems' => Oem::orderBy('oem_name')->get(['id', 'oem_name']),
            'depots' => Depot::orderBy('name')->get(['id', 'name', 'state_id']),
            'drivers' => DriverProfile::with('user')->orderBy('id')->get(),
            'vehicles' => Vehicle::where('status', 'Active')->orderBy('vehicle_no')->get(['id', 'vehicle_code', 'vehicle_no', 'capacity_seating', 'capacity_load', 'chassis_no', 'registration_valid_upto', 'fitness_expiry', 'pollution_expiry', 'insurance_expiry', 'oem_id', 'depot_id', 'state_id']),
            'supervisors' => SupervisorProfile::with('user')->orderBy('id')->get(),
            'controllers' => ControllerProfile::with('user')->orderBy('id')->get(),
            'assignedDriverIds' => $assignedDriverIds,
            'assignedVehicleIds' => $assignedVehicleIds,
        ];
    }

    private function relations(): array
    {
        return [
            'state',
            'oem',
            'depot',
            'tripSheetEntry.sheet.trip',
            'driverProfile.user',
            'vehicle',
            'supervisorProfile.user',
            'controllerProfile.user',
        ];
    }

    private function generateRosterCode(int $id): string
    {
        return generate_code(Roster::PREFIX_MODULE, $id, 4, 'RST');
    }

    private function time(?string $value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }

    private function statusBadge(?string $status): string
    {
        $label = Roster::STATUSES[$status] ?? 'Assigned';
        $class = match ($status) {
            'completed' => 'status-green',
            'missed' => 'status-red',
            'in_progress' => 'status-orange',
            default => 'badge bg-secondary',
        };

        return '<span class="' . $class . '">' . e($label) . '</span>';
    }

    private function attendanceBadge(?string $status): string
    {
        if (! $status) {
            return '<span class="badge bg-secondary">Not Marked</span>';
        }

        $class = match ($status) {
            'present' => 'status-green',
            'absent' => 'status-red',
            default => 'status-orange',
        };

        return '<span class="' . $class . '">' . e(Roster::ATTENDANCE_STATUSES[$status] ?? $status) . '</span>';
    }

    private function buildRosterPdf(Roster $record): string
    {
        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'Roaster Details', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 420, 795, 10);

        $this->pdfSection($content, 'Basic Information', 40, 590, 515, [
            'Roster Code' => $record->code ?: '-',
            'Duty Date' => $record->duty_date?->format('d M Y') ?: '-',
            'State' => $record->state?->name ?: '-',
            'Vendor' => $record->oem?->oem_name ?: '-',
            'Depot' => $record->depot?->name ?: '-',
            'Status' => Roster::STATUSES[$record->status] ?? '-',
        ], 165);

        $this->pdfSection($content, 'Shift And Attendance', 40, 405, 515, [
            'Shift Type' => Roster::SHIFT_TYPES[$record->shift_type] ?? '-',
            'Shift Start Time' => $this->time($record->shift_start_time) ?: '-',
            'Shift End Time' => $this->time($record->shift_end_time) ?: '-',
            'Reporting To Time' => $this->time($record->reporting_time) ?: '-',
            'Second Reporting To Time' => $this->time($record->reporting_to_time) ?: '-',
            'Attendance Status' => $record->attendance_status ? (Roster::ATTENDANCE_STATUSES[$record->attendance_status] ?? $record->attendance_status) : 'Not Marked',
        ], 145);

        $this->pdfSection($content, 'Trip Details', 40, 230, 515, [
            'Trip Sheet Code' => $record->tripSheetEntry?->sheet?->code ?: '-',
            'Trip Code' => $record->tripSheetEntry?->sheet?->trip?->code ?: '-',
            'Trip Title' => $record->tripSheetEntry?->sheet?->trip?->trip_title ?: '-',
            'Side' => ucfirst((string) $record->tripSheetEntry?->side) ?: '-',
        ], 135);

        $this->pdfSection($content, 'Assignment', 40, 70, 515, [
            'Driver' => $record->driverProfile?->user?->name ?: '-',
            'Vehicle' => $record->vehicle?->vehicle_no ?: '-',
            'Supervisor' => $record->supervisorProfile?->user?->name ?: '-',
            'Controller' => $record->controllerProfile?->user?->name ?: '-',
            'Remarks' => $record->remarks ?: '-',
        ], 140);

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
        $content .= "BT\n/" . $font . ' ' . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . $this->escapePdfText(substr($text, 0, 90)) . ") Tj\nET\n";
    }

    private function escapePdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
