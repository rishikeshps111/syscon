<?php

use App\Models\TripNature;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsTripNatureManager(): void
{
    $permissions = [
        'trip-natures.view', 'trip-natures.create', 'trip-natures.edit',
        'trip-natures.delete', 'trip-natures.export', 'trip-natures.status',
    ];
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);
    test()->actingAs($user);
}

test('trip nature supports create update status and delete', function () {
    actingAsTripNatureManager();

    $this->postJson('/trip-natures', [
        'title' => 'S/OFF', 'description' => null, 'is_active' => true,
    ])->assertCreated();
    $record = TripNature::firstOrFail();

    $this->putJson('/trip-natures/' . $record->id, [
        'title' => 'ADD ON', 'description' => 'Additional service', 'is_active' => true,
    ])->assertOk();
    $this->postJson('/trip-natures/status', ['id' => $record->id, 'status' => false])->assertOk();
    expect($record->refresh()->title)->toBe('ADD ON')->and($record->is_active)->toBeFalse();

    $this->deleteJson('/trip-natures/' . $record->id)->assertOk();
    expect($record->fresh())->toBeNull();
});
