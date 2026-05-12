<?php

use App\Models\ServiceType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsServiceTypeManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['service-types.create', 'service-types.edit', 'service-types.delete', 'service-types.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Service Type']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['service-types.create', 'service-types.edit', 'service-types.delete', 'service-types.view']);

    test()->actingAs($user);

    return $user;
}

test('service type can be created with generated code', function () {
    actingAsServiceTypeManager();

    $response = $this->postJson('/service-types', [
        'name' => 'Express Delivery',
        'description' => 'Fast delivery service',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $serviceType = ServiceType::first();

    expect($serviceType->name)->toBe('Express Delivery')
        ->and($serviceType->description)->toBe('Fast delivery service')
        ->and($serviceType->code)->toBe(generate_code('Service Type Module', 1, 3, 'SRT'));
});

test('service type can be updated', function () {
    actingAsServiceTypeManager();

    $serviceType = ServiceType::create([
        'code' => 'SRT' . now()->year . '#001',
        'name' => 'Express Delivery',
        'description' => 'Fast delivery service',
        'is_active' => true,
    ]);

    $response = $this->putJson('/service-types/' . $serviceType->id, [
        'name' => 'Standard Delivery',
        'description' => 'Regular delivery service',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($serviceType->refresh()->name)->toBe('Standard Delivery')
        ->and($serviceType->description)->toBe('Regular delivery service')
        ->and($serviceType->is_active)->toBeFalse();
});

test('service type status can be changed', function () {
    actingAsServiceTypeManager();

    $serviceType = ServiceType::create([
        'code' => 'SRT' . now()->year . '#001',
        'name' => 'Express Delivery',
        'is_active' => true,
    ]);

    $response = $this->postJson('/service-types/status', [
        'id' => $serviceType->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($serviceType->refresh()->is_active)->toBeFalse();
});

test('service types can be filtered by status', function () {
    actingAsServiceTypeManager();

    ServiceType::create([
        'code' => 'SRT' . now()->year . '#001',
        'name' => 'Express Delivery',
        'is_active' => true,
    ]);

    ServiceType::create([
        'code' => 'SRT' . now()->year . '#002',
        'name' => 'Standard Delivery',
        'is_active' => false,
    ]);

    $response = $this->getJson('/service-types?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Standard Delivery');
});

test('service type can be deleted', function () {
    actingAsServiceTypeManager();

    $serviceType = ServiceType::create([
        'code' => 'SRT' . now()->year . '#001',
        'name' => 'Express Delivery',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/service-types/' . $serviceType->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($serviceType->fresh())->toBeNull();
});
