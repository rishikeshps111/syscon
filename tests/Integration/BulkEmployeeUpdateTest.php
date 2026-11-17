<?php

namespace Tests\Integration;

use App\Http\Middleware\TrackStaffUserLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkEmployeeUpdateTest extends TestCase
{
    private User $operator;
    private array $employees = [];
    private const MODULES = ['Staff' => 'staff', 'Driver' => 'drivers', 'Housekeeping' => 'housekeeping', 'Controller' => 'controllers', 'Supervisor' => 'supervisors'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        Schema::table('users', fn (Blueprint $table) => $table->string('ref_code')->nullable());
        foreach (['states', 'districts', 'locations', 'depots', 'branch_locations', 'designations'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('state_id')->nullable(); $table->unsignedBigInteger('district_id')->nullable();
                $table->string('role_type')->nullable();
            });
        }
        DB::table('states')->insert([['id' => 1, 'name' => 'State A'], ['id' => 2, 'name' => 'State B']]);
        DB::table('districts')->insert(['id' => 1, 'name' => 'District A', 'state_id' => 1]);
        DB::table('locations')->insert(['id' => 1, 'name' => 'Location A', 'state_id' => 1, 'district_id' => 1]);
        foreach (['depots', 'branch_locations'] as $table) DB::table($table)->insert(['id' => 1, 'name' => 'Main']);
        $this->operator = User::factory()->create(['name' => 'Operator']);
        foreach (self::MODULES as $role => $module) {
            $class = 'App\\Models\\'.$role.'Profile';
            $profile = new $class;
            Schema::create($profile->getTable(), function (Blueprint $table) use ($profile) {
                $table->id();
                foreach ($profile->getFillable() as $field) {
                    if (str_ends_with($field, '_id') || $field === 'reporting_to') $table->unsignedBigInteger($field)->nullable();
                    else $table->string($field)->nullable();
                }
                $table->timestamps();
            });
            $employee = User::factory()->create(['name' => $role.' Employee', 'ref_code' => '000'.(count($this->employees) + 1), 'phone' => '987654321'.count($this->employees), 'country_code' => '+91']);
            $employee->assignRole(Role::create(['name' => $role, 'guard_name' => 'web']));
            $designation = DB::table('designations')->insertGetId(['name' => $role.' Designation', 'role_type' => $role]);
            $bank = in_array($role, ['Driver', 'Housekeeping'], true) ? 'account_number' : 'bank_account_number';
            $employee->{lcfirst($role).'Profile'}()->create([
                'depot_id' => 1, 'designation_id' => $designation, 'state_id' => 1, 'district_id' => 1,
                'location_id' => 1, 'uan' => '000111', $bank => '000123', 'ifsc_code' => 'OLD0001234',
            ] + ($role === 'Driver' ? ['salary' => 25000, 'license_number' => 'OLD-LICENCE', 'issue_date' => '2020-01-01', 'expiry_date' => '2030-01-01'] : ['gross_salary' => 25000]));
            $this->employees[$role] = $employee;
            $permission = Permission::create(['name' => lcfirst($role).'-management.edit', 'guard_name' => 'web', 'group_name' => 'HRMS']);
            $this->operator->givePermissionTo($permission);
        }
        $this->withoutMiddleware(TrackStaffUserLog::class);
        $this->actingAs($this->operator);
    }

    private function workbook(array $headers, array $rows): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        foreach ([$headers, ...$rows] as $r => $values) {
            foreach ($values as $c => $value) $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
        }
        $temporary = tmpfile();
        try {
            $path = stream_get_meta_data($temporary)['uri'];
            IOFactory::createWriter($book, 'Xlsx')->save($path);
            return file_get_contents($path);
        } finally {
            fclose($temporary); $book->disconnectWorksheets();
        }
    }

    private function prepare(string $module, array $headers, array $rows)
    {
        return $this->post('/bulk-update/'.$module, ['import_step' => 'prepare',
            'csv_file' => UploadedFile::fake()->createWithContent('update.xlsx', $this->workbook($headers, $rows))], ['Accept' => 'application/json']);
    }

    private function step(string $module, string $token, string $phase, int $offset = 0)
    {
        return $this->postJson('/bulk-update/'.$module, ['token' => $token, 'import_step' => $phase, 'offset' => $offset]);
    }

    private function updateFile(string $module, array $headers, array $rows): array
    {
        $token = $this->prepare($module, $headers, $rows)->assertOk()->json('token');
        $this->step($module, $token, 'validate')->assertOk()->assertJsonPath('phase', 'save');
        return $this->step($module, $token, 'save')->assertOk()->assertJsonPath('phase', 'done')->assertJsonPath('created', 0)->json();
    }

    private function sample(string $module, array $query = []): Spreadsheet
    {
        $bytes = $this->get('/bulk-update/'.$module.'/sample?'.http_build_query($query))->assertOk()->streamedContent();
        $temporary = tmpfile();
        try {
            $path = stream_get_meta_data($temporary)['uri']; file_put_contents($path, $bytes);
            return IOFactory::load($path);
        } finally { fclose($temporary); }
    }

    public function test_samples_and_forms_cover_all_modules_with_text_identity_and_blank_update_cells(): void
    {
        foreach (self::MODULES as $role => $module) {
            $this->get('/bulk-update/'.$module)->assertOk()->assertSee('Download Update Sample')->assertSee('Bulk Update')->assertDontSee('default login password');
            $book = $this->sample($module);
            try {
                $sheet = $book->getActiveSheet();
                $this->assertSame('Ref Code', $sheet->getCell('A1')->getValue());
                $this->assertSame('Name', $sheet->getCell('B1')->getValue());
                $this->assertSame($module === 'staff' ? 5 : 2, $sheet->getHighestDataRow());
                $found = false;
                $refs = [];
                for ($r = 2; $r <= $sheet->getHighestDataRow(); $r++) {
                    $refs[] = $sheet->getCell('A'.$r)->getValue();
                    if ($sheet->getCell('A'.$r)->getValue() === $this->employees[$role]->ref_code) {
                        $found = true;
                        $this->assertSame($role.' Employee', $sheet->getCell('B'.$r)->getValue());
                        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A'.$r)->getDataType());
                    }
                    for ($c = 3; $c <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()); $c++) {
                        $this->assertNull($sheet->getCell([$c, $r])->getValue());
                    }
                }
                $this->assertTrue($found, $role.' must be present in its module sample');
                $expected = $module === 'staff'
                    ? array_map(fn ($r) => $this->employees[$r]->ref_code, ['Staff', 'Housekeeping', 'Controller', 'Supervisor'])
                    : [$this->employees[$role]->ref_code];
                sort($refs); sort($expected);
                $this->assertSame($expected, $refs);
            } finally { $book->disconnectWorksheets(); }
        }
    }

    public function test_sparse_updates_cover_every_role_and_preserve_blanks_credentials_roles_and_salary(): void
    {
        $beforeCount = User::count();
        foreach (self::MODULES as $role => $module) {
            $employee = $this->employees[$role];
            $original = $employee->getAttributes();
            $roles = $employee->roles->pluck('name')->all();
            $designation = DB::table('designations')->insertGetId(['name' => $role.' New Designation', 'role_type' => $role]);
            $result = $this->updateFile($module, ['Ref Code', 'Name', 'Account Number', 'IFSC Code', 'UAN', 'Is Active', 'Designation'],
                [[$employee->ref_code, 'Updated '.$role, '000999', 'NEW0001234', '  ', '0', $role.' New Designation']]);
            $this->assertSame(1, $result['updated']);
            $employee->refresh();
            $profile = $employee->{lcfirst($role).'Profile'};
            $this->assertSame('Updated '.$role, $employee->name);
            $this->assertFalse($employee->is_active);
            $this->assertSame($original['password'], $employee->password);
            $this->assertSame($original['phone'], $employee->phone);
            $this->assertSame($roles, $employee->roles->pluck('name')->all());
            $this->assertSame('000111', $profile->uan);
            $this->assertSame('000999', $profile->{in_array($role, ['Driver', 'Housekeeping'], true) ? 'account_number' : 'bank_account_number'});
            $this->assertSame('NEW0001234', $profile->ifsc_code);
            $this->assertEquals($designation, $profile->designation_id);
            $this->assertEquals(25000, $profile->{$role === 'Driver' ? 'salary' : 'gross_salary'});
        }
        $this->assertSame($beforeCount, User::count());
    }

    public function test_downloaded_sample_round_trips_with_unchanged_rows_and_can_update_bank_details(): void
    {
        $book = $this->sample('drivers');
        $sheet = $book->getActiveSheet();
        $headers = $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().'1')[0];
        $row = $sheet->rangeToArray('A2:'.$sheet->getHighestDataColumn().'2')[0];
        $result = $this->updateFile('drivers', $headers, [$row]);
        $this->assertSame(0, $result['updated']); $this->assertSame(1, $result['unchanged']);
        $row[array_search('Account Number', $headers, true)] = '000567';
        $result = $this->updateFile('drivers', $headers, [$row]);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('000567', $this->employees['Driver']->driverProfile->fresh()->account_number);
        $book->disconnectWorksheets();
    }

    public function test_unknown_missing_duplicate_and_wrong_module_references_save_nothing(): void
    {
        $driver = $this->employees['Driver'];
        foreach ([[''], ['UNKNOWN'], [$this->employees['Staff']->ref_code], [$driver->ref_code, $driver->ref_code]] as $refs) {
            $token = $this->prepare('drivers', ['Ref Code', 'Name'], array_map(fn ($ref) => [$ref, 'Changed'], $refs))->assertOk()->json('token');
            $this->step('drivers', $token, 'validate')->assertUnprocessable()->assertJsonValidationErrors('csv_file');
            $this->assertSame('Driver Employee', $driver->fresh()->name);
        }
        $other = User::factory()->create(['ref_code' => $driver->ref_code]);
        $other->assignRole('Driver'); $other->driverProfile()->create();
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], [[$driver->ref_code, 'Changed']])->json('token');
        $this->step('drivers', $token, 'validate')->assertUnprocessable()->assertSee('ambiguous');
    }

    public function test_excluded_unknown_and_duplicate_columns_are_rejected(): void
    {
        foreach (['password', 'passcode', 'role', 'salary', 'salary_components', 'avatar', 'id', 'unknown'] as $column) {
            $this->prepare('drivers', ['Ref Code', $column], [[$this->employees['Driver']->ref_code, 'value']])
                ->assertUnprocessable()->assertJsonValidationErrors('csv_file');
        }
        $this->prepare('drivers', ['Ref Code', 'Name', 'Name'], [['0002', 'First', 'Second']])->assertUnprocessable();
        $this->prepare('drivers', ['Name'], [['Employee']])->assertUnprocessable();
        $this->prepare('drivers', ['Ref Code'], [])->assertUnprocessable();
    }

    public function test_invalid_lookup_uniqueness_and_dependent_values_save_nothing(): void
    {
        $driver = $this->employees['Driver'];
        foreach ([['Designation', 'Staff Designation'], ['State', 'State B'], ['IFSC Code', str_repeat('x', 21)],
            ['Expiry Date', '01-01-2010'], ['Date Of Birth', '31-02-1990'], ['Email', $this->employees['Staff']->email], ['Depot', 'Missing Depot']] as [$field, $value]) {
            $token = $this->prepare('drivers', ['Ref Code', 'Name', $field], [[$driver->ref_code, 'Changed', $value]])->assertOk()->json('token');
            $this->step('drivers', $token, 'validate')->assertUnprocessable()->assertJsonValidationErrors('csv_file');
            $this->assertSame('Driver Employee', $driver->fresh()->name);
        }
        DB::table('depots')->insert(['name' => 'Main']);
        $token = $this->prepare('drivers', ['Ref Code', 'Depot'], [[$driver->ref_code, 'Main']])->json('token');
        $this->step('drivers', $token, 'validate')->assertUnprocessable()->assertSee('ambiguous');
    }

    public function test_driver_specific_fields_dates_and_leading_zero_identifiers_update(): void
    {
        $driver = $this->employees['Driver'];
        $this->updateFile('drivers', ['Ref Code', 'License Number', 'Date Of Birth', 'Badge Number', 'Expiry Date'],
            [[$driver->ref_code, '000123LICENSE', '15-06-1990', '000BADGE', '01-01-2035']]);
        $profile = $driver->driverProfile->fresh();
        $this->assertSame('000123LICENSE', $profile->license_number);
        $this->assertSame('000BADGE', $profile->badge_number);
        $this->assertSame('1990-06-15', $profile->date_of_birth->format('Y-m-d'));
        $this->assertSame('2035-01-01', $profile->expiry_date->format('Y-m-d'));
    }

    public function test_edit_permissions_scope_forms_downloads_and_update_targets(): void
    {
        foreach (['Staff', 'Driver', 'Housekeeping', 'Supervisor'] as $role) $this->operator->revokePermissionTo(lcfirst($role).'-management.edit');
        $this->get('/bulk-update/drivers')->assertForbidden();
        $this->get('/bulk-update/drivers/sample')->assertForbidden();
        $this->prepare('drivers', ['Ref Code'], [['0002']])->assertForbidden();
        $book = $this->sample('staff');
        $this->assertSame(2, $book->getActiveSheet()->getHighestDataRow());
        $this->assertSame($this->employees['Controller']->ref_code, $book->getActiveSheet()->getCell('A2')->getValue());
        $book->disconnectWorksheets();
        $token = $this->prepare('staff', ['Ref Code', 'Name'], [[$this->employees['Staff']->ref_code, 'Forbidden']])->json('token');
        $this->step('staff', $token, 'validate')->assertUnprocessable();
        $this->assertSame('Staff Employee', $this->employees['Staff']->fresh()->name);
    }

    public function test_chunk_offsets_expiry_cross_module_and_full_file_validation(): void
    {
        $driver = $this->employees['Driver'];
        $rows = [];
        for ($i = 0; $i < 26; $i++) {
            $user = User::factory()->create(['ref_code' => 'BATCH'.$i, 'name' => 'Before '.$i]);
            $user->assignRole('Driver'); $user->driverProfile()->create();
            $rows[] = [$user->ref_code, 'After '.$i];
        }
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], $rows)->json('token');
        $this->step('drivers', $token, 'save')->assertConflict();
        $this->step('staff', $token, 'validate')->assertStatus(410);
        $this->step('drivers', $token, 'validate')->assertOk()->assertJsonPath('processed', 25)->assertJsonPath('phase', 'validate');
        $this->assertSame('Before 0', User::where('ref_code', 'BATCH0')->value('name'));
        $this->step('drivers', $token, 'validate')->assertConflict();
        $this->step('drivers', $token, 'validate', 25)->assertOk()->assertJsonPath('phase', 'save');
        $this->step('drivers', $token, 'save')->assertOk()->assertJsonPath('updated', 25);
        $this->step('drivers', $token, 'save')->assertConflict();
        $this->step('drivers', $token, 'save', 25)->assertOk()->assertJsonPath('phase', 'done')->assertJsonPath('updated', 26);
        $this->step('drivers', $token, 'save', 25)->assertStatus(410);
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], [[$driver->ref_code, 'Changed']])->json('token');
        $state = session('bulk_update.'.$token); $state['expires'] = time() - 1;
        $this->withSession(['bulk_update.'.$token => $state]);
        $this->step('drivers', $token, 'validate')->assertStatus(410);
        $this->assertSame('Driver Employee', $driver->fresh()->name);
    }

    public function test_late_validation_failure_and_revalidation_before_save_preserve_data(): void
    {
        $rows = [];
        for ($i = 0; $i < 25; $i++) {
            $user = User::factory()->create(['ref_code' => 'VALID'.$i, 'name' => 'Before']);
            $user->assignRole('Driver'); $user->driverProfile()->create();
            $rows[] = [$user->ref_code, 'After'];
        }
        $rows[] = ['UNKNOWN', 'After'];
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], $rows)->json('token');
        $this->step('drivers', $token, 'validate')->assertOk();
        $this->step('drivers', $token, 'validate', 25)->assertUnprocessable()->assertSee('Row 27');
        $this->assertSame(25, User::where('name', 'Before')->count());

        $driver = $this->employees['Driver'];
        $token = $this->prepare('drivers', ['Ref Code', 'Email'], [[$driver->ref_code, 'new@example.com']])->json('token');
        $this->step('drivers', $token, 'validate')->assertOk();
        User::factory()->create(['email' => 'new@example.com']);
        $this->step('drivers', $token, 'save')->assertUnprocessable();
        $this->assertSame($driver->email, $driver->fresh()->email);
    }

    public function test_failed_save_batch_rolls_back_and_reupload_preserves_completed_batches(): void
    {
        $rows = [];
        for ($i = 0; $i < 27; $i++) {
            $user = User::factory()->create(['ref_code' => 'RETRY'.$i, 'name' => 'Before '.$i]);
            $user->assignRole('Driver'); $user->driverProfile()->create();
            $rows[] = [$user->ref_code, 'After '.$i];
        }
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], $rows)->json('token');
        $this->step('drivers', $token, 'validate')->assertOk();
        $this->step('drivers', $token, 'validate', 25)->assertOk();
        $this->step('drivers', $token, 'save')->assertOk()->assertJsonPath('updated', 25);
        User::updating(function ($user) {
            if ($user->ref_code === 'RETRY26') throw new \RuntimeException('Simulated batch failure');
        });
        try {
            $this->step('drivers', $token, 'save', 25)->assertStatus(500);
        } finally { User::flushEventListeners(); }
        $this->assertSame('After 0', User::where('ref_code', 'RETRY0')->value('name'));
        $this->assertSame('Before 25', User::where('ref_code', 'RETRY25')->value('name'));
        $this->assertSame('Before 26', User::where('ref_code', 'RETRY26')->value('name'));
        $this->assertSame(25, session('bulk_update.'.$token.'.saved'));
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], $rows)->json('token');
        $this->step('drivers', $token, 'validate')->assertOk();
        $this->step('drivers', $token, 'validate', 25)->assertOk();
        $this->step('drivers', $token, 'save')->assertOk()->assertJsonPath('unchanged', 25);
        $this->step('drivers', $token, 'save', 25)->assertOk()->assertJsonPath('phase', 'done')->assertJsonPath('updated', 2)->assertJsonPath('unchanged', 25);
    }

    public function test_ref_reassignment_between_validation_and_save_is_rejected(): void
    {
        $driver = $this->employees['Driver'];
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], [[$driver->ref_code, 'Wrong Employee']])->json('token');
        $this->step('drivers', $token, 'validate')->assertOk();
        $driver->update(['ref_code' => 'REASSIGNED']);
        $other = User::factory()->create(['ref_code' => '0002', 'name' => 'Other Driver']);
        $other->assignRole('Driver'); $other->driverProfile()->create();
        $this->step('drivers', $token, 'save')->assertUnprocessable()->assertSee('matching changed');
        $this->assertSame('Other Driver', $other->fresh()->name);
    }

    public function test_formatted_numeric_identifiers_and_excel_dates_are_preserved(): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(['Ref Code', 'Account Number', 'Date Of Birth'], null, 'A1');
        $sheet->setCellValue('A2', 2)->getStyle('A2')->getNumberFormat()->setFormatCode('0000');
        $sheet->setCellValue('B2', 987)->getStyle('B2')->getNumberFormat()->setFormatCode('000000');
        $sheet->setCellValue('C2', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('1990-06-15')))
            ->getStyle('C2')->getNumberFormat()->setFormatCode('mm/dd/yyyy');
        $temporary = tmpfile();
        try {
            $path = stream_get_meta_data($temporary)['uri']; IOFactory::createWriter($book, 'Xlsx')->save($path);
            $token = $this->post('/bulk-update/drivers', ['import_step' => 'prepare',
                'csv_file' => UploadedFile::fake()->createWithContent('formatted.xlsx', file_get_contents($path))], ['Accept' => 'application/json'])
                ->assertOk()->json('token');
            $this->step('drivers', $token, 'validate')->assertOk();
            $this->step('drivers', $token, 'save')->assertOk()->assertJsonPath('updated', 1);
            $profile = $this->employees['Driver']->driverProfile->fresh();
            $this->assertSame('000987', $profile->account_number);
            $this->assertSame('1990-06-15', $profile->date_of_birth->format('Y-m-d'));
        } finally { fclose($temporary); $book->disconnectWorksheets(); }
    }

    public function test_duplicate_reference_and_email_across_validation_chunks_are_rejected(): void
    {
        $rows = [];
        for ($i = 0; $i < 26; $i++) {
            $user = User::factory()->create(['ref_code' => 'DUPLICATE'.$i, 'name' => 'Before']);
            $user->assignRole('Driver'); $user->driverProfile()->create();
            $rows[] = [$user->ref_code, 'After', 'unique'.$i.'@example.com'];
        }
        foreach (['reference', 'email'] as $kind) {
            $input = $rows;
            if ($kind === 'reference') $input[25][0] = $input[0][0];
            else $input[25][2] = $input[0][2];
            $token = $this->prepare('drivers', ['Ref Code', 'Name', 'Email'], $input)->json('token');
            $this->step('drivers', $token, 'validate')->assertOk();
            $this->step('drivers', $token, 'validate', 25)->assertUnprocessable()->assertSee('Duplicate');
            $this->assertSame(26, User::where('name', 'Before')->count());
        }
    }

    public function test_office_and_housekeeping_specific_fields_and_reporting_manager_validation(): void
    {
        $supervisor = $this->employees['Supervisor'];
        foreach (['Staff', 'Housekeeping', 'Controller', 'Supervisor'] as $role) {
            $employee = $this->employees[$role];
            $this->updateFile(self::MODULES[$role], ['Ref Code', 'Father Name', 'Date Of Joining', 'ESIC WC'],
                [[$employee->ref_code, 'Updated Father', '10-11-2020', 'NEW-ESIC']]);
            $profile = $employee->{lcfirst($role).'Profile'}->fresh();
            $this->assertSame('Updated Father', $profile->father_name);
            $this->assertSame('NEW-ESIC', $profile->esic_wc);
            $this->assertSame('2020-11-10', $profile->{$role === 'Housekeeping' ? 'joining_date' : 'date_of_joining'}->format('Y-m-d'));
        }
        $staff = $this->employees['Staff'];
        $this->updateFile('staff', ['Ref Code', 'Category'], [[$staff->ref_code, 'skilled']]);
        $this->assertSame('skilled', $staff->staffProfile->fresh()->category);
        foreach (['Controller', 'Housekeeping'] as $role) {
            $employee = $this->employees[$role];
            $this->updateFile(self::MODULES[$role], ['Ref Code', 'Reporting To'], [[$employee->ref_code, $supervisor->ref_code]]);
            $this->assertSame($supervisor->id, $employee->{lcfirst($role).'Profile'}->fresh()->reporting_to);
            $token = $this->prepare(self::MODULES[$role], ['Ref Code', 'Reporting To'], [[$employee->ref_code, $employee->ref_code]])->json('token');
            $this->step(self::MODULES[$role], $token, 'validate')->assertUnprocessable();
            $this->assertSame($supervisor->id, $employee->{lcfirst($role).'Profile'}->fresh()->reporting_to);
        }
        $housekeeper = $this->employees['Housekeeping'];
        $this->updateFile('housekeeping', ['Ref Code', 'Address', 'Branch', 'Emergency Contact Name'],
            [[$housekeeper->ref_code, 'New address', 'Main', 'Family Contact']]);
        $profile = $housekeeper->housekeepingProfile->fresh();
        $this->assertSame('New address', $profile->address);
        $this->assertSame(1, $profile->branch_location_id);
        $this->assertSame('Family Contact', $profile->emergency_contact_name);
    }

    public function test_tokens_are_bound_to_operator_and_permissions_are_rechecked_before_saving(): void
    {
        $driver = $this->employees['Driver'];
        $token = $this->prepare('drivers', ['Ref Code', 'Name'], [[$driver->ref_code, 'Changed']])->json('token');
        $other = User::factory()->create(); $other->givePermissionTo('driver-management.edit');
        $this->actingAs($other);
        $this->step('drivers', $token, 'validate')->assertStatus(410);
        $this->actingAs($this->operator);
        $this->step('drivers', $token, 'validate')->assertOk();
        $this->operator->revokePermissionTo('driver-management.edit');
        $this->step('drivers', $token, 'save')->assertForbidden();
        $this->assertSame('Driver Employee', $driver->fresh()->name);
    }

    public function test_sample_download_contains_only_selected_employees_or_all(): void
    {
        $ids = [$this->employees['Staff']->id, $this->employees['Supervisor']->id];
        $book = $this->sample('staff', ['sample_scope' => 'selected', 'user_ids' => $ids]);
        try {
            $sheet = $book->getActiveSheet();
            $this->assertSame(3, $sheet->getHighestDataRow());
            $this->assertSame($this->employees['Staff']->ref_code, $sheet->getCell('A2')->getValue());
            $this->assertSame('Staff Employee', $sheet->getCell('B2')->getValue());
            $this->assertSame($this->employees['Supervisor']->ref_code, $sheet->getCell('A3')->getValue());
        } finally { $book->disconnectWorksheets(); }
        $book = $this->sample('staff', ['sample_scope' => 'all']);
        $this->assertSame(5, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
        $book = $this->sample('drivers', ['sample_scope' => 'selected', 'user_ids' => [$this->employees['Driver']->id]]);
        $this->assertSame('0002', $book->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame(2, $book->getActiveSheet()->getHighestDataRow());
        $book->disconnectWorksheets();
    }

    public function test_sample_selection_rejects_empty_unknown_duplicate_and_inaccessible_employees(): void
    {
        foreach ([[], ['user_ids' => []], ['user_ids' => [999999]], ['user_ids' => [$this->employees['Staff']->id]],
            ['user_ids' => [$this->employees['Driver']->id, $this->employees['Driver']->id]], ['user_ids' => ['invalid']]] as $query) {
            $this->getJson('/bulk-update/drivers/sample?'.http_build_query(['sample_scope' => 'selected'] + $query))->assertUnprocessable();
        }
        $this->operator->revokePermissionTo('staff-management.edit');
        $this->getJson('/bulk-update/staff/sample?'.http_build_query(['sample_scope' => 'selected', 'user_ids' => [$this->employees['Staff']->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('user_ids');
    }

    public function test_employee_search_matches_name_code_and_ref_with_module_and_permission_scope(): void
    {
        $driver = $this->employees['Driver'];
        $driver->update(['code' => 'DRV-SEARCH-02']);
        foreach (['Driver Employee', 'SEARCH-02', '0002'] as $term) {
            $response = $this->getJson('/bulk-update/drivers/users?'.http_build_query(['q' => $term]))->assertOk()
                ->assertJsonCount(1, 'results')->assertJsonPath('results.0.id', $driver->id)
                ->assertJsonPath('results.0.text', 'Driver Employee | Code: DRV-SEARCH-02 | Ref: 0002')
                ->assertJsonPath('pagination.more', false);
        }
        $this->getJson('/bulk-update/drivers/users?q=Staff')->assertOk()->assertJsonCount(0, 'results');
        $this->operator->revokePermissionTo('staff-management.edit');
        $this->getJson('/bulk-update/staff/users?q=Staff')->assertOk()->assertJsonCount(0, 'results');
        $this->operator->revokePermissionTo('driver-management.edit');
        $this->getJson('/bulk-update/drivers/users')->assertForbidden();
    }

    public function test_employee_search_paginates_without_duplicates(): void
    {
        for ($i = 0; $i < 31; $i++) {
            $user = User::factory()->create(['name' => 'Driver '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'ref_code' => 'PAGE'.$i]);
            $user->assignRole('Driver'); $user->driverProfile()->create();
        }
        $first = $this->getJson('/bulk-update/drivers/users')->assertOk()->assertJsonCount(30, 'results')->assertJsonPath('pagination.more', true)->json('results');
        $second = $this->getJson('/bulk-update/drivers/users?page=2')->assertOk()->assertJsonCount(2, 'results')->assertJsonPath('pagination.more', false)->json('results');
        $this->assertSame([], array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->assertCount(32, array_unique(array_column(array_merge($first, $second), 'id')));
    }
}
