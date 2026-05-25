<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Route as TransportRoute;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = LeaveType::query()->where('is_active', true)->get()->keyBy('short_name');
        $supervisor = User::role('Supervisor')->where('is_active', true)->orderBy('id')->first();
        $controller = User::role('Controller')->where('is_active', true)->orderBy('id')->first();
        $driver = User::role('Driver')->where('is_active', true)->orderBy('id')->first();
        $admin = User::role('Super Admin')->orderBy('id')->first();
        $route = TransportRoute::query()->orderBy('id')->first();

        DB::transaction(function () use ($leaveTypes, $supervisor, $controller, $driver, $admin, $route) {
            if ($supervisor && $leaveTypes->has('CL')) {
                Leave::updateOrCreate(
                    [
                        'leave_for' => 'general',
                        'user_id' => $supervisor->id,
                        'from_date' => now()->addDays(2)->toDateString(),
                    ],
                    [
                        'leave_type_id' => $leaveTypes->get('CL')->id,
                        'to_date' => now()->addDays(3)->toDateString(),
                        'number_of_days' => 2,
                        'reason' => 'Personal work',
                        'status' => 'Pending',
                        'created_by' => $admin?->id,
                        'updated_by' => $admin?->id,
                    ]
                );
            }

            if ($controller && $leaveTypes->has('SL')) {
                Leave::updateOrCreate(
                    [
                        'leave_for' => 'general',
                        'user_id' => $controller->id,
                        'from_date' => now()->subDay()->toDateString(),
                    ],
                    [
                        'leave_type_id' => $leaveTypes->get('SL')->id,
                        'to_date' => now()->subDay()->toDateString(),
                        'number_of_days' => 1,
                        'reason' => 'Medical leave',
                        'status' => 'Approved',
                        'created_by' => $admin?->id,
                        'updated_by' => $admin?->id,
                    ]
                );
            }

            if ($driver) {
                Leave::updateOrCreate(
                    [
                        'leave_for' => 'driver',
                        'user_id' => $driver->id,
                        'leave_date' => now()->toDateString(),
                    ],
                    [
                        'driver_leave_type' => 'Planned Leave',
                        'shift' => 'Morning',
                        'assigned_vehicle_route' => $route?->route_name ?: 'Route not assigned',
                        'reason' => 'Scheduled driver leave',
                        'status' => 'Pending',
                        'created_by' => $admin?->id,
                        'updated_by' => $admin?->id,
                    ]
                );
            }
        });
    }
}
