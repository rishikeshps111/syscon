<?php

namespace App\Http\Controllers;

use App\Exports\TripExport;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Oem;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\ShiftSetting;
use App\Models\SupervisorProfile;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class TripController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('trips.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('trips.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('trips.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('trips.sheet'), ['sheet', 'storeSheet', 'sheetView']),
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

    public function create(Request $request)
    {
        $generatedCode = $this->generateTripCode(((int) Trip::max('id')) + 1);

        return view('trip.create', $this->formData(['generatedCode' => $generatedCode]));
    }

    public function store(StoreTripRequest $request)
    {
        $data = $request->validated() + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
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
        $trip->update($request->validated() + ['updated_by' => auth()->id()]);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->route('trips.index')->with('success', 'Trip updated successfully.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Trip updated successfully.',
            'data' => $trip->fresh(),
        ]);
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
        return view('trip.sheet', $this->assignmentData($trip) + [
            'record' => $trip->load(['route.startPoint', 'route.endPoint', 'route.stops', 'sheetEntries']),
            'entries' => $trip->sheetEntries()->orderBy('trip_date')->get(),
            'controllers' => ControllerProfile::with('user')
                ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->get(),
            'supervisors' => SupervisorProfile::with('user')
                ->when($trip->depot_id, fn ($query) => $query->where('depot_id', $trip->depot_id))
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->get(),
            'shifts' => ShiftSetting::where('is_active', true)->orderBy('shift_name')->get(['id', 'shift_name', 'start_time', 'end_time']),
        ]);
    }

    public function sheetView(Request $request, Trip $trip)
    {
        $trip->load(['route.startPoint', 'route.endPoint', 'route.stops', 'depot']);
        $query = $trip->sheetEntries()
            ->with(['driverProfile.user', 'vehicle'])
            ->orderBy('trip_date');

        if ($request->filled('date_from')) {
            $query->whereDate('trip_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trip_date', '<=', $request->date_to);
        }

        if ($request->input('export') === 'csv') {
            $entries = $query->get();
            $fileName = ($trip->code ?: 'trip') . '-sheet.csv';

            return response()->streamDownload(function () use ($entries) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'SL No',
                    'Date',
                    'Departure Time',
                    'Arrival Time',
                    'Actual Start Time',
                    'Actual Reach Time',
                    'Verified By',
                    'Approved By',
                    'Shift',
                    'Driver Name',
                    'Vehicle No',
                    'Notes',
                ]);

                foreach ($entries as $index => $entry) {
                    fputcsv($handle, [
                        $index + 1,
                        $entry->trip_date?->format('d-m-Y'),
                        $this->formatSheetTime($entry->departure_time),
                        $this->formatSheetTime($entry->arrival_time),
                        $this->formatSheetTime($entry->actual_start_time),
                        $this->formatSheetTime($entry->actual_reach_time),
                        $entry->verified_by,
                        $entry->approved_by,
                        $entry->shift,
                        $entry->driverProfile?->user?->name,
                        $entry->vehicle?->vehicle_no,
                        $entry->notes,
                    ]);
                }

                fclose($handle);
            }, $fileName, ['Content-Type' => 'text/csv']);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('trip_date', fn ($entry) => $entry->trip_date?->format('d-m-Y') ?: '-')
                ->editColumn('departure_time', fn ($entry) => $this->formatSheetTime($entry->departure_time) ?: '-')
                ->editColumn('arrival_time', fn ($entry) => $this->formatSheetTime($entry->arrival_time) ?: '-')
                ->editColumn('actual_start_time', fn ($entry) => $this->formatSheetTime($entry->actual_start_time) ?: '-')
                ->editColumn('actual_reach_time', fn ($entry) => $this->formatSheetTime($entry->actual_reach_time) ?: '-')
                ->editColumn('verified_by', fn ($entry) => $entry->verified_by ?: '-')
                ->editColumn('approved_by', fn ($entry) => $entry->approved_by ?: '-')
                ->editColumn('shift', fn ($entry) => $entry->shift ?: '-')
                ->addColumn('driver_name', fn ($entry) => $entry->driverProfile?->user?->name ?? '-')
                ->addColumn('vehicle_no', fn ($entry) => $entry->vehicle?->vehicle_no ?? '-')
                ->editColumn('notes', fn ($entry) => $entry->notes ?: '-')
                ->make(true);
        }

        return view('trip.sheet-view', [
            'record' => $trip,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function storeSheet(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.trip_date' => ['required', 'date'],
            'entries.*.departure_time' => ['nullable', 'date_format:H:i'],
            'entries.*.arrival_time' => ['nullable', 'date_format:H:i'],
            'entries.*.actual_start_time' => ['nullable', 'date_format:H:i'],
            'entries.*.actual_reach_time' => ['nullable', 'date_format:H:i'],
            'entries.*.verified_by' => ['nullable', 'string', 'max:255'],
            'entries.*.approved_by' => ['nullable', 'string', 'max:255'],
            'entries.*.shift' => ['nullable', 'string', 'max:255'],
            'entries.*.driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'entries.*.vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'entries.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($trip, $validated) {
            $trip->sheetEntries()->delete();

            foreach ($validated['entries'] as $entry) {
                $trip->sheetEntries()->create($entry);
            }
        });

        return redirect()->route('trips.index')->with('success', 'Trip sheet saved successfully.');
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

    private function generateTripCode(int $id): string
    {
        return generate_code(Trip::PREFIX_MODULE, $id, 4);
    }

    private function formatSheetTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '';
    }

    private function statusBadge(?string $status): string
    {
        return match ($status) {
            'Active' => '<span class="status-green">Active</span>',
            'Cancelled' => '<span class="status-red">Cancelled</span>',
            default => '<span class="status-orange">' . e($status ?: 'Inactive') . '</span>',
        };
    }
}
