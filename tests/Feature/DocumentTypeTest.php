<?php

use App\Models\DocumentType;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsDocumentTypeManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['document-types.create', 'document-types.edit', 'document-types.delete', 'document-types.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Document Type']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['document-types.create', 'document-types.edit', 'document-types.delete', 'document-types.view']);

    test()->actingAs($user);

    return $user;
}

test('document type can be created with generated code', function () {
    actingAsDocumentTypeManager();

    $response = $this->postJson('/document-types', [
        'name' => 'Driving License',
        'applicable_for' => 'driver',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $documentType = DocumentType::first();

    expect($documentType->name)->toBe('Driving License')
        ->and($documentType->applicable_for)->toBe('driver')
        ->and($documentType->is_mandatory)->toBeTrue()
        ->and($documentType->is_expiry_required)->toBeTrue()
        ->and($documentType->code)->toBe(generate_code('Document Type Module', 1, 3, 'DOCT'));
});

test('document type can be updated', function () {
    actingAsDocumentTypeManager();

    $documentType = DocumentType::create([
        'code' => 'DOCT' . now()->year . '#001',
        'name' => 'Driving License',
        'applicable_for' => 'driver',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    $response = $this->putJson('/document-types/' . $documentType->id, [
        'name' => 'Vehicle RC',
        'applicable_for' => 'vehicle',
        'is_active' => false,
        'is_mandatory' => false,
        'is_expiry_required' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($documentType->refresh()->name)->toBe('Vehicle RC')
        ->and($documentType->applicable_for)->toBe('vehicle')
        ->and($documentType->is_active)->toBeFalse()
        ->and($documentType->is_mandatory)->toBeFalse()
        ->and($documentType->is_expiry_required)->toBeFalse();
});

test('document type status can be changed', function () {
    actingAsDocumentTypeManager();

    $documentType = DocumentType::create([
        'code' => 'DOCT' . now()->year . '#001',
        'name' => 'Driving License',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    $response = $this->postJson('/document-types/status', [
        'id' => $documentType->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($documentType->refresh()->is_active)->toBeFalse();
});

test('document types can be filtered by status', function () {
    actingAsDocumentTypeManager();

    DocumentType::create([
        'code' => 'DOCT' . now()->year . '#001',
        'name' => 'Driving License',
        'applicable_for' => 'driver',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    DocumentType::create([
        'code' => 'DOCT' . now()->year . '#002',
        'name' => 'Vehicle RC',
        'applicable_for' => 'vehicle',
        'is_active' => false,
        'is_mandatory' => false,
        'is_expiry_required' => false,
    ]);

    $response = $this->getJson('/document-types?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Vehicle RC')
        ->and($response->json('data.0.applies_to'))->toBe('Vehicle');
});

test('document type can be deleted', function () {
    actingAsDocumentTypeManager();

    $documentType = DocumentType::create([
        'code' => 'DOCT' . now()->year . '#001',
        'name' => 'Driving License',
        'is_active' => true,
        'is_mandatory' => true,
        'is_expiry_required' => true,
    ]);

    $response = $this->deleteJson('/document-types/' . $documentType->id);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($documentType->fresh())->toBeNull();
});
