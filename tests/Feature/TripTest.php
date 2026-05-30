<?php

use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\Trip;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsTripManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['trips.create', 'trips.edit', 'trips.delete', 'trips.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Trip']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['trips.create', 'trips.edit', 'trips.delete', 'trips.view']);

    test()->actingAs($user);

    return $user;
}

function createTripFixtures(): array
{
    $serviceType = ServiceType::create([
        'code' => 'SRT' . now()->year . '#001',
        'name' => 'Express',
        'is_active' => true,
    ]);

    $state = State::create([
        'code' => 'ST' . now()->year . '#001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);

    $district = District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
    ]);

    $startLocation = Location::create([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
    ]);

    $endLocation = Location::create([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#002',
        'name' => 'Aluva',
        'is_active' => true,
        'is_default' => false,
    ]);

    $startDepot = Depot::create([
        'location_id' => $startLocation->id,
        'code' => 'DPM' . now()->year . '#001',
        'name' => 'Kakkanad Depot',
        'is_active' => true,
    ]);

    $endDepot = Depot::create([
        'location_id' => $endLocation->id,
        'code' => 'DPM' . now()->year . '#002',
        'name' => 'Aluva Depot',
        'is_active' => true,
    ]);

    $route = RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    return [$serviceType, $route];
}

test('Trip can be created with generated code', function () {
    actingAsTripManager();
    [$serviceType, $route] = createTripFixtures();

    $response = $this->postJson('/trips', [
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $trip = Trip::first();

    expect($trip->service_type_id)->toBe($serviceType->id)
        ->and($trip->route_id)->toBe($route->id)
        ->and($trip->code)->toBe(generate_code(Trip::PREFIX_MODULE, 1, 4));
});

test('Trip can be updated', function () {
    actingAsTripManager();
    [$serviceType, $route] = createTripFixtures();

    $trip = Trip::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TRP' . now()->year . '#0001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->putJson('/trips/' . $trip->id, [
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'schedule_type' => 'weekly',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($trip->refresh()->schedule_type)->toBe('weekly')
        ->and($trip->is_active)->toBeFalse();
});

test('Trip status can be changed', function () {
    actingAsTripManager();
    [$serviceType, $route] = createTripFixtures();

    $trip = Trip::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TRP' . now()->year . '#0001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->postJson('/trips/status', [
        'id' => $trip->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($trip->refresh()->is_active)->toBeFalse();
});

test('Trips can be filtered by status', function () {
    actingAsTripManager();
    [$serviceType, $route] = createTripFixtures();

    Trip::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TRP' . now()->year . '#0001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    Trip::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TRP' . now()->year . '#0002',
        'schedule_type' => 'weekly',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'is_active' => false,
    ]);

    $response = $this->getJson('/trips?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.schedule_type'))->toBe('weekly')
        ->and($response->json('data.0.service_type_name'))->toBe('Express')
        ->and($response->json('data.0.route_name'))->toBe('Kakkanad to Aluva');
});

test('Trip can be deleted', function () {
    actingAsTripManager();
    [$serviceType, $route] = createTripFixtures();

    $trip = Trip::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TRP' . now()->year . '#0001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/trips/' . $trip->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($trip->fresh())->toBeNull();
});
