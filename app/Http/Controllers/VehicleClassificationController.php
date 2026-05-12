<?php

namespace App\Http\Controllers;

use App\Exports\VehicleClassificationExport;
use App\Http\Requests\StoreVehicleClassificationRequest;
use App\Http\Requests\UpdateVehicleClassificationRequest;
use App\Models\VehicleClassification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleClassificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('vehicle-classifications.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('vehicle-classifications.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('vehicle-classifications.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('vehicle-classifications.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = VehicleClassification::select([
                'id',
                'code',
                'name',
                'capacity',
                'fuel_type',
                'is_active',
                'created_at',
            ])->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('capacity', function ($row) {
                    return $row->capacity ?? '-';
                })
                ->addColumn('fuel', function ($row) {
                    return $this->formatFuelType($row->fuel_type);
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('vehicle-classification.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('vehicle-classification.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = VehicleClassification::findOrFail($request->id);

            return response()->json([
                'html' => view('vehicle-classification.form', compact('record'))->render(),
                'title' => 'Update Vehicle Classification',
            ]);
        }

        $generatedCode = generate_code('Vehicle Classification Module', ((int) VehicleClassification::max('id')) + 1, 3, 'VC');

        return response()->json([
            'html' => view('vehicle-classification.form', compact('generatedCode'))->render(),
            'title' => 'Add Vehicle Classification',
        ]);
    }

    public function store(StoreVehicleClassificationRequest $request)
    {
        $vehicleClassification = VehicleClassification::create($request->validated());
        $vehicleClassification->code = generate_code('Vehicle Classification Module', $vehicleClassification->id, 3, 'VC');
        $vehicleClassification->save();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle classification created successfully.',
            'data' => $vehicleClassification,
        ], 201);
    }

    public function show(VehicleClassification $vehicleClassification) {}

    public function edit(VehicleClassification $vehicleClassification) {}

    public function update(UpdateVehicleClassificationRequest $request, VehicleClassification $vehicleClassification)
    {
        $vehicleClassification->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vehicle classification updated successfully.',
            'data' => $vehicleClassification->fresh(),
        ]);
    }

    public function destroy(VehicleClassification $vehicleClassification)
    {
        $vehicleClassification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle classification deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = VehicleClassification::select('code', 'name', 'capacity', 'fuel_type', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new VehicleClassificationExport($query), 'vehicle-classifications.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:vehicle_classifications,id'],
            'status' => ['required', 'boolean'],
        ]);

        $vehicleClassification = VehicleClassification::findOrFail($request->id);
        $vehicleClassification->is_active = $request->status;
        $vehicleClassification->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function formatFuelType(?string $fuelType): string
    {
        return match ($fuelType) {
            'petrol' => 'Petrol',
            'diesel' => 'Diesel',
            'ev' => 'EV',
            'hybrid' => 'Hybrid',
            default => '-',
        };
    }
}
