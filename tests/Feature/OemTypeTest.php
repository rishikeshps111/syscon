<?php

use App\Models\OemType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsOemTypeManager(): void
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['oem-types.create', 'oem-types.edit', 'oem-types.delete', 'oem-types.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'OEM Type']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['oem-types.create', 'oem-types.edit', 'oem-types.delete', 'oem-types.view']);

    test()->withSession(['_token' => 'test-token']);
    test()->actingAs($user);
}

test('oem type can be managed', function () {
    actingAsOemTypeManager();

    $response = $this->postJson(route('oem-types.store'), [
        '_token' => 'test-token',
        'name' => 'Aggregator',
        'is_active' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $oemType = OemType::first();

    expect($oemType->name)->toBe('Aggregator')
        ->and($oemType->is_active)->toBeTrue();

    $listResponse = $this->getJson(route('oem-types.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $listResponse->assertOk();
    expect($listResponse->json('recordsTotal'))->toBe(1)
        ->and($listResponse->json('data.0.name'))->toBe('Aggregator');

    $updateResponse = $this->putJson(route('oem-types.update', $oemType), [
        '_token' => 'test-token',
        'name' => 'Aggregator Updated',
        'is_active' => 1,
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($oemType->fresh()->name)->toBe('Aggregator Updated');

    $statusResponse = $this->postJson(route('oem-types.status'), [
        '_token' => 'test-token',
        'id' => $oemType->id,
        'status' => 0,
    ]);

    $statusResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($oemType->fresh()->is_active)->toBeFalse();

    $deleteResponse = $this->deleteJson(route('oem-types.destroy', $oemType), [
        '_token' => 'test-token',
    ]);

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($oemType->fresh())->toBeNull();
});
