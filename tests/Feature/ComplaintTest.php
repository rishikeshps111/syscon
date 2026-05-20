<?php

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function actingAsComplaintManager(): array
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['Super Admin', 'Driver', 'Controller', 'Supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    foreach (['complaints.create', 'complaints.edit', 'complaints.delete', 'complaints.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'Complaints']
        );
    }

    $manager = User::factory()->create();
    $manager->givePermissionTo(['complaints.create', 'complaints.edit', 'complaints.delete', 'complaints.view']);

    $controller = User::factory()->create(['name' => 'Controller One', 'code' => 'CTL2026#001', 'is_active' => true]);
    $controller->assignRole('Controller');

    $supervisor = User::factory()->create(['name' => 'Supervisor One', 'code' => 'SUP2026#001', 'is_active' => true]);
    $supervisor->assignRole('Supervisor');

    $driver = User::factory()->create(['name' => 'Driver One', 'code' => 'DRV2026#001', 'is_active' => true]);
    $driver->assignRole('Driver');

    $category = ComplaintCategory::create([
        'code' => 'CC2026#001',
        'name' => 'Late Reporting',
        'is_active' => true,
    ]);

    test()->withSession(['_token' => 'test-token']);
    test()->actingAs($manager);

    return compact('controller', 'supervisor', 'driver', 'category');
}

test('complaint can be created with generated code', function () {
    $data = actingAsComplaintManager();
    Storage::fake('public');

    $response = $this->post('/complaints', [
        '_token' => 'test-token',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'controller',
        'reported_by_user_id' => $data['controller']->id,
        'against_role' => 'driver',
        'against_user_id' => $data['driver']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Driver reported late.',
        'severity' => 'medium',
        'remarks' => 'Initial note',
        'attachments' => [
            UploadedFile::fake()->image('proof-one.jpg'),
            UploadedFile::fake()->create('proof-two.pdf', 20, 'application/pdf'),
        ],
    ]);

    $response->assertRedirect(route('complaints.index', ['reported_by_role' => 'controller']));

    $complaint = Complaint::first();

    expect($complaint->code)->toBe(generate_code('Complaint Module', 1, 3, 'CMP'))
        ->and($complaint->reportedBy->is($data['controller']))->toBeTrue()
        ->and($complaint->againstUser->is($data['driver']))->toBeTrue()
        ->and($complaint->category->is($data['category']))->toBeTrue()
        ->and($complaint->status)->toBe('pending')
        ->and($complaint->attachment_paths)->toHaveCount(2);

    foreach ($complaint->attachment_paths as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('supervisor complaint can be updated against controller', function () {
    $data = actingAsComplaintManager();

    $complaint = Complaint::create([
        'code' => 'CMP2026#001',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'supervisor',
        'reported_by_user_id' => $data['supervisor']->id,
        'against_role' => 'driver',
        'against_user_id' => $data['driver']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Old description',
        'severity' => 'low',
    ]);

    $response = $this->put('/complaints/' . $complaint->id, [
        '_token' => 'test-token',
        'complaint_date' => '2026-05-21',
        'reported_by_role' => 'supervisor',
        'reported_by_user_id' => $data['supervisor']->id,
        'against_role' => 'controller',
        'against_user_id' => $data['controller']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Updated description',
        'severity' => 'high',
    ]);

    $response->assertRedirect(route('complaints.index', ['reported_by_role' => 'supervisor']));

    expect($complaint->refresh()->against_role)->toBe('controller')
        ->and($complaint->against_user_id)->toBe($data['controller']->id)
        ->and($complaint->severity)->toBe('high');
});

test('controller cannot raise complaint against controller', function () {
    $data = actingAsComplaintManager();

    $response = $this->post('/complaints', [
        '_token' => 'test-token',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'controller',
        'reported_by_user_id' => $data['controller']->id,
        'against_role' => 'controller',
        'against_user_id' => $data['controller']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Invalid hierarchy.',
        'severity' => 'medium',
    ]);

    $response->assertSessionHasErrors('against_role');
});

test('complaints can be filtered by supervisor tab and status', function () {
    $data = actingAsComplaintManager();

    Complaint::create([
        'code' => 'CMP2026#001',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'supervisor',
        'reported_by_user_id' => $data['supervisor']->id,
        'against_role' => 'driver',
        'against_user_id' => $data['driver']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Supervisor complaint',
        'severity' => 'high',
        'status' => 'in_review',
    ]);

    Complaint::create([
        'code' => 'CMP2026#002',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'controller',
        'reported_by_user_id' => $data['controller']->id,
        'against_role' => 'driver',
        'against_user_id' => $data['driver']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Controller complaint',
        'severity' => 'medium',
        'status' => 'pending',
    ]);

    $response = $this->getJson('/complaints?reported_by_role=supervisor&status=in_review', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.code'))->toBe('CMP2026#001');
});

test('complaint status assignment and delete work', function () {
    $data = actingAsComplaintManager();

    $complaint = Complaint::create([
        'code' => 'CMP2026#001',
        'complaint_date' => '2026-05-20',
        'reported_by_role' => 'supervisor',
        'reported_by_user_id' => $data['supervisor']->id,
        'against_role' => 'driver',
        'against_user_id' => $data['driver']->id,
        'complaint_category_id' => $data['category']->id,
        'description' => 'Supervisor complaint',
        'severity' => 'high',
    ]);

    $this->postJson('/complaints/' . $complaint->id . '/change-status', [
        '_token' => 'test-token',
        'status' => 'in_review',
    ])->assertOk();

    $this->postJson('/complaints/' . $complaint->id . '/assign-action', [
        '_token' => 'test-token',
        'assigned_to' => 'hr',
        'action_taken' => 'warning',
        'action_date' => '2026-05-22',
    ])->assertOk();

    expect($complaint->refresh()->status)->toBe('in_review')
        ->and($complaint->assigned_to)->toBe('hr')
        ->and($complaint->assigned_to_label)->toBe('HR')
        ->and($complaint->action_taken)->toBe('warning')
        ->and($complaint->action_taken_label)->toBe('Warning');

    $this->deleteJson('/complaints/' . $complaint->id, [
        '_token' => 'test-token',
    ])->assertOk();

    expect($complaint->fresh())->toBeNull();
});
