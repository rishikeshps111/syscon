<?php

use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\Route as RouteModel;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsRouteManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['routes.create', 'routes.edit', 'routes.delete', 'routes.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Route']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['routes.create', 'routes.edit', 'routes.delete', 'routes.view']);

    test()->actingAs($user);

    return $user;
}

function createRouteFixtures(): array
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

    return [$state, $startDepot, $endDepot];
}

test('route can be created with generated code', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    $response = $this->postJson('/routes', [
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'name' => 'Kakkanad to Aluva',
        'distance' => 28,
        'estimated_duration' => '01:15',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $route = RouteModel::first();

    expect($route->name)->toBe('Kakkanad to Aluva')
        ->and($route->state_id)->toBe($state->id)
        ->and($route->start_point_id)->toBe($startDepot->id)
        ->and($route->end_point_id)->toBe($endDepot->id)
        ->and($route->code)->toBe(generate_code('Route Module', 1, 3, 'RT'));
});

test('route can be updated', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    $route = RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'distance' => 28,
        'estimated_duration' => '01:15',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    $response = $this->putJson('/routes/' . $route->id, [
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'name' => 'Kakkanad to Aluva Express',
        'distance' => 30,
        'estimated_duration' => '01:05',
        'route_type' => 'intercity',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($route->refresh()->name)->toBe('Kakkanad to Aluva Express')
        ->and($route->distance)->toBe(30)
        ->and($route->route_type)->toBe('intercity')
        ->and($route->is_active)->toBeFalse();
});

test('route status can be changed', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    $route = RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    $response = $this->postJson('/routes/status', [
        'id' => $route->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($route->refresh()->is_active)->toBeFalse();
});

test('routes can be filtered by state status start and end point', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $endDepot->id,
        'end_point_id' => $startDepot->id,
        'code' => 'RT' . now()->year . '#002',
        'name' => 'Aluva to Kakkanad',
        'route_type' => 'intercity',
        'is_active' => false,
    ]);

    $response = $this->getJson('/routes?state_id=' . $state->id . '&start_point_id=' . $endDepot->id . '&end_point_id=' . $startDepot->id . '&status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Aluva to Kakkanad')
        ->and($response->json('data.0.start_point'))->toBe('Aluva Depot')
        ->and($response->json('data.0.end_point'))->toBe('Kakkanad Depot')
        ->and($response->json('data.0.state_name'))->toBe('Kerala');
});

test('route can be deleted', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    $route = RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/routes/' . $route->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($route->fresh())->toBeNull();
});

test('route preview can be viewed and exported', function () {
    actingAsRouteManager();
    [$state, $startDepot, $endDepot] = createRouteFixtures();

    $route = RouteModel::create([
        'state_id' => $state->id,
        'start_point_id' => $startDepot->id,
        'end_point_id' => $endDepot->id,
        'code' => 'RT' . now()->year . '#001',
        'name' => 'Kakkanad to Aluva',
        'route_type' => 'Intracity',
        'is_active' => true,
    ]);

    $route->stops()->create([
        'name' => 'Palarivattom',
        'expected_reach_time' => '09:30',
        'position' => 1,
    ]);

    $this->get('/routes/' . $route->id . '/preview')
        ->assertOk()
        ->assertSee('Route Preview')
        ->assertSee('Kakkanad to Aluva')
        ->assertSee('Palarivattom');

    $this->get('/routes/' . $route->id . '/preview/export')
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
