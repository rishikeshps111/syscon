<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function staffApiUser(): User
{
    $staff = User::factory()->create(['code' => 'MNGT001', 'password' => 'web-secret', 'is_active' => true]);
    $staff->assignRole(Role::findOrCreate('Staff', 'web'));

    return $staff;
}

function staffApiAdmin(bool $allowed = true): User
{
    $admin = User::factory()->create();
    if ($allowed) {
        $admin->givePermissionTo(Permission::findOrCreate('staff-management.edit', 'web'));
    }

    return $admin;
}

it('enables Staff API login while preserving the web password and returns Staff profile', function () {
    $staff = staffApiUser();
    $staff->staffProfile()->create(['father_name' => 'Example Father']);
    $passwordHash = $staff->password;

    $passcode = $this->actingAs(staffApiAdmin())
        ->postJson(route('staff-management.enable-api', $staff))
        ->assertOk()
        ->json('passcode');

    expect($passcode)->toMatch('/^\d{6}$/')
        ->and($staff->refresh()->password)->toBe($passwordHash)
        ->and(Hash::check($passcode, $staff->staff_api_passcode))->toBeTrue();

    $response = $this->postJson('/api/v1/login', ['phone' => 'MNGT001', 'passcode' => $passcode, 'type' => 'staff'])
        ->assertOk()
        ->assertJsonPath('data.user.type', 'staff')
        ->assertJsonPath('data.user.profile.father_name', 'Example Father');

    $token = $staff->tokens()->first();
    expect($token->abilities)->toContain('type:staff');
    $this->withToken($response->json('data.access_token'))->getJson('/api/v1/me')
        ->assertOk()->assertJsonPath('data.user.type', 'staff');
});

it('rejects disabled and inactive Staff API login without changing the web password', function () {
    $staff = staffApiUser();
    $staff->forceFill(['staff_api_passcode' => '123456'])->save();

    $this->postJson('/api/v1/login', ['phone' => $staff->code, 'passcode' => '123456', 'type' => 'staff'])
        ->assertUnprocessable()->assertJsonValidationErrors('phone');

    $staff->forceFill(['staff_api_enabled' => true, 'is_active' => false])->save();
    $this->postJson('/api/v1/login', ['phone' => $staff->code, 'passcode' => '123456', 'type' => 'staff'])
        ->assertUnprocessable()->assertJsonValidationErrors('phone');

    expect(Hash::check('web-secret', $staff->refresh()->password))->toBeTrue();
});

it('blocks Staff API access after three wrong passcodes without deactivating web login', function () {
    $staff = staffApiUser();
    $staff->forceFill(['staff_api_enabled' => true, 'staff_api_passcode' => '123456'])->save();
    $staff->createToken('Staff App', ['type:staff']);

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/login', ['phone' => $staff->code, 'passcode' => '000000', 'type' => 'staff'])
            ->assertUnprocessable()->assertJsonValidationErrors('passcode');
    }

    expect($staff->refresh()->staff_api_enabled)->toBeFalse()
        ->and($staff->is_active)->toBeTrue()
        ->and($staff->tokens()->count())->toBe(0)
        ->and(Hash::check('web-secret', $staff->password))->toBeTrue();
});

it('regenerates Staff passcode and revokes Staff tokens on regeneration disable and deactivation', function () {
    $staff = staffApiUser();
    $this->actingAs(staffApiAdmin());
    $first = $this->postJson(route('staff-management.enable-api', $staff))->assertOk()->json('passcode');
    $staff->createToken('Staff App', ['type:staff']);

    $second = $this->postJson(route('staff-management.regenerate-passcode', $staff))->assertOk()->json('passcode');
    expect($staff->tokens()->count())->toBe(0)
        ->and(Hash::check($first, $staff->refresh()->staff_api_passcode))->toBeFalse()
        ->and(Hash::check($second, $staff->staff_api_passcode))->toBeTrue();

    $this->postJson('/api/v1/login', ['phone' => $staff->code, 'passcode' => $first, 'type' => 'staff'])
        ->assertUnprocessable()->assertJsonValidationErrors('passcode');
    $staff->createToken('Staff App', ['type:staff']);
    $this->postJson(route('staff-management.disable-api', $staff))->assertOk();
    expect($staff->refresh()->staff_api_enabled)->toBeFalse()->and($staff->tokens()->count())->toBe(0);

    $this->postJson(route('staff-management.enable-api', $staff))->assertOk();
    $staff->createToken('Staff App', ['type:staff']);
    $this->postJson(route('staff-management.status'), ['id' => $staff->id, 'status' => false])->assertOk();
    expect($staff->refresh()->is_active)->toBeFalse()->and($staff->tokens()->count())->toBe(0);
});

it('requires an active Staff target and Staff edit permission for API access actions', function () {
    $staff = staffApiUser();
    $this->actingAs(staffApiAdmin(false))
        ->postJson(route('staff-management.enable-api', $staff))->assertForbidden();

    $this->actingAs(staffApiAdmin());
    $staff->update(['is_active' => false]);
    $this->postJson(route('staff-management.enable-api', $staff))->assertUnprocessable();

    $other = User::factory()->create();
    $other->assignRole(Role::findOrCreate('Controller', 'web'));
    $this->postJson(route('staff-management.enable-api', $other))->assertNotFound();
});
