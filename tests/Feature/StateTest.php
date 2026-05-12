<?php

use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

function actingAsStateManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['states.create', 'states.edit', 'states.delete', 'states.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'State']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['states.create', 'states.edit', 'states.delete', 'states.view']);

    test()->actingAs($user);

    return $user;
}

test('state can be created with generated code', function () {
    actingAsStateManager();

    $response = $this->postJson('/states', [
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $state = State::first();

    expect($state->name)->toBe('Kerala')
        ->and($state->code)->toBe(generate_code('State Module', 1, 3, 'ST'))
        ->and($state->is_default)->toBeTrue();
});

test('only one state can be default', function () {
    actingAsStateManager();

    $first = State::create([
        'code' => 'ST/' . now()->year . '/001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->postJson('/states', [
        'name' => 'Tamil Nadu',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response->assertCreated();

    expect($first->refresh()->is_default)->toBeFalse()
        ->and(State::where('is_default', true)->count())->toBe(1)
        ->and(State::where('name', 'Tamil Nadu')->first()->is_default)->toBeTrue();
});

test('state status can be changed', function () {
    actingAsStateManager();

    $state = State::create([
        'code' => 'ST/' . now()->year . '/001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);

    $response = $this->postJson('/states/status', [
        'id' => $state->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($state->refresh()->is_active)->toBeFalse();
});

test('states can be filtered by status', function () {
    actingAsStateManager();

    State::create([
        'code' => 'ST/' . now()->year . '/001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);

    State::create([
        'code' => 'ST/' . now()->year . '/002',
        'name' => 'Tamil Nadu',
        'is_active' => false,
        'is_default' => false,
    ]);

    $response = $this->getJson('/states?status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Tamil Nadu');
});

test('state cannot be deleted when related records exist', function () {
    actingAsStateManager();

    $state = State::create([
        'code' => 'ST/' . now()->year . '/001',
        'name' => 'Kerala',
        'is_active' => true,
        'is_default' => false,
    ]);

    DB::table('districts')->insert([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->deleteJson('/states/' . $state->id);

    $response->assertStatus(422);
    expect($state->fresh())->not->toBeNull();
});
