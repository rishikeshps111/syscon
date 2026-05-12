<?php

use App\Models\TripSetup;
use Database\Seeders\DepotSeeder;
use Database\Seeders\DistrictSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PrefixSeeder;
use Database\Seeders\RouteSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\TripSetupSeeder;

test('trip setup seeder creates trip setup records', function () {
    $this->seed(PrefixSeeder::class);
    $this->seed(StateSeeder::class);
    $this->seed(DistrictSeeder::class);
    $this->seed(LocationSeeder::class);
    $this->seed(DepotSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    $this->seed(RouteSeeder::class);
    $this->seed(TripSetupSeeder::class);

    expect(TripSetup::count())->toBeGreaterThan(0);
});
