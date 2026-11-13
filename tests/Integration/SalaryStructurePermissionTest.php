<?php

namespace Tests\Integration;

use App\Http\Controllers\DriverManagementController;
use App\Http\Controllers\StaffManagementController;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalaryPermissionTestLayout extends \Illuminate\View\Component
{
    public function render(): string
    {
        return '<div>{{ $slot }}</div>@yield("scripts")';
    }
}

class SalaryStructurePermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'activitylog.enabled' => false]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_05_12_031408_create_permission_tables.php'))->up();
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->migration()->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware(\App\Http\Middleware\TrackStaffUserLog::class);
        Blade::component(SalaryPermissionTestLayout::class, 'app-layout');
        Schema::create('designations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('role_type'); });
        Schema::create('salary_components', function (Blueprint $t) { $t->id(); $t->string('component_name'); $t->string('type'); $t->timestamps(); });
        Schema::create('salary_templates', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('designation_id')->nullable(); $t->timestamps(); });
        Schema::create('salary_template_items', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('salary_template_id'); $t->unsignedBigInteger('salary_component_id'); $t->decimal('amount', 12, 2); $t->timestamps(); });
        Schema::create('user_salary_component_values', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('salary_component_id'); $t->decimal('amount', 12, 2); $t->timestamps(); });
        foreach (['Staff', 'Driver'] as $index => $role) {
            $r = Role::create(['name' => $role, 'guard_name' => 'web']);
            DB::table('designations')->insert(['id' => $index + 1, 'name' => $role, 'role_type' => $role]);
            DB::table('salary_templates')->insert(['id' => $index + 1, 'role_id' => $r->id, 'designation_id' => $index + 1]);
            DB::table('salary_template_items')->insert(['salary_template_id' => $index + 1, 'salary_component_id' => 1, 'amount' => 96321]);
        }
        SalaryComponent::create(['component_name' => 'Confidential Basic', 'type' => 'earning']);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_13_000003_add_salary_structure_view_permissions.php');
    }

    private function formData(): array
    {
        $data = ['errors' => new \Illuminate\Support\ViewErrorBag, 'record' => null, 'profile' => null, 'countries' => ['India'], 'employeeRoles' => ['Staff', 'Housekeeping', 'Controller', 'Supervisor'],
            'employmentTypes' => [], 'licenseTypes' => [], 'verificationStatuses' => [], 'categories' => [],
            'salaryComponents' => collect([SalaryComponent::first()->setAttribute('template_default_amount', 96321)]),
            'salaryComponentValues' => collect()];
        foreach (['depots', 'states', 'districts', 'locations', 'designations', 'branches', 'reportingManagers', 'reportingUsers'] as $field) $data[$field] = collect();
        return $data;
    }

    public function test_forms_hide_salary_values_and_salary_requests_without_the_module_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $employee = new User(['name' => 'Existing Employee']);
        $employee->id = 999;
        $employee->setRelation('staffProfile', null)->setRelation('driverProfile', null);
        foreach ([null, $employee] as $record) {
        foreach (['staff-management.unified-form', 'staff-management.form', 'driver-management.form'] as $view) {
            view()->flushSections();
            $html = str_replace(chr(92).'/', '/', view($view, array_replace($this->formData(), ['record' => $record]))->render());
            $this->assertStringNotContainsString('Confidential Basic', $html);
            $this->assertStringNotContainsString('96321', $html);
            $this->assertStringNotContainsString('id="gross_salary_preview"', $html);
            $this->assertStringNotContainsString('>Salary Structure<', $html);
            $this->assertStringNotContainsString('/salary-structure', $html);
        }
        }
        $user->givePermissionTo('staff-management.view-salary');
        view()->flushSections();
        $staff = str_replace(chr(92).'/', '/', view('staff-management.unified-form', $this->formData())->render());
        $this->assertStringContainsString('Confidential Basic', $staff);
        $this->assertStringContainsString('value="96321"', $staff);
        $this->assertStringContainsString('/staff-management/salary-structure', $staff);
        view()->flushSections();
        $legacyStaff = str_replace(chr(92).'/', '/', view('staff-management.form', $this->formData())->render());
        $this->assertStringContainsString('Confidential Basic', $legacyStaff);
        view()->flushSections();
        $driver = str_replace(chr(92).'/', '/', view('driver-management.form', $this->formData())->render());
        $this->assertStringNotContainsString('Confidential Basic', $driver);
        $user->givePermissionTo('driver-management.view-salary');
        view()->flushSections();
        $driver = str_replace(chr(92).'/', '/', view('driver-management.form', $this->formData())->render());
        $this->assertStringContainsString('Confidential Basic', $driver);
        $this->assertStringContainsString('/driver-management/salary-structure', $driver);
    }

    public function test_salary_view_permissions_do_not_grant_employee_create_access(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['staff-management.view-salary', 'driver-management.view-salary']);
        $this->actingAs($user);
        $this->getJson('/staff-management/create')->assertForbidden();
        $this->getJson('/driver-management/create')->assertForbidden();
    }

    public function test_salary_endpoints_enforce_independent_permissions_even_for_module_viewers(): void
    {
        $user = User::factory()->create();
        foreach (['staff-management.view', 'driver-management.view'] as $name) {
            $user->givePermissionTo(Permission::create(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Test']));
        }
        $this->actingAs($user);
        $staff = '/staff-management/salary-structure?role=Staff&designation_id=1';
        $driver = '/driver-management/salary-structure?designation_id=2';
        $this->getJson($staff)->assertForbidden();
        $this->getJson($driver)->assertForbidden();
        $user->givePermissionTo('staff-management.view-salary');
        $this->get($staff)->assertOk()->assertSee('Confidential Basic')->assertSee('96321');
        $this->getJson($driver)->assertForbidden();
        $user->givePermissionTo('driver-management.view-salary');
        $this->get($driver)->assertOk()->assertSee('Confidential Basic')->assertSee('96321');
        $user->revokePermissionTo('staff-management.view-salary');
        $this->getJson($staff)->assertForbidden();
    }

    public function test_post_validation_employee_saves_keep_template_salaries_without_salary_view_permission(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('ref_code')->nullable());
        Schema::create('depots', function (Blueprint $t) { $t->id(); $t->string('short_name'); });
        DB::table('depots')->insert(['id' => 1, 'short_name' => 'NLG']);
        foreach (['staff_profiles', 'driver_profiles'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('designation_id')->nullable();
                $t->unsignedBigInteger('depot_id')->nullable(); $t->string('category')->nullable();
                foreach (['basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary', 'salary'] as $field) $t->decimal($field, 12, 2)->nullable();
                $t->timestamps();
            });
        }
        $operator = User::factory()->create();
        foreach (['staff-management.create', 'staff-management.edit', 'driver-management.create', 'driver-management.edit'] as $name) {
            $operator->givePermissionTo(Permission::create(['name' => $name, 'guard_name' => 'web', 'group_name' => 'Test']));
        }
        $this->actingAs($operator);
        foreach (['Staff', 'Driver'] as $role) {
            $module = $role === 'Staff' ? 'staff-management' : 'driver-management';
            $this->assertFalse($operator->can($module.'.view-salary'));
            $controller = $role === 'Staff' ? new StaffManagementController : new DriverManagementController;
            $data = ['role' => $role, 'name' => $role.' Employee', 'email' => strtolower($role).'@example.test',
                'country_code' => '+91', 'phone' => '9876543210', 'is_active' => true, 'depot_id' => 1,
                'designation_id' => $role === 'Staff' ? 1 : 2, 'salary_components' => [1 => 1]];
            foreach (['store', 'update'] as $operation) {
                $requestClass = $role === 'Staff' ? \App\Http\Requests\SaveUnifiedStaffRequest::class
                    : ($operation === 'store' ? \App\Http\Requests\StoreDriverManagementRequest::class : \App\Http\Requests\UpdateDriverManagementRequest::class);
                $request = \Mockery::mock($requestClass)->makePartial();
                $data['name'] = $role.' '.$operation;
                $request->shouldReceive('validated')->once()->andReturn($data);
                $request->shouldReceive('hasFile')->with('avatar')->andReturnFalse();
                if ($operation === 'store') $controller->store($request);
                else $controller->update($request, $employee);
                $employee = User::where('email', $data['email'])->firstOrFail();
                $this->assertSame($data['name'], $employee->name);
                $this->assertEquals(96321, DB::table('user_salary_component_values')->where('user_id', $employee->id)->value('amount'));
                $profile = $employee->{lcfirst($role).'Profile'};
                $this->assertEquals(96321, $role === 'Staff' ? $profile->gross_salary : $profile->salary);
            }
        }
    }

    public function test_permission_migration_and_seeder_are_idempotent_and_only_grant_super_admin(): void
    {
        $viewer = Role::create(['name' => 'Module Viewer', 'guard_name' => 'web']);
        $viewer->givePermissionTo(Permission::create(['name' => 'staff-management.view', 'guard_name' => 'web', 'group_name' => 'Staff Management']));
        $this->migration()->up();
        $this->migration()->up();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = Role::findByName('Super Admin');
        foreach (['staff-management.view-salary', 'driver-management.view-salary'] as $name) {
            $this->assertSame(1, Permission::where('name', $name)->count());
            $this->assertTrue($admin->hasPermissionTo($name));
            $this->assertFalse($viewer->hasPermissionTo($name));
        }
        $this->actingAs(User::factory()->create()->assignRole($admin));
        $this->get('/driver-management/salary-structure?designation_id=2')->assertOk();
        $tree = (new \ReflectionMethod(\App\Http\Controllers\RolePermissionController::class, 'permissionTree'))->invoke(new \App\Http\Controllers\RolePermissionController);
        foreach (['Staff Management' => 'staff-management.view-salary', 'Driver Management' => 'driver-management.view-salary'] as $label => $name) {
            $child = collect($tree)->flatMap(fn ($section) => $section['children'])->firstWhere('label', $label);
            $this->assertContains($name, $child['permissions']->pluck('name')->all());
        }
        $this->migration()->down();
        $this->assertSame(0, Permission::whereIn('name', ['staff-management.view-salary', 'driver-management.view-salary'])->count());
        $this->assertTrue(Permission::where('name', 'staff-management.view')->exists());
    }
}
