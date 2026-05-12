<?php

use App\Models\User;
use App\Models\VehicleClassification;
use Spatie\Permission\Models\Permission;

function actingAsVehicleClassificationManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['vehicle-classifications.create', 'vehicle-classifications.edit', 'vehicle-classifications.delete', 'vehicle-classifications.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Vehicle Classification']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo([
        'vehicle-classifications.create',
        'vehicle-classifications.edit',
        'vehicle-classifications.delete',
        'vehicle-classifications.view',
    ]);

    test()->actingAs($user);

    return $user;
}

test('vehicle classification can be created with generated code', function () {
    actingAsVehicleClassificationManager();

    $response = $this->postJson('/vehicle-classifications', [
        'name' => 'Electric Van',
        'capacity' => 750,
        'fuel_type' => 'ev',
        'description' => 'Electric cargo van',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $vehicleClassification = VehicleClassification::first();

    expect($vehicleClassification->name)->toBe('Electric Van')
        ->and($vehicleClassification->capacity)->toBe(750)
        ->and($vehicleClassification->fuel_type)->toBe('ev')
        ->and($vehicleClassification->description)->toBe('Electric cargo van')
        ->and($vehicleClassification->code)->toBe(generate_code('Vehicle Classification Module', 1, 3, 'VC'));
});

test('vehicle classification can be updated', function () {
    actingAsVehicleClassificationManager();

    $vehicleClassification = VehicleClassification::create([
        'code' => 'VC' . now()->year . '#001',
        'name' => 'Electric Van',
        'capacity' => 750,
        'fuel_type' => 'ev',
        'description' => 'Electric cargo van',
        'is_active' => true,
    ]);

    $response = $this->putJson('/vehicle-classifications/' . $vehicleClassification->id, [
        'name' => 'Heavy Truck',
        'capacity' => 12000,
        'fuel_type' => 'diesel',
        'description' => 'High-capacity truck',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($vehicleClassification->refresh()->name)->toBe('Heavy Truck')
        ->and($vehicleClassification->capacity)->toBe(12000)
        ->and($vehicleClassification->fuel_type)->toBe('diesel')
        ->and($vehicleClassification->is_active)->toBeFalse();
});

test('vehicle classification status can be changed', function () {
    actingAsVehicleClassificationManager();

    $vehicleClassification = VehicleClassification::create([
        'code' => 'VC' . now()->year . '#001',
        'name' => 'Electric Van',
        'is_active' => true,
    ]);

    $response = $this->postJson('/vehicle-classifications/status', [
        'id' => $vehicleClassification->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($vehicleClassification->refresh()->is_active)->toBeFalse();
});

test('vehicle classifications can be filtered by status', function () {
    actingAsVehicleClassificationManager();

    VehicleClassification::create([
        'code' => 'VC' . now()->year . '#001',
        'name' => 'Electric Van',
        'fuel_type' => 'ev',
        'is_active' => true,
    ]);

    VehicleClassification::create([
        'code' => 'VC' . now()->year . '#002',
        'name' => 'Heavy Truck',
        'fuel_type' => 'diesel',
        'is_active' => false,
    ]);

    $response = $this->getJson('/vehicle-classifications?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Heavy Truck')
        ->and($response->json('data.0.fuel'))->toBe('Diesel');
});

test('vehicle classification can be deleted', function () {
    actingAsVehicleClassificationManager();

    $vehicleClassification = VehicleClassification::create([
        'code' => 'VC' . now()->year . '#001',
        'name' => 'Electric Van',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/vehicle-classifications/' . $vehicleClassification->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($vehicleClassification->fresh())->toBeNull();
});
