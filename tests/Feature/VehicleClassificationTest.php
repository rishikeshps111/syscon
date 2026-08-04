<?php

use App\Models\User;
use App\Models\VehicleClassification;
use Spatie\Permission\Models\Permission;

function actingAsVehicleClassificationManager(): void
{
    $permissions = [
        'vehicle-classifications.view', 'vehicle-classifications.create', 'vehicle-classifications.edit',
        'vehicle-classifications.delete', 'vehicle-classifications.export', 'vehicle-classifications.status',
    ];
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);
    test()->actingAs($user);
}

test('vehicle classification can be created', function () {
    actingAsVehicleClassificationManager();

    $this->postJson('/vehicle-classifications', [
        'title' => 'Express', 'description' => 'Express service', 'is_active' => true,
    ])->assertCreated()->assertJsonPath('success', true);

    $this->assertDatabaseHas('vehicle_classifications', [
        'title' => 'Express', 'description' => 'Express service', 'is_active' => true,
    ]);
});

test('vehicle classification can be updated and its status toggled', function () {
    actingAsVehicleClassificationManager();
    $record = VehicleClassification::create(['title' => 'Express', 'is_active' => true]);

    $this->putJson('/vehicle-classifications/' . $record->id, [
        'title' => 'Deluxe', 'description' => null, 'is_active' => true,
    ])->assertOk();
    $this->postJson('/vehicle-classifications/status', ['id' => $record->id, 'status' => false])->assertOk();

    expect($record->refresh()->title)->toBe('Deluxe')->and($record->is_active)->toBeFalse();
});

test('vehicle classification can be deleted', function () {
    actingAsVehicleClassificationManager();
    $record = VehicleClassification::create(['title' => 'Express', 'is_active' => true]);
    $this->deleteJson('/vehicle-classifications/' . $record->id)->assertOk();
    expect($record->fresh())->toBeNull();
});
