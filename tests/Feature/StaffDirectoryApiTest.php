<?php

use App\Models\Depot;
use App\Models\BranchLocation;
use App\Models\DriverDocument;
use App\Models\Designation;
use App\Models\Department;
use App\Models\District;
use App\Models\GeneratedHrLetter;
use App\Models\HrmsDocumentType;
use App\Models\Level;
use App\Models\Location;
use App\Models\State;
use App\Models\StaffDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function directoryDepot(string $suffix): Depot
{
    $state = State::create(['name' => 'State '.$suffix]);
    $district = District::create(['state_id' => $state->id, 'name' => 'District '.$suffix, 'code' => 'D'.$suffix]);
    $location = Location::create(['state_id' => $state->id, 'district_id' => $district->id, 'name' => 'Location '.$suffix]);

    return Depot::create(['location_id' => $location->id, 'name' => 'Depot '.$suffix]);
}

function directoryDesignation(string $name): Designation
{
    $department = Department::create(['name' => 'Department '.$name]);
    $level = Level::create(['name' => 'Level '.$name]);
    $role = Role::findOrCreate('Staff', 'web');

    return Designation::create([
        'department_id' => $department->id,
        'level_id' => $level->id,
        'reporting_to' => $role->id,
        'name' => $name,
        'role_type' => 'Staff',
    ]);
}

function directoryStaff(Depot $depot, string $name, ?Designation $designation = null, bool $active = true): User
{
    $staff = User::factory()->create(['name' => $name, 'code' => 'MNGT'.str_replace(' ', '', $name), 'is_active' => $active]);
    $staff->assignRole(Role::findOrCreate('Staff', 'web'));
    $staff->staffProfile()->create([
        'depot_id' => $depot->id,
        'designation_id' => $designation?->id,
        'employment_type' => 'full_time',
        'date_of_joining' => '2026-01-10',
        'basic' => 25000,
        'bank_account_number' => '123456789',
        'aadhaar_number' => '123412341234',
    ]);

    return $staff;
}

function directoryToken(User $user, string $ability = 'type:staff'): string
{
    return $user->createToken('Staff App', [$ability])->plainTextToken;
}

it('lists and filters only Staff in the token holders depot', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $designation = directoryDesignation('Coordinator');
    $caller = directoryStaff($depot, 'Caller', $designation);
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $active = directoryStaff($depot, 'Active One', $designation);
    $inactive = directoryStaff($depot, 'Inactive One', $designation, false);
    directoryStaff($otherDepot, 'Other Depot', $designation);

    $this->withToken(directoryToken($caller))
        ->getJson('/api/v1/staff?per_page=2')
        ->assertOk()->assertJsonPath('meta.total', 3);

    $this->getJson('/api/v1/staff?search_text=Inactive&status=0&designation_id='.$designation->id.'&employment_type=full_time&date_of_joining=2026-01-10')
        ->assertOk()->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $inactive->id);

    $this->getJson('/api/v1/staff?search_text=Active%20One&status=1')
        ->assertOk()->assertJsonPath('data.0.id', $active->id);
});

it('returns only designations assigned to Staff in the callers depot', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $local = directoryDesignation('Local');
    $remote = directoryDesignation('Remote');
    $caller = directoryStaff($depot, 'Caller', $local);
    $caller->forceFill(['staff_api_enabled' => true])->save();
    directoryStaff($depot, 'Second Local', $local);
    directoryStaff($otherDepot, 'Remote Person', $remote);

    $this->withToken(directoryToken($caller))->getJson('/api/v1/staff/designations')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $local->id)
        ->assertJsonPath('data.0.name', 'Local');
});

it('restricts Staff details by depot and excludes private profile fields', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryStaff($depot, 'Local Person');
    $remote = directoryStaff($otherDepot, 'Remote Person');

    $this->withToken(directoryToken($caller))->getJson('/api/v1/staff/'.$local->id)
        ->assertOk()->assertJsonPath('data.id', $local->id)
        ->assertJsonMissingPath('data.profile.basic')
        ->assertJsonMissingPath('data.profile.bank_account_number')
        ->assertJsonMissingPath('data.profile.aadhaar_number')
        ->assertJsonMissingPath('data.documents');

    $this->getJson('/api/v1/staff/'.$remote->id)->assertNotFound();
});

it('returns empty lists without a depot and rejects invalid filters and non Staff tokens', function () {
    $caller = User::factory()->create(['is_active' => true, 'staff_api_enabled' => true]);
    $caller->assignRole(Role::findOrCreate('Staff', 'web'));
    $this->withToken(directoryToken($caller))->getJson('/api/v1/staff')->assertOk()->assertJsonPath('meta.total', 0);
    $this->getJson('/api/v1/staff/designations')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/staff?status=invalid')->assertUnprocessable()->assertJsonValidationErrors('status');

    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole(Role::findOrCreate('Controller', 'web'));
    $this->withToken(directoryToken($other, 'type:controller'))->getJson('/api/v1/staff')->assertForbidden();
});

it('lists and streams only documents belonging to accessible Staff', function () {
    Storage::fake('public');
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryStaff($depot, 'Local Person');
    $remote = directoryStaff($otherDepot, 'Remote Person');
    $type = HrmsDocumentType::create(['name' => 'Identity Card', 'applicable_for' => 'staff']);
    Storage::disk('public')->put('staff-documents/local.pdf', '%PDF-1.4 test');
    Storage::disk('public')->put('staff-documents/remote.pdf', '%PDF-1.4 remote');
    $document = StaffDocument::create(['user_id' => $local->id, 'hrms_document_type_id' => $type->id,
        'file_path' => 'staff-documents/local.pdf', 'original_name' => 'local.pdf', 'is_verified' => true]);
    $remoteDocument = StaffDocument::create(['user_id' => $remote->id, 'hrms_document_type_id' => $type->id,
        'file_path' => 'staff-documents/remote.pdf', 'original_name' => 'remote.pdf']);

    $this->withToken(directoryToken($caller))->getJson('/api/v1/staff/'.$local->id.'/documents')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.document_type', 'Identity Card')
        ->assertJsonMissingPath('data.0.file_path');
    $this->get('/api/v1/staff/'.$local->id.'/documents/'.$document->id.'/preview')
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get('/api/v1/staff/'.$local->id.'/documents/'.$document->id.'/download')
        ->assertOk()->assertDownload('local.pdf');
    $this->get('/api/v1/staff/'.$local->id.'/documents/'.$remoteDocument->id.'/preview')->assertNotFound();
    $this->getJson('/api/v1/staff/'.$remote->id.'/documents')->assertNotFound();
    Storage::disk('public')->delete($document->file_path);
    $this->get('/api/v1/staff/'.$local->id.'/documents/'.$document->id.'/preview')->assertNotFound();
});

it('lists saved letters and serves JSON and PDF only for accessible Staff', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryStaff($depot, 'Local Person');
    $remote = directoryStaff($otherDepot, 'Remote Person');
    $letter = GeneratedHrLetter::create(['user_id' => $local->id, 'letter_number' => 'HRL-LOCAL',
        'entity_type' => 'appointment_letter', 'language' => 'English', 'subject' => 'Welcome',
        'content' => '<p>Saved content</p>', 'generated_at' => now()]);
    $remoteLetter = GeneratedHrLetter::create(['user_id' => $remote->id, 'letter_number' => 'HRL-REMOTE',
        'entity_type' => 'appointment_letter', 'language' => 'English', 'subject' => 'Remote',
        'content' => '<p>Remote content</p>', 'generated_at' => now()]);

    $this->withToken(directoryToken($caller))->getJson('/api/v1/staff/'.$local->id.'/letters')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $letter->id)
        ->assertJsonMissingPath('data.0.content');
    $this->getJson('/api/v1/staff/'.$local->id.'/letters/'.$letter->id)
        ->assertOk()->assertJsonPath('data.content', '<p>Saved content</p>');
    $this->get('/api/v1/staff/'.$local->id.'/letters/'.$letter->id.'/preview')
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get('/api/v1/staff/'.$local->id.'/letters/'.$letter->id.'/download')
        ->assertOk()->assertHeader('content-disposition', 'attachment; filename="HRL-LOCAL.pdf"');
    $this->getJson('/api/v1/staff/'.$local->id.'/letters/'.$remoteLetter->id)->assertNotFound();
    $this->getJson('/api/v1/staff/'.$remote->id.'/letters')->assertNotFound();
});

function directoryDriver(Depot $depot, string $name, array $profile = [], bool $active = true): User
{
    $location = $depot->location;
    $branch = BranchLocation::create(['state_id' => $location->state_id,
        'district_id' => $location->district_id, 'location_id' => $location->id, 'name' => 'Branch '.$name]);
    $driver = User::factory()->create(['name' => $name, 'code' => 'DRV'.str_replace(' ', '', $name),
        'phone' => '98'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT), 'is_active' => $active]);
    $driver->assignRole(Role::findOrCreate('Driver', 'web'));
    $driver->driverProfile()->create(array_merge([
        'aadhaar_number' => 'AAD'.$driver->id,
        'state_id' => $location->state_id,
        'district_id' => $location->district_id,
        'location_id' => $location->id,
        'pincode' => '123456', 'address' => 'Test address',
        'license_number' => 'LIC'.$driver->id,
        'license_type' => 'lmv',
        'issue_date' => '2025-01-01',
        'expiry_date' => today()->addDays(10)->toDateString(),
        'employment_type' => 'permanent',
        'joining_date' => '2025-01-01',
        'salary' => 25000,
        'depot_id' => $depot->id,
        'branch_location_id' => $branch->id,
        'account_number' => '1234567890',
        'ifsc_code' => 'BANK0001',
        'emergency_contact_name' => 'Contact',
        'emergency_contact_no' => '9876543210',
        'medical_fitness_expiry' => today()->addDays(10)->toDateString(),
        'verification_status' => 'verified',
    ], $profile));

    return $driver;
}

it('lists only depot Drivers and applies the admin search and status filters', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryDriver($depot, 'Local Driver', [], false);
    directoryDriver($otherDepot, 'Remote Driver');

    $this->withToken(directoryToken($caller))
        ->getJson('/api/v1/drivers?search_text=Local&status=0&employment_type=permanent&license_type=lmv&verification_status=verified&expiry_filter=license_expiring')
        ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $local->id);
    $this->getJson('/api/v1/drivers/filter-options')
        ->assertOk()->assertJsonPath('data.license_type.lmv', 'LMV');
    $this->getJson('/api/v1/drivers?expiry_filter=invalid')
        ->assertUnprocessable()->assertJsonValidationErrors('expiry_filter');
    $this->getJson('/api/v1/drivers/'.$local->id)
        ->assertOk()->assertJsonMissingPath('data.profile.salary')
        ->assertJsonMissingPath('data.profile.account_number')
        ->assertJsonMissingPath('data.profile.aadhaar_number');
});

it('scopes Driver details and QR to the Staff callers depot', function () {
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryDriver($depot, 'Local Driver');
    $remote = directoryDriver($otherDepot, 'Remote Driver');

    $this->withToken(directoryToken($caller))->getJson('/api/v1/drivers/'.$local->id.'/qr')
        ->assertOk()->assertJsonPath('code', $local->code)
        ->assertJsonPath('success', true);
    $this->get('/api/v1/drivers/'.$local->id.'/qr/download')
        ->assertOk()->assertHeader('content-type', 'image/svg+xml')
        ->assertHeader('content-disposition', 'attachment; filename="'.$local->code.'-qr.svg"');
    $this->getJson('/api/v1/drivers/'.$remote->id)->assertNotFound();
    $this->getJson('/api/v1/drivers/'.$remote->id.'/qr')->assertNotFound();
    $local->update(['code' => null]);
    $this->getJson('/api/v1/drivers/'.$local->id.'/qr')->assertUnprocessable();
});

it('scopes Driver documents and letters including binary responses', function () {
    Storage::fake('public');
    $depot = directoryDepot('A');
    $otherDepot = directoryDepot('B');
    $caller = directoryStaff($depot, 'Caller');
    $caller->forceFill(['staff_api_enabled' => true])->save();
    $local = directoryDriver($depot, 'Local Driver');
    $remote = directoryDriver($otherDepot, 'Remote Driver');
    $type = HrmsDocumentType::create(['name' => 'License', 'applicable_for' => 'driver']);
    Storage::disk('public')->put('driver-documents/local.pdf', '%PDF-1.4 test');
    $document = DriverDocument::create(['user_id' => $local->id, 'hrms_document_type_id' => $type->id,
        'file_path' => 'driver-documents/local.pdf', 'original_name' => 'license.pdf']);
    $remoteDocument = DriverDocument::create(['user_id' => $remote->id, 'hrms_document_type_id' => $type->id,
        'file_path' => 'driver-documents/remote.pdf', 'original_name' => 'remote.pdf']);
    $letter = GeneratedHrLetter::create(['user_id' => $local->id, 'letter_number' => 'HRL-DRIVER',
        'entity_type' => 'appointment_letter', 'language' => 'English', 'subject' => 'Welcome',
        'content' => '<p>Driver letter</p>', 'generated_at' => now()]);
    $remoteLetter = GeneratedHrLetter::create(['user_id' => $remote->id, 'letter_number' => 'HRL-REMOTE-DRIVER',
        'entity_type' => 'appointment_letter', 'language' => 'English', 'subject' => 'Remote',
        'content' => '<p>Remote letter</p>', 'generated_at' => now()]);

    $this->withToken(directoryToken($caller))->getJson('/api/v1/drivers/'.$local->id.'/documents')
        ->assertOk()->assertJsonPath('data.0.id', $document->id);
    $this->get('/api/v1/drivers/'.$local->id.'/documents/'.$document->id.'/preview')->assertOk();
    $this->get('/api/v1/drivers/'.$local->id.'/documents/'.$document->id.'/download')->assertDownload('license.pdf');
    $this->get('/api/v1/drivers/'.$local->id.'/documents/'.$remoteDocument->id.'/preview')->assertNotFound();
    $this->getJson('/api/v1/drivers/'.$local->id.'/letters')
        ->assertOk()->assertJsonPath('data.0.id', $letter->id);
    $this->getJson('/api/v1/drivers/'.$local->id.'/letters/'.$letter->id)
        ->assertOk()->assertJsonPath('data.content', '<p>Driver letter</p>');
    $this->get('/api/v1/drivers/'.$local->id.'/letters/'.$letter->id.'/preview')->assertOk();
    $this->get('/api/v1/drivers/'.$local->id.'/letters/'.$letter->id.'/download')->assertOk();
    $this->getJson('/api/v1/drivers/'.$local->id.'/letters/'.$remoteLetter->id)->assertNotFound();
    $this->getJson('/api/v1/drivers/'.$remote->id.'/documents')->assertNotFound();
    $this->getJson('/api/v1/drivers/'.$remote->id.'/letters')->assertNotFound();
    $this->getJson('/api/v1/drivers/'.$remote->id.'/letters/'.$letter->id)->assertNotFound();
    Storage::disk('public')->delete($document->file_path);
    $this->get('/api/v1/drivers/'.$local->id.'/documents/'.$document->id.'/preview')->assertNotFound();
});

it('returns no Drivers without a depot and rejects non Staff tokens', function () {
    $caller = User::factory()->create(['is_active' => true, 'staff_api_enabled' => true]);
    $caller->assignRole(Role::findOrCreate('Staff', 'web'));
    $this->withToken(directoryToken($caller))->getJson('/api/v1/drivers')
        ->assertOk()->assertJsonPath('meta.total', 0);

    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole(Role::findOrCreate('Controller', 'web'));
    $this->withToken(directoryToken($other, 'type:controller'))
        ->getJson('/api/v1/drivers')->assertForbidden();
});
