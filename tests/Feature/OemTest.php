<?php

use App\Models\Oem;
use App\Models\OemBankDetail;
use App\Models\OemStateMapping;
use App\Models\OemType;
use App\Models\DocumentType;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

function actingAsOemManager(): array
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['oems.create', 'oems.edit', 'oems.delete', 'oems.view', 'oem-types.create', 'oem-types.edit', 'oem-types.delete', 'oem-types.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'OEM']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['oems.create', 'oems.edit', 'oems.delete', 'oems.view', 'oem-types.create', 'oem-types.edit', 'oem-types.delete', 'oem-types.view']);

    foreach (['Manufacturer', 'Service Provider', 'Dealer'] as $name) {
        OemType::firstOrCreate(['name' => $name], ['is_active' => true]);
    }

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

test('oem detail page and pdf can be viewed', function () {
    $data = actingAsOemManager();
    $payload = validOemPayload($data);

    $this->post('/oems', $payload);
    $oem = Oem::first();

    $showResponse = $this->get(route('oems.show', $oem));

    $showResponse->assertOk()
        ->assertSee('Alpha Motors')
        ->assertSee('Download PDF')
        ->assertSee('Contact Details')
        ->assertSee('Address Details');

    $pdfResponse = $this->get(route('oems.download-pdf', $oem));

    $pdfResponse->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('oems can be filtered by state type and status', function () {
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

    $response = $this->getJson('/oems?state_id=' . $data['state']->id . '&oem_type=Manufacturer&status=Active', [
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

test('oem can be verified and status can be changed', function () {
    $data = actingAsOemManager();
    $userId = auth()->id();

    $oem = Oem::create([
        'state_id' => $data['state']->id,
        'oem_code' => 'OEM001',
        'oem_name' => 'Alpha Motors',
        'oem_type' => 'Manufacturer',
        'registration_type' => 'Company',
        'gst_number' => 'GSTALPHA001',
        'pan_number' => 'PANALPHA001',
        'status' => 'Active',
        'is_verified' => false,
    ]);

    $verifyResponse = $this->postJson(route('oems.verify', $oem), [
        '_token' => 'test-token',
    ]);

    $verifyResponse->assertOk()
        ->assertJsonPath('success', true);

    $oem->refresh();

    expect($oem->is_verified)->toBeTrue()
        ->and($oem->verified_by)->toBe($userId)
        ->and($oem->verified_at)->not->toBeNull();

    $statusResponse = $this->postJson(route('oems.change-status', $oem), [
        '_token' => 'test-token',
        'status' => 'Blocked',
    ]);

    $statusResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($oem->fresh()->status)->toBe('Blocked');
});

test('oem documents can be uploaded listed and deleted', function () {
    Storage::fake('public');
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

    $documentType = DocumentType::create([
        'code' => 'DOCT001',
        'name' => 'OEM Registration Certificate',
        'applicable_for' => 'oem',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    $response = $this->postJson(route('oems.documents.store', $oem), [
        '_token' => 'test-token',
        'document_type_id' => $documentType->id,
        'expiry_date' => '2027-05-21',
        'document_file' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
        'is_verified' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $document = $oem->documents()->first();

    expect($document)->not->toBeNull()
        ->and($document->document_type_id)->toBe($documentType->id)
        ->and($document->is_verified)->toBeTrue();

    Storage::disk('public')->assertExists($document->file_path);

    $listResponse = $this->getJson(route('oems.documents.index', $oem), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $listResponse->assertOk();
    expect($listResponse->json('recordsTotal'))->toBe(1)
        ->and($listResponse->json('data.0.type'))->toBe('OEM Registration Certificate')
        ->and($listResponse->json('data.0.expiry_date'))->toBe('21-05-2027');

    $deleteResponse = $this->deleteJson(route('oem-documents.destroy', $document), [
        '_token' => 'test-token',
    ]);

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    Storage::disk('public')->assertMissing($document->file_path);
    expect($document->fresh())->toBeNull();
});

test('oem bank details can be created updated listed and deleted', function () {
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

    $response = $this->postJson(route('oems.bank-details.store', $oem), [
        '_token' => 'test-token',
        'account_name' => 'Alpha Motors Pvt Ltd',
        'account_number' => '123456789012',
        'bank_name' => 'Federal Bank',
        'branch' => 'Kakkanad',
        'ifsc_code' => 'FDRL0001234',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $bankDetail = $oem->bankDetails()->first();

    expect($bankDetail)->not->toBeNull()
        ->and($bankDetail->account_name)->toBe('Alpha Motors Pvt Ltd')
        ->and($bankDetail->is_primary)->toBeTrue();

    $secondDetail = OemBankDetail::create([
        'oem_id' => $oem->id,
        'account_name' => 'Alpha Motors Service',
        'account_number' => '987654321098',
        'bank_name' => 'South Indian Bank',
        'branch' => 'Edappally',
        'ifsc_code' => 'SIBL0001234',
        'is_primary' => false,
    ]);

    $updateResponse = $this->putJson(route('oem-bank-details.update', $secondDetail), [
        '_token' => 'test-token',
        'account_name' => 'Alpha Motors Service Updated',
        'account_number' => '987654321098',
        'bank_name' => 'South Indian Bank',
        'branch' => 'Edappally',
        'ifsc_code' => 'SIBL0001234',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondDetail->fresh()->is_primary)->toBeFalse()
        ->and($bankDetail->fresh()->is_primary)->toBeTrue();

    $primaryResponse = $this->postJson(route('oem-bank-details.make-primary', $secondDetail), [
        '_token' => 'test-token',
    ]);

    $primaryResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondDetail->fresh()->is_primary)->toBeTrue()
        ->and($bankDetail->fresh()->is_primary)->toBeFalse();

    $listResponse = $this->getJson(route('oems.bank-details.index', $oem), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $listResponse->assertOk();
    expect($listResponse->json('recordsTotal'))->toBe(2)
        ->and($listResponse->json('data.0.account_name'))->toBe('Alpha Motors Service Updated');

    $deleteResponse = $this->deleteJson(route('oem-bank-details.destroy', $secondDetail), [
        '_token' => 'test-token',
    ]);

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondDetail->fresh())->toBeNull()
        ->and($bankDetail->fresh()->is_primary)->toBeTrue();
});

test('oem state mappings can be created updated toggled listed and deleted', function () {
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

    $otherState = State::create([
        'code' => 'ST2026#003',
        'name' => 'Karnataka',
        'is_active' => true,
        'is_default' => false,
    ]);

    $response = $this->postJson(route('oems.state-mappings.store', $oem), [
        '_token' => 'test-token',
        'state_id' => $data['state']->id,
        'gst_number' => '32AALCA1234A1Z5',
        'status' => 1,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $mapping = $oem->stateMappings()->first();

    expect($mapping)->not->toBeNull()
        ->and($mapping->state_id)->toBe($data['state']->id)
        ->and($mapping->is_primary)->toBeTrue()
        ->and($mapping->status)->toBeTrue();

    $secondMapping = OemStateMapping::create([
        'oem_id' => $oem->id,
        'state_id' => $otherState->id,
        'gst_number' => '29AALCA1234A1Z8',
        'is_primary' => false,
        'status' => true,
    ]);

    $updateResponse = $this->putJson(route('oem-state-mappings.update', $secondMapping), [
        '_token' => 'test-token',
        'state_id' => $otherState->id,
        'gst_number' => '29AALCA1234A1Z9',
        'status' => 0,
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondMapping->fresh()->gst_number)->toBe('29AALCA1234A1Z9')
        ->and($secondMapping->fresh()->is_primary)->toBeFalse()
        ->and($secondMapping->fresh()->status)->toBeFalse();

    $primaryResponse = $this->postJson(route('oem-state-mappings.make-primary', $secondMapping), [
        '_token' => 'test-token',
    ]);

    $primaryResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondMapping->fresh()->is_primary)->toBeTrue()
        ->and($mapping->fresh()->is_primary)->toBeFalse();

    $listResponse = $this->getJson(route('oems.state-mappings.index', $oem), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $listResponse->assertOk();
    expect($listResponse->json('recordsTotal'))->toBe(2)
        ->and($listResponse->json('data.0.state'))->toBe('Karnataka')
        ->and($listResponse->json('data.0.gst_number'))->toBe('29AALCA1234A1Z9');

    $deleteResponse = $this->deleteJson(route('oem-state-mappings.destroy', $secondMapping), [
        '_token' => 'test-token',
    ]);

    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    expect($secondMapping->fresh())->toBeNull()
        ->and($mapping->fresh()->is_primary)->toBeTrue();
});
