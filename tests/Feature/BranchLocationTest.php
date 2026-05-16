<?php

use App\Models\BranchLocation;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsBranchLocationManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['branch-locations.create', 'branch-locations.edit', 'branch-locations.delete', 'branch-locations.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Branch Location']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['branch-locations.create', 'branch-locations.edit', 'branch-locations.delete', 'branch-locations.view']);

    test()->actingAs($user);

    return $user;
}

function createLocationForBranchLocation(
    string $stateName = 'Kerala',
    string $districtName = 'Ernakulam',
    string $locationName = 'Kakkanad'
): Location {
    $state = State::create([
        'code' => 'ST' . now()->year . '#' . str_pad((string) (State::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $stateName,
        'is_active' => true,
        'is_default' => false,
    ]);

    $district = District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#' . str_pad((string) (District::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $districtName,
        'is_active' => true,
        'is_default' => false,
    ]);

    return Location::create([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#' . str_pad((string) (Location::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $locationName,
        'is_active' => true,
        'is_default' => false,
    ]);
}

test('branch location can be created with generated code', function () {
    actingAsBranchLocationManager();
    $location = createLocationForBranchLocation();

    $response = $this->postJson('/branch-locations', [
        'state_id' => $location->state_id,
        'district_id' => $location->district_id,
        'location_id' => $location->id,
        'name' => 'Main Branch',
        'remarks' => 'Corporate office',
        'status' => 'active',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $branchLocation = BranchLocation::first();

    expect($branchLocation->name)->toBe('Main Branch')
        ->and($branchLocation->location_id)->toBe($location->id)
        ->and($branchLocation->remarks)->toBe('Corporate office')
        ->and($branchLocation->status)->toBe('active')
        ->and($branchLocation->code)->toBe(generate_code('Branch Location Module', 1, 3, 'BL'));
});

test('branch location dependent districts and locations are loaded', function () {
    actingAsBranchLocationManager();
    $keralaLocation = createLocationForBranchLocation();
    createLocationForBranchLocation('Tamil Nadu', 'Chennai', 'T. Nagar');

    $districtResponse = $this->getJson('/branch-locations/districts-by-state?state_id=' . $keralaLocation->state_id);
    $districtResponse->assertOk();

    expect($districtResponse->json())->toHaveCount(1)
        ->and($districtResponse->json('0.name'))->toBe('Ernakulam');

    $locationResponse = $this->getJson('/branch-locations/locations-by-district?state_id=' . $keralaLocation->state_id . '&district_id=' . $keralaLocation->district_id);
    $locationResponse->assertOk();

    expect($locationResponse->json())->toHaveCount(1)
        ->and($locationResponse->json('0.name'))->toBe('Kakkanad');
});

test('branch locations can be filtered by state district location and status', function () {
    actingAsBranchLocationManager();
    $keralaLocation = createLocationForBranchLocation();
    $tamilNaduLocation = createLocationForBranchLocation('Tamil Nadu', 'Chennai', 'T. Nagar');

    BranchLocation::create([
        'state_id' => $keralaLocation->state_id,
        'district_id' => $keralaLocation->district_id,
        'location_id' => $keralaLocation->id,
        'code' => 'BL' . now()->year . '#001',
        'name' => 'Main Branch',
        'status' => 'active',
    ]);

    BranchLocation::create([
        'state_id' => $tamilNaduLocation->state_id,
        'district_id' => $tamilNaduLocation->district_id,
        'location_id' => $tamilNaduLocation->id,
        'code' => 'BL' . now()->year . '#002',
        'name' => 'South Branch',
        'status' => 'inactive',
    ]);

    $response = $this->getJson('/branch-locations?state_id=' . $tamilNaduLocation->state_id . '&district_id=' . $tamilNaduLocation->district_id . '&location_id=' . $tamilNaduLocation->id . '&status=inactive', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('South Branch')
        ->and($response->json('data.0.location_name'))->toBe('T. Nagar');
});

test('branch location can be updated and deleted', function () {
    actingAsBranchLocationManager();
    $location = createLocationForBranchLocation();

    $branchLocation = BranchLocation::create([
        'state_id' => $location->state_id,
        'district_id' => $location->district_id,
        'location_id' => $location->id,
        'code' => 'BL' . now()->year . '#001',
        'name' => 'Main Branch',
        'status' => 'active',
    ]);

    $response = $this->putJson('/branch-locations/' . $branchLocation->id, [
        'state_id' => $location->state_id,
        'district_id' => $location->district_id,
        'location_id' => $location->id,
        'name' => 'North Branch',
        'remarks' => 'Updated',
        'status' => 'suspended',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($branchLocation->refresh()->name)->toBe('North Branch')
        ->and($branchLocation->status)->toBe('suspended');

    $deleteResponse = $this->deleteJson('/branch-locations/' . $branchLocation->id);
    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($branchLocation->fresh())->toBeNull();
});

test('branch location status can be changed from action modal', function () {
    actingAsBranchLocationManager();
    $location = createLocationForBranchLocation();

    $branchLocation = BranchLocation::create([
        'state_id' => $location->state_id,
        'district_id' => $location->district_id,
        'location_id' => $location->id,
        'code' => 'BL' . now()->year . '#001',
        'name' => 'Main Branch',
        'status' => 'active',
    ]);

    $response = $this->postJson('/branch-locations/status', [
        'id' => $branchLocation->id,
        'status' => 'suspended',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($branchLocation->refresh()->status)->toBe('suspended');
});
