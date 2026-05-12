<?php

use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\Route as RouteModel;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\TripSetup;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsTripSetupManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['trip-setups.create', 'trip-setups.edit', 'trip-setups.delete', 'trip-setups.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Trip Setup']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['trip-setups.create', 'trip-setups.edit', 'trip-setups.delete', 'trip-setups.view']);

    test()->actingAs($user);

    return $user;
}

function createTripSetupFixtures(): array
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

test('trip setup can be created with generated code', function () {
    actingAsTripSetupManager();
    [$serviceType, $route] = createTripSetupFixtures();

    $response = $this->postJson('/trip-setups', [
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $tripSetup = TripSetup::first();

    expect($tripSetup->service_type_id)->toBe($serviceType->id)
        ->and($tripSetup->route_id)->toBe($route->id)
        ->and($tripSetup->code)->toBe(generate_code('Trip Setup Module', 1, 3, 'TSU'));
});

test('trip setup can be updated', function () {
    actingAsTripSetupManager();
    [$serviceType, $route] = createTripSetupFixtures();

    $tripSetup = TripSetup::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TSU' . now()->year . '#001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->putJson('/trip-setups/' . $tripSetup->id, [
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'schedule_type' => 'weekly',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($tripSetup->refresh()->schedule_type)->toBe('weekly')
        ->and($tripSetup->is_active)->toBeFalse();
});

test('trip setup status can be changed', function () {
    actingAsTripSetupManager();
    [$serviceType, $route] = createTripSetupFixtures();

    $tripSetup = TripSetup::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TSU' . now()->year . '#001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->postJson('/trip-setups/status', [
        'id' => $tripSetup->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($tripSetup->refresh()->is_active)->toBeFalse();
});

test('trip setups can be filtered by status', function () {
    actingAsTripSetupManager();
    [$serviceType, $route] = createTripSetupFixtures();

    TripSetup::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TSU' . now()->year . '#001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    TripSetup::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TSU' . now()->year . '#002',
        'schedule_type' => 'weekly',
        'start_time' => '09:00',
        'end_time' => '11:00',
        'is_active' => false,
    ]);

    $response = $this->getJson('/trip-setups?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.schedule_type'))->toBe('weekly')
        ->and($response->json('data.0.service_type_name'))->toBe('Express')
        ->and($response->json('data.0.route_name'))->toBe('Kakkanad to Aluva');
});

test('trip setup can be deleted', function () {
    actingAsTripSetupManager();
    [$serviceType, $route] = createTripSetupFixtures();

    $tripSetup = TripSetup::create([
        'service_type_id' => $serviceType->id,
        'route_id' => $route->id,
        'code' => 'TSU' . now()->year . '#001',
        'schedule_type' => 'daily',
        'start_time' => '08:00',
        'end_time' => '10:00',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/trip-setups/' . $tripSetup->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($tripSetup->fresh())->toBeNull();
});
