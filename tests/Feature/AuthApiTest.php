<?php

use App\Http\Resources\Api\V1\UserResource;
use App\Models\Depot;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\HrmsDocumentType;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

function apiRole(string $name): Role
{
    return Role::findOrCreate($name, 'web');
}

it('logs in an app user with code in the phone field, passcode and type', function () {
    $user = User::factory()->create([
        'code' => 'DRV001',
        'password' => '123456',
        'is_active' => true,
    ]);
    $user->assignRole(apiRole('Driver'));

    $response = $this->postJson('/api/v1/login', [
        'phone' => 'DRV001',
        'passcode' => '123456',
        'type' => 'driver',
        'device_name' => 'Android',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.code', 'DRV001')
        ->assertJsonPath('data.user.type', 'driver')
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'user' => ['id', 'code', 'name', 'type', 'roles', 'profile'],
            ],
        ]);

    expect($user->tokens()->count())->toBe(1);
});

it('rejects login when the requested type does not match the role', function () {
    $user = User::factory()->create([
        'code' => 'SUP001',
        'password' => '123456',
        'is_active' => true,
    ]);
    $user->assignRole(apiRole('Supervisor'));
    apiRole('Driver');

    $this->postJson('/api/v1/login', [
        'phone' => 'SUP001',
        'passcode' => '123456',
        'type' => 'driver',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
});

it('deactivates an app user after three wrong passcode attempts', function () {
    $user = User::factory()->create([
        'code' => 'DRV002',
        'phone' => '9876543212',
        'password' => '123456',
        'is_active' => true,
    ]);
    $user->assignRole(apiRole('Driver'));

    foreach (range(1, 2) as $attempt) {
        $this->postJson('/api/v1/login', [
            'phone' => 'DRV002',
            'passcode' => '000000',
            'type' => 'driver',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('passcode');

        $user->refresh();

        expect($user->failed_login_attempts)->toBe($attempt)
            ->and($user->is_active)->toBeTrue();
    }

    $this->postJson('/api/v1/login', [
        'phone' => 'DRV002',
        'passcode' => '000000',
        'type' => 'driver',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('passcode')
        ->assertJsonPath('errors.passcode.0', '3 attempts already done with wrong passcode, so your account has been blocked.');

    $user->refresh();

    expect($user->failed_login_attempts)->toBe(3)
        ->and($user->is_active)->toBeFalse();
});

it('resets failed passcode attempts after successful api login', function () {
    $user = User::factory()->create([
        'code' => 'DRV003',
        'password' => '123456',
        'is_active' => true,
        'failed_login_attempts' => 2,
    ]);
    $user->assignRole(apiRole('Driver'));

    $this->postJson('/api/v1/login', [
        'phone' => 'DRV003',
        'passcode' => '123456',
        'type' => 'driver',
    ])->assertOk();

    expect($user->refresh()->failed_login_attempts)->toBe(0);
});

it('logs out the current API token', function () {
    $user = User::factory()->create([
        'code' => 'CTL001',
        'password' => 'secret-password',
        'is_active' => true,
    ]);
    $user->assignRole(apiRole('Controller'));

    $token = $user->createToken('Android', ['type:controller'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->tokens()->count())->toBe(0);
});

it('formats profile depot document and employment fields for the API user resource', function () {
    $user = new User([
        'id' => 10,
        'code' => 'DRV001',
        'name' => 'Driver One',
        'email' => 'driver@example.com',
        'is_active' => true,
    ]);
    $user->setAttribute('api_user_type', 'driver');
    $user->setRelation('roles', collect([new Role(['name' => 'Driver'])]));

    $depot = new Depot([
        'id' => 20,
        'code' => 'DPT001',
        'name' => 'Central Depot',
        'short_name' => 'Central',
        'is_active' => true,
    ]);

    $profile = new DriverProfile();
    $profile->forceFill([
        'date_of_birth' => '2034-04-12',
        'joining_date' => '2034-04-12',
        'employment_type' => 'full_time',
    ]);
    $profile->setRelation('depot', $depot);

    $document = function (int $id, string $name, string $code, string $path): DriverDocument {
        $document = new DriverDocument();
        $document->forceFill([
            'id' => $id,
            'hrms_document_type_id' => $id + 10,
            'file_path' => $path,
            'original_name' => basename($path),
            'is_verified' => true,
            'expiry_date' => '2035-04-12',
        ]);
        $document->setRelation('documentType', new HrmsDocumentType([
            'id' => $id + 10,
            'name' => $name,
            'code' => $code,
        ]));

        return $document;
    };

    $user->setRelation('driverProfile', $profile);
    $user->setRelation('controllerProfile', null);
    $user->setRelation('supervisorProfile', null);
    $user->setRelation('driverDocuments', new Collection([
        $document(30, 'Aadhaar Card', 'AADHAAR', 'driver-documents/10/aadhaar.pdf'),
        $document(31, 'Driving Licence', 'LICENCE', 'driver-documents/10/licence.pdf'),
        $document(32, 'Badge', 'BADGE', 'driver-documents/10/badge.pdf'),
        $document(33, 'Medical Certificate', 'MEDICAL', 'driver-documents/10/medical.pdf'),
    ]));
    $user->setRelation('controllerDocuments', new Collection());
    $user->setRelation('supervisorDocuments', new Collection());

    $payload = (new UserResource($user))->resolve(request());

    expect($payload['profile']['date_of_birth'])->toBe('12 Apr 2034')
        ->and($payload['profile']['joining_date'])->toBe('12 Apr 2034')
        ->and($payload['profile']['employment_type'])->toBe('Full Time')
        ->and($payload['profile']['current_depot']['name'])->toBe('Central Depot')
        ->and($payload['profile']['aadhaar_file_url'])->toContain('/storage/driver-documents/10/aadhaar.pdf')
        ->and($payload['profile']['aadhaar_document']['expiry_date'])->toBe('12 Apr 2035')
        ->and($payload['profile']['licence_file_url'])->toContain('/storage/driver-documents/10/licence.pdf')
        ->and($payload['profile']['licence_document']['document_type_name'])->toBe('Driving Licence')
        ->and($payload['profile']['badge_file_url'])->toContain('/storage/driver-documents/10/badge.pdf')
        ->and($payload['profile']['badge_document']['document_type_name'])->toBe('Badge')
        ->and($payload['profile']['medical_certificate_file_url'])->toContain('/storage/driver-documents/10/medical.pdf')
        ->and($payload['profile']['medical_certificate_document']['document_type_name'])->toBe('Medical Certificate');
});
