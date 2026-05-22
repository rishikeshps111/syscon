<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleFuelLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleFuelLogController extends Controller implements HasMiddleware
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
        $fuelTypes = Vehicle::FUEL_TYPES;

        if ($request->ajax()) {
            $query = VehicleFuelLog::where('vehicle_id', $vehicle->id)->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('fuel_type_display', fn ($row) => Vehicle::FUEL_TYPES[$row->fuel_type] ?? $row->fuel_type)
                ->addColumn('quantity_display', fn ($row) => number_format((float) $row->quantity, 2))
                ->addColumn('cost_display', fn ($row) => $row->cost !== null ? number_format((float) $row->cost, 2) : '-')
                ->addColumn('odometer_display', fn ($row) => $row->odometer_reading !== null ? $row->odometer_reading : '-')
                ->addColumn('date_display', fn ($row) => $row->date?->format('d-m-Y') ?? '-')
                ->addColumn('action', function ($row) {
                    if (! auth()->user()->can('vehicles.edit')) {
                        return '<span class="text-muted">No Access</span>';
                    }

                    $editButton = '<button type="button" class="btn-edit edit-fuel-log" title="Edit" data-bs-toggle="modal" data-bs-target="#fuelLogModal"'
                        . ' data-url="' . e(route('vehicle-fuel-logs.update', $row->id)) . '"'
                        . ' data-fuel-type="' . e($row->fuel_type) . '"'
                        . ' data-quantity="' . e($row->quantity) . '"'
                        . ' data-cost="' . e($row->cost) . '"'
                        . ' data-odometer-reading="' . e($row->odometer_reading) . '"'
                        . ' data-date="' . e($row->date?->format('Y-m-d')) . '">'
                        . '<i class="fa-solid fa-pen-to-square"></i></button>';
                    $deleteButton = '<button type="button" class="btn-delete" onclick="deleteFuelLog(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';

                    return '<div class="action-btns justify-content-center">' . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('vehicle.fuel-logs.index', compact('vehicle', 'fuelTypes'));
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        $log = $vehicle->fuelLogs()->create($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Fuel / energy log added successfully.',
            'data' => $log,
        ], 201);
    }

    public function update(Request $request, VehicleFuelLog $vehicleFuelLog)
    {
        $vehicleFuelLog->update($this->validatedData($request));

        return response()->json([
            'success' => true,
            'message' => 'Fuel / energy log updated successfully.',
            'data' => $vehicleFuelLog->fresh(),
        ]);
    }

    public function destroy(VehicleFuelLog $vehicleFuelLog)
    {
        $vehicleFuelLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fuel / energy log deleted successfully.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'fuel_type' => ['required', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
            'quantity' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_reading' => ['nullable', 'integer', 'min:0'],
            'date' => ['required', 'date'],
        ]);
    }
}
