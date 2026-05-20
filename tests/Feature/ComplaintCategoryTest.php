<?php

use App\Models\ComplaintCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;

function actingAsComplaintCategoryManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['complaint-categories.create', 'complaint-categories.edit', 'complaint-categories.delete', 'complaint-categories.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Complaint Category']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['complaint-categories.create', 'complaint-categories.edit', 'complaint-categories.delete', 'complaint-categories.view']);

    test()->withSession(['_token' => 'test-token']);
    test()->actingAs($user);

    return $user;
}

test('complaint category can be created with generated code', function () {
    actingAsComplaintCategoryManager();

    $response = $this->postJson('/complaint-categories', [
        '_token' => 'test-token',
        'name' => 'Service Delay',
        'is_active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $complaintCategory = ComplaintCategory::first();

    expect($complaintCategory->name)->toBe('Service Delay')
        ->and($complaintCategory->code)->toBe(generate_code('Complaint Category Module', 1, 3, 'CC'));
});

test('complaint category can be updated', function () {
    actingAsComplaintCategoryManager();

    $complaintCategory = ComplaintCategory::create([
        'code' => 'CC' . now()->year . '#001',
        'name' => 'Service Delay',
        'is_active' => true,
    ]);

    $response = $this->putJson('/complaint-categories/' . $complaintCategory->id, [
        '_token' => 'test-token',
        'name' => 'Vehicle Condition',
        'is_active' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($complaintCategory->refresh()->name)->toBe('Vehicle Condition')
        ->and($complaintCategory->is_active)->toBeFalse();
});

test('complaint category status can be changed', function () {
    actingAsComplaintCategoryManager();

    $complaintCategory = ComplaintCategory::create([
        'code' => 'CC' . now()->year . '#001',
        'name' => 'Service Delay',
        'is_active' => true,
    ]);

    $response = $this->postJson('/complaint-categories/status', [
        '_token' => 'test-token',
        'id' => $complaintCategory->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($complaintCategory->refresh()->is_active)->toBeFalse();
});

test('complaint categories can be filtered by status', function () {
    actingAsComplaintCategoryManager();

    ComplaintCategory::create([
        'code' => 'CC' . now()->year . '#001',
        'name' => 'Service Delay',
        'is_active' => true,
    ]);

    ComplaintCategory::create([
        'code' => 'CC' . now()->year . '#002',
        'name' => 'Vehicle Condition',
        'is_active' => false,
    ]);

    $response = $this->getJson('/complaint-categories?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Vehicle Condition');
});

test('complaint category can be deleted', function () {
    actingAsComplaintCategoryManager();

    $complaintCategory = ComplaintCategory::create([
        'code' => 'CC' . now()->year . '#001',
        'name' => 'Service Delay',
        'is_active' => true,
    ]);

    $response = $this->deleteJson('/complaint-categories/' . $complaintCategory->id, [
        '_token' => 'test-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($complaintCategory->fresh())->toBeNull();
});
