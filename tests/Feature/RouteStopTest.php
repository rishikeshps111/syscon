<?php

use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\Route as RouteModel;
use App\Models\RouteStop;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsRouteStopManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['routes.edit', 'routes.delete', 'routes.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Route']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['routes.edit', 'routes.delete', 'routes.view']);

    test()->actingAs($user);

    return $user;
}

function createRouteForStops(): RouteModel
{
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

    return RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);
}

test('route stop can be created for a route', function () {
    actingAsRouteStopManager();
    $route = createRouteForStops();

    $response = $this->postJson('/routes/' . $route->id . '/stops', [
        'name' => 'Palarivattom',
        'expected_reach_time' => '09:30',
        'position' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $stop = RouteStop::first();

    expect($stop->route_id)->toBe($route->id)
        ->and($stop->name)->toBe('Palarivattom')
        ->and($stop->position)->toBe(1);
});

test('route stop position must be unique inside route', function () {
    actingAsRouteStopManager();
    $route = createRouteForStops();

    RouteStop::create([
        'route_id' => $route->id,
        'name' => 'Palarivattom',
        'expected_reach_time' => '09:30',
        'position' => 1,
    ]);

    $response = $this->postJson('/routes/' . $route->id . '/stops', [
        'name' => 'Edappally',
        'expected_reach_time' => '09:45',
        'position' => 1,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('position');
});

test('route stops are listed by position', function () {
    actingAsRouteStopManager();
    $route = createRouteForStops();

    RouteStop::create([
        'route_id' => $route->id,
        'name' => 'Edappally',
        'expected_reach_time' => '09:45',
        'position' => 2,
    ]);

    RouteStop::create([
        'route_id' => $route->id,
        'name' => 'Palarivattom',
        'expected_reach_time' => '09:30',
        'position' => 1,
    ]);

    $response = $this->getJson('/routes/' . $route->id . '/stops', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('data.0.name'))->toBe('Palarivattom')
        ->and($response->json('data.1.name'))->toBe('Edappally');
});

test('route stop can be updated', function () {
    actingAsRouteStopManager();
    $route = createRouteForStops();

    $stop = RouteStop::create([
        'route_id' => $route->id,
        'name' => 'Palarivattom',
        'expected_reach_time' => '09:30',
        'position' => 1,
    ]);

    $response = $this->putJson('/route-stops/' . $stop->id, [
        'name' => 'Edappally',
        'expected_reach_time' => '09:45',
        'position' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($stop->refresh()->name)->toBe('Edappally')
        ->and($stop->position)->toBe(2);
});

test('route stop can be deleted', function () {
    actingAsRouteStopManager();
    $route = createRouteForStops();

    $stop = RouteStop::create([
        'route_id' => $route->id,
        'name' => 'Palarivattom',
        'position' => 1,
    ]);

    $response = $this->deleteJson('/route-stops/' . $stop->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($stop->fresh())->toBeNull();
});
