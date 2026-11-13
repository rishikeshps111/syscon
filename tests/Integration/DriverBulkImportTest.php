<?php

namespace Tests\Integration;

use App\Http\Controllers\BulkImportController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverBulkImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::table('users', function (Blueprint $table) {
            $table->string('ref_code')->nullable();
        });
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            foreach (['aadhaar_number', 'license_number', 'badge_number', 'salary', 'uan'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        (require database_path('migrations/2026_11_13_000001_add_date_of_birth_to_driver_profiles_table.php'))->up();
        foreach (['staff_profiles', 'housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('father_name')->nullable();
                $table->string('uan')->nullable();
                $table->string('gross_salary')->nullable();
                $table->timestamps();
            });
        }
    }

    private function driver(string $ref = 'REF1', string $email = 'driver@example.com'): User
    {
        $user = User::factory()->create(['ref_code' => $ref, 'email' => $email, 'code' => User::count() === 0 ? 'DRV1' : 'DRV2']);
        $user->driverProfile()->create(['aadhaar_number' => $email, 'license_number' => $email, 'badge_number' => 'OLD', 'salary' => 25000]);
        return $user;
    }

    private function validateImport(array $rows): array
    {
        $controller = new BulkImportController;
        $config = (new \ReflectionMethod($controller, 'config'))->invoke($controller, 'drivers');
        return (new \ReflectionMethod($controller, 'validateRows'))->invoke($controller, 'drivers', $rows, $config);
    }

    public function test_driver_import_accepts_legacy_and_dob_spreadsheet_headers(): void
    {
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        $this->actingAs($this->driver());
        \Illuminate\Support\Facades\Gate::before(fn () => true);
        $controller = new BulkImportController;
        $config = (new \ReflectionMethod($controller, 'config'))->invoke($controller, 'drivers');
        $this->assertSame('date_of_birth', $config['headers'][array_key_last($config['headers'])]);
        $this->assertSame('15-06-1990', $config['sample'][array_key_last($config['sample'])]);
        foreach ([false, true] as $withDob) {
            $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
            $headers = $withDob ? $config['headers'] : array_values(array_diff($config['headers'], ['date_of_birth', 'uan']));
            $sample = array_map(fn ($header) => $config['sample'][array_search($header, $config['headers'], true)], $headers);
            $book->getActiveSheet()->fromArray([$headers, $sample]);
            $path = tempnam(sys_get_temp_dir(), 'driver-dob-');
            try {
                (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
                $file = new \Illuminate\Http\UploadedFile($path, 'drivers.xlsx', null, null, true);
                $request = \Illuminate\Http\Request::create('/', 'POST', ['import_step' => 'prepare'], [], ['csv_file' => $file]);
                $session = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
                $request->setLaravelSession($session);
                $response = $controller->import($request, 'drivers')->getData(true);
                $this->assertSame(1, $response['total']);
                $row = $session->get('bulk_import.'.$response['token'])['rows'][0]['data'];
                $this->assertSame($withDob ? '15-06-1990' : null, $row['date_of_birth'] ?? null);
            } finally {
                unlink($path);
                $book->disconnectWorksheets();
            }
        }
    }

    public function test_driver_dob_import_normalizes_dates_and_persists_updates(): void
    {
        $user = $this->driver();
        $controller = new BulkImportController;
        $normalize = new \ReflectionMethod($controller, 'normalize');
        $update = new \ReflectionMethod($controller, 'updateImportedDriver');
        foreach (['15-06-1990', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('1990-06-15'))] as $value) {
            $data = $normalize->invoke($controller, ['date_of_birth' => $value], 'drivers');
            $this->assertSame('1990-06-15', $data['date_of_birth']);
            $update->invoke($controller, $data + ['_driver_user_id' => $user->id]);
            $this->assertSame('1990-06-15', $user->driverProfile->fresh()->date_of_birth->format('Y-m-d'));
        }
        $update->invoke($controller, ['_driver_user_id' => $user->id, 'date_of_birth' => null]);
        $this->assertSame('1990-06-15', $user->driverProfile->fresh()->date_of_birth->format('Y-m-d'));
        foreach (['31-02-1990', 'invalid'] as $value) {
            [, $errors] = $this->validateImport([['line' => 2, 'data' => ['date_of_birth' => $value]]]);
            $this->assertStringContainsString('date_of_birth received', implode(' ', $errors));
        }
        foreach ([[], ['date_of_birth' => '']] as $data) {
            [, $errors] = $this->validateImport([['line' => 2, 'data' => $data]]);
            $this->assertStringNotContainsString('birth', implode(' ', $errors));
        }
    }

    public function test_driver_dob_form_validation_and_profile_creation_editing_and_clearing(): void
    {
        foreach ([new \App\Http\Requests\StoreDriverManagementRequest, new \App\Http\Requests\UpdateDriverManagementRequest] as $request) {
            $rules = $request->rules()['date_of_birth'];
            foreach ([null, '1990-06-15'] as $value) {
                $this->assertFalse(\Illuminate\Support\Facades\Validator::make(['date_of_birth' => $value], ['date_of_birth' => $rules])->fails());
            }
            $this->assertTrue(\Illuminate\Support\Facades\Validator::make(['date_of_birth' => 'invalid'], ['date_of_birth' => $rules])->fails());
        }
        Schema::create('salary_components', fn (Blueprint $table) => $table->id());
        $profileData = new \ReflectionMethod(\App\Http\Controllers\DriverManagementController::class, 'profileData');
        $controller = new \App\Http\Controllers\DriverManagementController;
        $user = User::factory()->create();
        $data = $profileData->invoke($controller, ['date_of_birth' => '1990-06-15']);
        $user->driverProfile()->create($data);
        $this->assertSame('1990-06-15', $user->driverProfile->fresh()->date_of_birth->format('Y-m-d'));
        foreach (['1991-07-16', null] as $value) {
            $user->driverProfile()->update($profileData->invoke($controller, ['date_of_birth' => $value]));
            $this->assertSame($value, $user->driverProfile->fresh()->date_of_birth?->format('Y-m-d'));
        }
    }

    public function test_import_uan_can_be_cleared_or_preserved_for_all_employee_roles(): void
    {
        $controller = new BulkImportController;
        foreach (['Driver', 'Staff', 'Housekeeping', 'Controller', 'Supervisor'] as $role) {
            $user = User::factory()->create();
            $relation = lcfirst($role).'Profile';
            $user->{$relation}()->create(['uan' => 'OLD-UAN']);
            $method = new \ReflectionMethod($controller, $role === 'Driver' ? 'updateImportedDriver' : 'updateImportedStaff');
            $identity = $role === 'Driver' ? ['_driver_user_id' => $user->id] : ['_staff_user_id' => $user->id, 'role' => $role];
            $method->invoke($controller, $identity);
            $this->assertSame('OLD-UAN', $user->{$relation}->fresh()->uan);
            $method->invoke($controller, $identity + ['uan' => '  ']);
            $this->assertNull($user->{$relation}->fresh()->uan);
            $method->invoke($controller, $identity + ['uan' => '  NEW-UAN  ']);
            $this->assertSame('NEW-UAN', $user->{$relation}->fresh()->uan);
        }
        $normalize = new \ReflectionMethod($controller, 'normalize');
        foreach (['drivers', 'staff', 'controllers', 'supervisors'] as $module) {
            $this->assertNull($normalize->invoke($controller, ['uan' => '  '], $module)['uan']);
            $this->assertSame('00123', $normalize->invoke($controller, ['uan' => ' 00123 '], $module)['uan']);
        }
    }

    public function test_matches_trimmed_ref_and_ignores_own_unique_values(): void
    {
        $user = $this->driver();
        [$rows, $errors] = $this->validateImport([['line' => 543, 'data' => ['ref_code' => ' REF1 ', 'email' => $user->email, 'aadhaar_number' => $user->email, 'license_number' => $user->email]]]);
        $this->assertSame($user->id, $rows[0]['_driver_user_id']);
        $this->assertStringNotContainsString('already been taken', implode(' ', $errors));
    }

    public function test_rejects_conflicts_duplicate_refs_and_ambiguous_matches(): void
    {
        $this->driver();
        $other = $this->driver('REF2', 'other@example.com');
        $row = ['ref_code' => 'REF1', 'email' => $other->email, 'aadhaar_number' => $other->email, 'license_number' => $other->email];
        [, $errors] = $this->validateImport([['line' => 543, 'data' => $row], ['line' => 544, 'data' => $row]]);
        $messages = implode(' ', $errors);
        foreach (['email has already been taken', 'aadhaar number has already been taken', 'license number has already been taken', 'duplicate ref_code'] as $message) {
            $this->assertStringContainsString($message, $messages);
        }
        $other->update(['ref_code' => 'REF1']);
        [, $errors] = $this->validateImport([['line' => 543, 'data' => $row]]);
        $this->assertStringContainsString('matches multiple drivers', implode(' ', $errors));
    }

    public function test_updates_preserve_identity_salary_blank_values_and_password(): void
    {
        $user = $this->driver();
        $password = $user->password;
        $method = new \ReflectionMethod(BulkImportController::class, 'updateImportedDriver');
        $controller = new BulkImportController;
        $method->invoke($controller, ['_driver_user_id' => $user->id, 'name' => 'Updated', 'email' => null, 'badge_number' => null, 'passcode' => null, 'is_active' => 0]);
        $user->refresh();
        $this->assertSame('Updated', $user->name);
        $this->assertSame('driver@example.com', $user->email);
        $this->assertSame('DRV1', $user->code);
        $this->assertFalse($user->is_active);
        $this->assertSame($password, $user->password);
        $this->assertEquals(25000, $user->driverProfile->salary);
        $this->assertSame('OLD', $user->driverProfile->badge_number);
        $method->invoke($controller, ['_driver_user_id' => $user->id, 'passcode' => '123456']);
        $this->assertTrue(Hash::check('123456', $user->fresh()->password));
        $this->assertSame(1, User::count());
    }

    public function test_new_and_blank_refs_do_not_select_existing_drivers(): void
    {
        $this->driver();
        [$rows] = $this->validateImport([['line' => 2, 'data' => ['ref_code' => 'NEW']], ['line' => 3, 'data' => ['ref_code' => '']]]);
        foreach ($rows as $row) {
            $this->assertArrayNotHasKey('_driver_user_id', $row);
        }
    }

    public function test_staff_matches_ref_and_ignores_own_email_and_phone(): void
    {
        $user = User::factory()->create(['ref_code' => 'STAFF1', 'phone' => '9876543210']);
        $user->staffProfile()->create(['father_name' => 'Original', 'gross_salary' => 40000]);
        $controller = new BulkImportController;
        $config = (new \ReflectionMethod($controller, 'config'))->invoke($controller, 'staff');
        $input = ['ref_code' => ' STAFF1 ', 'role' => 'Staff', 'email' => $user->email, 'phone' => '9876543210'];
        [$rows, $errors] = (new \ReflectionMethod($controller, 'validateRows'))->invoke($controller, 'staff', [['line' => 2, 'data' => $input]], $config);
        $this->assertSame($user->id, $rows[0]['_staff_user_id']);
        $this->assertStringNotContainsString('already been taken', implode(' ', $errors));
        (new \ReflectionMethod($controller, 'updateImportedStaff'))->invoke($controller, ['_staff_user_id' => $user->id, 'role' => 'Staff', 'name' => 'Changed', 'email' => null, 'father_name' => null, 'is_active' => 0]);
        $this->assertSame('Changed', $user->fresh()->name);
        $this->assertSame('Original', $user->staffProfile->fresh()->father_name);
        $this->assertEquals(40000, $user->staffProfile->fresh()->gross_salary);

        $other = User::factory()->create(['phone' => '9999999999']);
        $input['phone'] = $other->phone;
        $input['email'] = $other->email;
        $input['role'] = 'Controller';
        [, $errors] = (new \ReflectionMethod($controller, 'validateRows'))->invoke($controller, 'staff', [['line' => 2, 'data' => $input]], $config);
        $messages = implode(' ', $errors);
        $this->assertStringContainsString('phone has already been taken', $messages);
        $this->assertStringContainsString('email has already been taken', $messages);
        $this->assertStringContainsString('role does not match', $messages);
    }

    public function test_duplicate_tracking_survives_validation_chunks(): void
    {
        $this->driver();
        $controller = new BulkImportController;
        $config = (new \ReflectionMethod($controller, 'config'))->invoke($controller, 'drivers');
        $method = new \ReflectionMethod($controller, 'validateRows');
        $state = [];
        $method->invokeArgs($controller, ['drivers', [['line' => 2, 'data' => ['ref_code' => 'REF1']]], $config, &$state]);
        [, $errors] = $method->invokeArgs($controller, ['drivers', [['line' => 27, 'data' => ['ref_code' => 'REF1']]], $config, &$state]);
        $this->assertStringContainsString('Row 27: duplicate ref_code; first seen on row 2', implode(' ', $errors));
    }

    public function test_save_requests_process_only_25_rows_and_reject_stale_offsets(): void
    {
        $user = $this->driver();
        $session = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
        $token = (string) \Illuminate\Support\Str::uuid();
        $key = 'bulk_import.'.$token;
        $session->put($key, ['module' => 'drivers', 'phase' => 'save', 'expires' => time() + 60, 'rows' => array_fill(0, 26, []), 'valid' => array_fill(0, 26, ['_driver_user_id' => $user->id, 'name' => 'Batch update']), 'saved' => 0, 'updated' => 0]);
        $request = \Illuminate\Http\Request::create('/', 'POST', ['token' => $token, 'offset' => 0, 'import_step' => 'save']);
        $request->setLaravelSession($session);
        $method = new \ReflectionMethod(BulkImportController::class, 'processImportChunk');
        $response = $method->invoke(new BulkImportController, $request, 'drivers', []);
        $this->assertSame(25, $response->getData(true)['processed']);
        $this->assertSame(['total' => 26, 'validated' => 26, 'saved' => 25, 'created' => 0, 'updated' => 25], array_intersect_key($response->getData(true), array_flip(['total', 'validated', 'saved', 'created', 'updated'])));
        $this->assertSame(25, $session->get($key)['saved']);
        $this->assertSame('Batch update', $user->fresh()->name);
        try {
            $method->invoke(new BulkImportController, $request, 'drivers', []);
            $this->fail('Stale offset should be rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $request->merge(['offset' => 25]);
        $response = $method->invoke(new BulkImportController, $request, 'drivers', ['index_route' => 'driver-management.index']);
        $this->assertSame('done', $response->getData(true)['phase']);
        $this->assertFalse($session->has($key));
        $this->assertSame('Import completed: 0 rows created, 26 rows updated', $session->get('success'));
        $this->assertSame($session->get('success'), $response->getData(true)['message']);
        $this->assertSame(26, $response->getData(true)['total']);
        $this->assertSame(26, $response->getData(true)['validated']);
        $this->assertSame(26, $response->getData(true)['saved']);
        $this->assertSame(0, $response->getData(true)['created']);
        $this->assertSame(26, $response->getData(true)['updated']);
    }

    public function test_progress_counts_for_validation_creation_updates_and_mixed_imports(): void
    {
        $method = new \ReflectionMethod(BulkImportController::class, 'importProgress');
        foreach ([[25, 0, 0], [26, 26, 0], [26, 26, 26], [26, 26, 12], [26, 25, 10]] as [$validated, $saved, $updated]) {
            $result = $method->invoke(new BulkImportController, ['rows' => array_fill(0, 26, []), 'valid' => array_fill(0, $validated, []), 'saved' => $saved, 'updated' => $updated]);
            $this->assertSame(['total' => 26, 'validated' => $validated, 'saved' => $saved, 'created' => $saved - $updated, 'updated' => $updated], $result);
        }
    }

    public function test_validation_failure_discards_import_without_saving(): void
    {
        $user = $this->driver();
        $original = $user->name;
        $session = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
        $token = (string) \Illuminate\Support\Str::uuid();
        $key = 'bulk_import.'.$token;
        $session->put($key, ['module' => 'drivers', 'phase' => 'validate', 'expires' => time() + 60, 'rows' => [['line' => 2, 'data' => ['ref_code' => 'REF1', 'name' => 'Must not save']]], 'valid' => [], 'validation' => [], 'offset' => 0]);
        $request = \Illuminate\Http\Request::create('/', 'POST', ['token' => $token, 'offset' => 0, 'import_step' => 'validate']);
        $request->setLaravelSession($session);
        $controller = new BulkImportController;
        $config = (new \ReflectionMethod($controller, 'config'))->invoke($controller, 'drivers');
        try {
            (new \ReflectionMethod($controller, 'processImportChunk'))->invoke($controller, $request, 'drivers', $config);
            $this->fail('Invalid rows should be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('csv_file', $exception->errors());
        }
        $this->assertFalse($session->has($key));
        $this->assertSame($original, $user->fresh()->name);
    }
}
