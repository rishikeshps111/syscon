<?php

use App\Models\District;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

function actingAsDistrictManager(): User
{
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (['districts.create', 'districts.edit', 'districts.delete', 'districts.view'] as $permission) {
        Permission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'web'],
            ['group_name' => 'District']
        );
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['districts.create', 'districts.edit', 'districts.delete', 'districts.view']);

    test()->actingAs($user);

    return $user;
}

function createStateForDistrict(string $name = 'Kerala'): State
{
    return State::create([
        'code' => 'ST' . now()->year . '#' . str_pad((string) (State::count() + 1), 3, '0', STR_PAD_LEFT),
        'name' => $name,
        'is_active' => true,
        'is_default' => false,
    ]);
}

test('district can be created with generated code', function () {
    actingAsDistrictManager();
    $state = createStateForDistrict();

    $response = $this->postJson('/districts', [
        'state_id' => $state->id,
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $district = District::first();

    expect($district->name)->toBe('Ernakulam')
        ->and($district->state_id)->toBe($state->id)
        ->and($district->code)->toBe(generate_code('District Module', 1, 3, 'DS'))
        ->and($district->is_default)->toBeTrue();
});

test('only one district can be default', function () {
    actingAsDistrictManager();
    $state = createStateForDistrict();

    $first = District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response = $this->postJson('/districts', [
        'state_id' => $state->id,
        'name' => 'Kozhikode',
        'is_active' => true,
        'is_default' => true,
    ]);

    $response->assertCreated();

    expect($first->refresh()->is_default)->toBeFalse()
        ->and(District::where('is_default', true)->count())->toBe(1)
        ->and(District::where('name', 'Kozhikode')->first()->is_default)->toBeTrue();
});

test('districts can be filtered by state and status', function () {
    actingAsDistrictManager();
    $kerala = createStateForDistrict();
    $tamilNadu = createStateForDistrict('Tamil Nadu');

    District::create([
        'state_id' => $kerala->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
    ]);

    District::create([
        'state_id' => $tamilNadu->id,
        'code' => 'DS' . now()->year . '#002',
        'name' => 'Chennai',
        'is_active' => false,
        'is_default' => false,
    ]);

    $response = $this->getJson('/districts?state_id=' . $tamilNadu->id . '&status=0', [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();

    expect($response->json('recordsFiltered'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Chennai');
});

test('district status can be changed', function () {
    actingAsDistrictManager();
    $state = createStateForDistrict();

    $district = District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
    ]);

    $response = $this->postJson('/districts/status', [
        'id' => $district->id,
        'status' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($district->refresh()->is_active)->toBeFalse();
});

test('district cannot be deleted when related records exist', function () {
    actingAsDistrictManager();
    $state = createStateForDistrict();

    $district = District::create([
        'state_id' => $state->id,
        'code' => 'DS' . now()->year . '#001',
        'name' => 'Ernakulam',
        'is_active' => true,
        'is_default' => false,
    ]);

    DB::table('locations')->insert([
        'state_id' => $state->id,
        'district_id' => $district->id,
        'code' => 'LOC' . now()->year . '#001',
        'name' => 'Kakkanad',
        'is_active' => true,
        'is_default' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->deleteJson('/districts/' . $district->id);

    $response->assertStatus(422);
    expect($district->fresh())->not->toBeNull();
});
