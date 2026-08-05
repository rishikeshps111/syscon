<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Depot;
use App\Models\DriverProfile;
use App\Models\Oem;
use App\Models\Trip;
use App\Models\TripSheetEntry;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\PermissionRedirect;

class DashboardController extends Controller
{
    public function index()
    {
        // $route = PermissionRedirect::routeNameFor(auth()->user());

        // if ($route !== 'dashboard') {
        //     return redirect()->route($route);
        // }

        $cards = collect($this->cards())
            ->filter(fn (array $card) => auth()->user()->can($card['permission']))
            ->values();

        if ($cards->isEmpty()) {
            return view('dashboard', compact('cards'));
        }

        return view('dashboard', compact('cards'));
    }

    private function cards(): array
    {
        $today = today();

        return [
            [
                'permission' => 'oems.view',
                'icon' => 'fa-solid fa-handshake',
                'class' => 'card-green',
                'label' => 'Total OEM',
                'value' => Oem::count(),
                'route' => 'oems.index',
            ],
            [
                'permission' => 'depots.view',
                'icon' => 'fa-solid fa-warehouse',
                'class' => 'card-purple',
                'label' => 'Total Depots',
                'value' => Depot::count(),
                'route' => 'depots.index',
            ],
            [
                'permission' => 'vehicles.view',
                'icon' => 'fa-solid fa-bus',
                'class' => 'card-pink',
                'label' => 'Active Vehicles',
                'value' => Vehicle::where('status', 'Active')->count(),
                'route' => 'vehicles.index',
            ],
            [
                'permission' => 'driver-management.view',
                'icon' => 'fa-solid fa-id-badge',
                'class' => 'card-purple',
                'label' => 'Active Drivers',
                'value' => User::role('Driver')->where('is_active', true)->count(),
                'route' => 'driver-management.index',
            ],
            [
                'permission' => 'controller-management.view',
                'icon' => 'fa-solid fa-id-badge',
                'class' => 'card-orange',
                'label' => 'Total Controllers',
                'value' => User::role('Controller')->count(),
                'route' => 'controller-management.index',
            ],
            [
                'permission' => 'supervisor-management.view',
                'icon' => 'fa-solid fa-id-badge',
                'class' => 'card-teal',
                'label' => 'Total Supervisor',
                'value' => User::role('Supervisor')->count(),
                'route' => 'supervisor-management.index',
            ],
            [
                'permission' => 'trips.view',
                'icon' => 'fa-solid fa-route',
                'class' => 'card-orange',
                'label' => 'Trips Today',
                'value' => Trip::where('status', 'Active')
                    ->whereDate('from_date', '<=', $today)
                    ->where(function ($query) use ($today) {
                        $query->whereNull('to_date')
                            ->orWhereDate('to_date', '>=', $today);
                    })
                    ->count(),
                'route' => 'trips.index',
            ],
            [
                'permission' => 'trips.view',
                'icon' => 'fa-solid fa-circle-check',
                'class' => 'card-green',
                'label' => 'Completed Trip Sheet',
                'value' => TripSheetEntry::whereNotNull('actual_reach_time')->count(),
                'route' => 'trips.completed.index',
            ],
            [
                'permission' => 'trips.view',
                'icon' => 'fa-solid fa-clock',
                'class' => 'card-red',
                'label' => 'Delayed Trips',
                'value' => TripSheetEntry::whereNotNull('actual_reach_time')
                    ->whereColumn('actual_reach_time', '>', 'arrival_time')
                    ->count(),
                'route' => 'trips.completed.index',
            ],
            [
                'permission' => 'trips.view',
                'icon' => 'fa-solid fa-ban',
                'class' => 'card-dark',
                'label' => 'Cancelled Trips',
                'value' => Trip::where('status', 'Cancelled')->count(),
                'route' => 'trips.index',
            ],
            [
                'permission' => 'complaints.view',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'class' => 'card-red',
                'label' => 'Complaints Open',
                'value' => Complaint::whereNotIn('status', ['closed', 'rejected'])->count(),
                'route' => 'complaints.index',
            ],
            [
                'permission' => 'driver-management.view',
                'icon' => 'fa-solid fa-id-card',
                'class' => 'card-orange',
                'label' => 'Expired Driver Licenses',
                'value' => DriverProfile::expiredLicenseCount(),
                'route' => 'driver-management.index',
                'route_params' => ['expiry_filter' => 'license_expired'],
            ],
        ];
    }
}
