<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleMaintenanceLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('vehicles.view'), ['index']),
            new Middleware(PermissionMiddleware::using('vehicles.edit'), ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request, Vehicle $vehicle)
    {
        $vehicle->load(['state', 'oem', 'depot', 'branch']);
        $types = VehicleMaintenanceLog::TYPES;
        $statuses = VehicleMaintenanceLog::STATUSES;

        if ($request->ajax()) {
            $query = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('service_date_display', fn ($row) => $row->service_date?->format('d-m-Y') ?? '-')
                ->addColumn('next_service_due_display', fn ($row) => $row->next_service_due?->format('d-m-Y') ?? '-')
                ->addColumn('cost_display', fn ($row) => $row->cost !== null ? number_format((float) $row->cost, 2) : '-')
                ->addColumn('status_badge', fn ($row) => $row->status === 'Closed'
                    ? '<span class="status-green">Closed</span>'
                    : '<span class="status-orange">Open</span>')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('vehicles.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $editButton = '<button type="button" class="btn-edit edit-maintenance" title="Edit" data-bs-toggle="modal" data-bs-target="#maintenanceModal"'
                        . ' data-url="' . e(route('vehicle-maintenance-logs.update', $row->id)) . '"'
                        . ' data-maintenance-type="' . e($row->maintenance_type) . '"'
                        . ' data-description="' . e($row->description) . '"'
                        . ' data-cost="' . e($row->cost) . '"'
                        . ' data-vendor-name="' . e($row->vendor_name) . '"'
                        . ' data-service-date="' . e($row->service_date?->format('Y-m-d')) . '"'
                        . ' data-next-service-due="' . e($row->next_service_due?->format('Y-m-d')) . '"'
                        . ' data-status="' . e($row->status) . '">'
                        . '<i class="fa-solid fa-pen-to-square"></i></button>';
                    $deleteButton = '<button type="button" class="btn-delete" onclick="deleteMaintenance(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';

                    return '<div class="action-btns justify-content-center">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('vehicle.maintenance-logs.index', compact('vehicle', 'types', 'statuses'));
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        $log = $vehicle->maintenanceLogs()->create($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Maintenance log added successfully.',
            'data' => $log,
        ], 201);
    }

    public function update(Request $request, VehicleMaintenanceLog $vehicleMaintenanceLog)
    {
        $vehicleMaintenanceLog->update($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Maintenance log updated successfully.',
            'data' => $vehicleMaintenanceLog->fresh(),
        ]);
    }

    public function destroy(VehicleMaintenanceLog $vehicleMaintenanceLog)
    {
        $vehicleMaintenanceLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance log deleted successfully.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'maintenance_type' => ['required', Rule::in(array_keys(VehicleMaintenanceLog::TYPES))],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'next_service_due' => ['nullable', 'date', 'after_or_equal:service_date'],
            'status' => ['required', Rule::in(array_keys(VehicleMaintenanceLog::STATUSES))],
        ]);
    }
}
