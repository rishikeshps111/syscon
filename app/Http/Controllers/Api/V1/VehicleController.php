<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function detail(Request $request)
    {
        $vehicleCode = trim((string) ($request->input('vehicle_code') ?? $request->input('vehical_code') ?? ''));

        if ($vehicleCode === '') {
            $this->invalidVehicle();
        }

        $vehicle = Vehicle::with(['state', 'oem', 'depot', 'branch'])
            ->where('vehicle_code', $vehicleCode)
            ->first();

        if (! $vehicle) {
            $this->invalidVehicle();
        }

        return new VehicleResource($vehicle);
    }

    private function invalidVehicle(): never
    {
        throw ValidationException::withMessages([
            'vehicle_code' => 'invalid vehical',
        ]);
    }
}
