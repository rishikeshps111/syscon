<?php

use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

function actingAsLocationManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['locations.create', 'locations.edit', 'locations.delete', 'locations.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Location']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['locations.create', 'locations.edit', 'locations.delete', 'locations.view']);

    test()->actingAs($user);

    return $user;
}

function createDistrictForLocation(string $stateName = 'Kerala', string $districtName = 'Ernakulam'): District
{
    $state = State::create([
        'code' => 'ST' . now()->year . '#' . str_pad((string) (State::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $stateName,
        'is_active' => true,
        'is_default' => false,
    ]);

    return District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#' . str_pad((string) (District::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $districtName,
        'is_active' => true,
        'is_default' => false,
    ]);
}

test('location can be created with generated code', function () {
    actingAsLocationManager();
    $district = createDistrictForLocation();

    $response = $this->postJson('/locations', [
        'state_id' => $district->state_id,
        'district_id' => $district->id,
        'name' => 'Kakkanad',
        'pincode' => '682030',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $location = Location::first();

    expect($location->name)->toBe('Kakkanad')
        ->and($location->pincode)->toBe('682030')
        ->and($location->state_id)->toBe($district->state_id)
        ->and($location->district_id)->toBe($district->id)
        ->and($location->code)->toBe(generate_code('Location Module', 1, 3, 'LOC'))
        ->and($location->is_default)->toBeTrue();
});

test('districts are loaded by selected state', function () {
    actingAsLocationManager();
    $keralaDistrict = createDistrictForLocation();
    createDistrictForLocation('Tamil Nadu', 'Chennai');

    $response = $this->getJson('/locations/districts-by-state?state_id=' . $keralaDistrict->state_id);

    $response->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.name'))->toBe('Ernakulam');
});

test('locations can be filtered by state district and status', function () {
    actingAsLocationManager();
    $keralaDistrict = createDistrictForLocation();
    $tamilNaduDistrict = createDistrictForLocation('Tamil Nadu', 'Chennai');

    Location::create([
        'state_id' => $keralaDistrict->state_id,
        'district_id' => $keralaDistrict->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
    ]);

    Location::create([
        'state_id' => $tamilNaduDistrict->state_id,
        'district_id' => $tamilNaduDistrict->id,
        'code' => 'LOC' . now()->year . '#002',
        'name' => 'T. Nagar',
        'is_active' => false,
        'is_default' => false,
    ]);

    $response = $this->getJson('/locations?state_id=' . $tamilNaduDistrict->state_id . '&district_id=' . $tamilNaduDistrict->id . '&status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('T. Nagar');
});

test('location status can be changed', function () {
    actingAsLocationManager();
    $district = createDistrictForLocation();

    $location = Location::create([
        'state_id' => $district->state_id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
    ]);

    $response = $this->postJson('/locations/status', [
        'id' => $location->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($location->refresh()->is_active)->toBeFalse();
});

test('location cannot be deleted when related records exist', function () {
    actingAsLocationManager();
    $district = createDistrictForLocation();

    Schema::create('depots', function ($table) {
        $table->id();
        $table->foreignId('location_id');
        $table->string('name');
        $table->timestamps();
    });

    $location = Location::create([
        'state_id' => $district->state_id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
    ]);

    DB::table('depots')->insert([
        'location_id' => $location->id,
        'name' => 'Main Depot',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->deleteJson('/locations/' . $location->id);

    $response->assertStatus(422);
    expect($location->fresh())->not->toBeNull();
});
