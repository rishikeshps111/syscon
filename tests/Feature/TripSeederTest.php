<?php

use App\Models\Trip;
use Database\Seeders\DepotSeeder;
use Database\Seeders\DistrictSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PrefixSeeder;
use Database\Seeders\RouteSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\TripSeeder;

test('Trip seeder creates Trip records', function () {
    $this->seed(PrefixSeeder::class);
    $this->seed(StateSeeder::class);
    $this->seed(DistrictSeeder::class);
    $this->seed(LocationSeeder::class);
    $this->seed(DepotSeeder::class);
    $this->seed(ServiceTypeSeeder::class);
    $this->seed(RouteSeeder::class);
    $this->seed(TripSeeder::class);

    expect(Trip::count())->toBeGreaterThan(0);
});
