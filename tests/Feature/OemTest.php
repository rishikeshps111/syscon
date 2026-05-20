<?php

use App\Models\Oem;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsOemManager(): array
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['oems.create', 'oems.edit', 'oems.delete', 'oems.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'OEM']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['oems.create', 'oems.edit', 'oems.delete', 'oems.view']);

    $state = State::create([
        'code' => 'ST2026#001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);
    $district = District::create([
        'state_id' => $state->id,
        'code' => 'DS2026#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
    ]);
    $location = Location::create([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC2026#001',
        'name' => 'Kakkanad',
        'pincode' => '682030',
        'is_active' => true,
        'is_default' => false,
    ]);

    test()->withSession(['_token' => 'test-token']);
    test()->actingAs($user);

    return compact('state', 'district', 'location');
}

function validOemPayload(array $data): array
{
    return [
        '_token' => 'test-token',
        'state_id' => $data['state']->id,
        'oem_name' => 'Alpha Motors',
        'short_name' => 'Alpha',
        'oem_type' => 'Manufacturer',
        'registration_type' => 'Company',
        'gst_number' => '32AALCA1234A1Z5',
        'pan_number' => 'AALCA1234A',
        'cin_number' => 'U34100KL2020PTC001001',
        'remarks' => 'Approved partner.',
        'primary_contact_index' => 0,
        'contacts' => [
            [
                'contact_person' => 'Rahul Menon',
                'designation' => 'Manager',
                'phone_country_code' => '+91',
                'phone' => '9876543210',
                'alternate_phone_country_code' => '+91',
                'alternate_phone' => '9876500001',
                'email' => 'rahul@example.com',
            ],
        ],
        'addresses' => [
            [
                'address_type' => 'HQ',
                'state_id' => $data['state']->id,
                'district_id' => $data['district']->id,
                'city_id' => $data['location']->id,
                'address_line1' => 'Infopark Road',
                'address_line2' => 'Tower A',
                'pincode' => '682030',
                'latitude' => '10.0159',
                'longitude' => '76.3419',
            ],
        ],
    ];
}

test('oem can be created with contact and address', function () {
    $data = actingAsOemManager();

    $response = $this->post('/oems', validOemPayload($data));

    $response->assertRedirect(route('oems.index'));

    $oem = Oem::with(['contacts', 'addresses'])->first();

    expect($oem->oem_code)->toBe(generate_code('OEM Module', 1, 3, 'OEM'))
        ->and($oem->oem_name)->toBe('Alpha Motors')
        ->and($oem->contacts)->toHaveCount(1)
        ->and($oem->contacts->first()->is_primary)->toBeTrue()
        ->and($oem->addresses)->toHaveCount(1)
        ->and($oem->addresses->first()->city_id)->toBe($data['location']->id);
});

test('oem can be updated with contact and address', function () {
    $data = actingAsOemManager();
    $payload = validOemPayload($data);

    $this->post('/oems', $payload);
    $oem = Oem::first();

    $payload['oem_name'] = 'Alpha Motors Updated';
    $payload['contacts'][0]['contact_person'] = 'Updated Person';
    $payload['addresses'][0]['address_line1'] = 'Updated Address';

    $response = $this->put('/oems/' . $oem->id, $payload);

    $response->assertRedirect(route('oems.index'));

    $oem->refresh()->load(['contacts', 'addresses']);

    expect($oem->oem_name)->toBe('Alpha Motors Updated')
        ->and($oem->contacts)->toHaveCount(1)
        ->and($oem->contacts->first()->contact_person)->toBe('Updated Person')
        ->and($oem->addresses->first()->address_line1)->toBe('Updated Address');
});

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
