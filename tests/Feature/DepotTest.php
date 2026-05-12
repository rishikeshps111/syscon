<?php

use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsDepotManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['depots.create', 'depots.edit', 'depots.delete', 'depots.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Depot']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['depots.create', 'depots.edit', 'depots.delete', 'depots.view']);

    test()->actingAs($user);

    return $user;
}

function createLocationForDepot(): Location
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

    return Location::create([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
    ]);
}

test('depot can be created with generated code', function () {
    actingAsDepotManager();
    $location = createLocationForDepot();

    $response = $this->postJson('/depots', [
        'location_id' => $location->id,
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $depot = Depot::first();

    expect($depot->name)->toBe('Main Depot')
        ->and($depot->location_id)->toBe($location->id)
        ->and($depot->code)->toBe(generate_code('Depot Module', 1, 3, 'DPM'));
});

test('depot can be updated', function () {
    actingAsDepotManager();
    $location = createLocationForDepot();

    $depot = Depot::create([
        'location_id' => $location->id,
        'code' => 'DPM' . now()->year . '#001',
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    $response = $this->putJson('/depots/' . $depot->id, [
        'location_id' => $location->id,
        'name' => 'North Depot',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($depot->refresh()->name)->toBe('North Depot')
        ->and($depot->is_active)->toBeFalse();
});

test('depot status can be changed', function () {
    actingAsDepotManager();
    $location = createLocationForDepot();

    $depot = Depot::create([
        'location_id' => $location->id,
        'code' => 'DPM' . now()->year . '#001',
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    $response = $this->postJson('/depots/status', [
        'id' => $depot->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($depot->refresh()->is_active)->toBeFalse();
});

test('depots can be filtered by status', function () {
    actingAsDepotManager();
    $location = createLocationForDepot();

    Depot::create([
        'location_id' => $location->id,
        'code' => 'DPM' . now()->year . '#001',
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    Depot::create([
        'location_id' => $location->id,
        'code' => 'DPM' . now()->year . '#002',
        'name' => 'North Depot',
        'is_active' => false,
    ]);

    $response = $this->getJson('/depots?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('North Depot')
        ->and($response->json('data.0.location_name'))->toBe('Kakkanad');
});

test('depot can be deleted', function () {
    actingAsDepotManager();
    $location = createLocationForDepot();

    $depot = Depot::create([
        'location_id' => $location->id,
        'code' => 'DPM' . now()->year . '#001',
        'name' => 'Main Depot',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/depots/' . $depot->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($depot->fresh())->toBeNull();
});
