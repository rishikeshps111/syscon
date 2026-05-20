<?php

use App\Models\Oem;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsOemManager(): array
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['oems.delete', 'oems.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'OEM']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['oems.delete', 'oems.view']);

    $state = State::create([
        'code' => 'ST2026#001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);

    test()->withSession(['_token' => 'test-token']);
    test()->actingAs($user);

    return compact('state');
}

test('oems can be filtered by state and status', function () {
    $data = actingAsOemManager();
    $otherState = State::create([
        'code' => 'ST2026#002',
        'name' => 'Tamil Nadu',
        'is_active' => true,
        'is_default' => false,
    ]);

    Oem::create([
        'state_id' => $data['state']->id,
        'oem_code' => 'OEM001',
        'oem_name' => 'Alpha Motors',
        'oem_type' => 'Manufacturer',
        'registration_type' => 'Company',
        'gst_number' => 'GSTALPHA001',
        'pan_number' => 'PANALPHA001',
        'status' => 'Active',
        'is_verified' => true,
    ]);

    Oem::create([
        'state_id' => $otherState->id,
        'oem_code' => 'OEM002',
        'oem_name' => 'Beta Services',
        'oem_type' => 'Service Provider',
        'registration_type' => 'Partnership',
        'gst_number' => 'GSTBETA001',
        'pan_number' => 'PANBETA001',
        'status' => 'Inactive',
        'is_verified' => false,
    ]);

    $response = $this->getJson('/oems?state_id=' . $data['state']->id . '&status=Active', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.oem_code'))->toBe('OEM001')
        ->and($response->json('data.0.state'))->toBe('Kerala');
});

test('oem can be deleted', function () {
    $data = actingAsOemManager();

    $oem = Oem::create([
        'state_id' => $data['state']->id,
        'oem_code' => 'OEM001',
        'oem_name' => 'Alpha Motors',
        'oem_type' => 'Manufacturer',
        'registration_type' => 'Company',
        'gst_number' => 'GSTALPHA001',
        'pan_number' => 'PANALPHA001',
        'status' => 'Active',
        'is_verified' => true,
    ]);

    $response = $this->deleteJson('/oems/' . $oem->id, [
        '_token' => 'test-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($oem->fresh())->toBeNull();
});
