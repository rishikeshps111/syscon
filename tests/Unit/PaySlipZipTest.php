<?php

use App\Models\SalaryProcessing;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\TrackStaffUserLog::class);
    foreach (['users', 'depots', 'roles', 'permissions'] as $name) {
        Schema::create($name, function (Blueprint $table) use ($name) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });
    }
    foreach (['driver_profiles', 'housekeeping_profiles', 'staff_profiles', 'controller_profiles', 'supervisor_profiles'] as $name) {
        Schema::create($name, function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id');
        });
    }
    foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $name => $column) {
        Schema::create($name, function (Blueprint $table) use ($column) {
            $table->unsignedBigInteger($column);
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });
    }
    Schema::create('role_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('permission_id');
    });
    Schema::create('salary_processings', function (Blueprint $table) {
        $table->id(); $table->integer('year'); $table->integer('month');
        $table->integer('depot_id'); $table->string('status'); $table->timestamps();
    });
    Schema::create('salary_processing_items', function (Blueprint $table) {
        $table->id(); $table->integer('salary_processing_id'); $table->integer('user_id');
        $table->decimal('basic_salary')->default(0); $table->decimal('net_salary')->default(0);
        $table->json('salary_split')->nullable(); $table->timestamps();
    });
    DB::table('depots')->insert(['id' => 1, 'name' => 'Test Depot']);
    DB::table('users')->insert(['id' => 1, 'name' => 'Manager']);
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'salary-slips.view', 'guard_name' => 'web']);
    $this->filters = ['year' => 2026, 'month' => 10, 'depot_id' => 1];
    $this->travelTo(\Carbon\Carbon::parse('2026-10-14 12:00:00'));
});

function authorizeZipTest(): void
{
    $user = User::find(1);
    $user->givePermissionTo('salary-slips.view');
    test()->actingAs($user);
}

test('zip endpoint requires authentication and payslip permission', function () {
    $this->postJson(route('salary-slips.zip'), $this->filters)->assertUnauthorized();
    $this->actingAs(User::find(1))->postJson(route('salary-slips.zip'), $this->filters)->assertForbidden();
});

test('zip validates filters and rejects missing or empty approved payroll', function () {
    authorizeZipTest();
    $this->postJson(route('salary-slips.zip'), [])->assertUnprocessable();
    $this->postJson(route('salary-slips.zip'), $this->filters)->assertNotFound();
    $processing = SalaryProcessing::create($this->filters + ['status' => 'Completed']);
    $this->postJson(route('salary-slips.zip'), $this->filters)->assertNotFound();
    $processing->update(['status' => 'Approved']);
    $this->postJson(route('salary-slips.zip'), $this->filters)->assertNotFound();
});

test('zip includes each employee once with unique names and deletes after sending', function (int $count) {
    authorizeZipTest();
    $processing = SalaryProcessing::create($this->filters + ['status' => 'Approved']);
    foreach (range(2, $count + 1) as $id) {
        DB::table('users')->insert(['id' => $id, 'name' => 'Same Name']);
        $processing->items()->create(['user_id' => $id, 'basic_salary' => 10000, 'net_salary' => 9000]);
    }
    $processing->items()->create(['user_id' => 999]); // Deleted employee.
    $other = SalaryProcessing::create(['year' => 2025, 'month' => 10, 'depot_id' => 1, 'status' => 'Approved']);
    $other->items()->create(['user_id' => 1]);
    $response = $this->postJson(route('salary-slips.zip'), $this->filters)->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();
    $archive = new ZipArchive();
    expect($archive->open($path))->toBeTrue()->and($archive->numFiles)->toBe($count);
    foreach (range(0, $count - 1) as $index) {
        expect($archive->getNameIndex($index))->toContain('-employee-' . ($index + 2) . '.pdf');
        $pdf = $archive->getFromIndex($index);
        expect($pdf)->toStartWith('%PDF')->toContain('10,000')->toContain('9,000');
        $individual = $this->get(route('salary-slips.pdf', $this->filters + ['user_id' => $index + 2]))->assertOk();
        expect($pdf)->toBe($individual->getContent());
    }
    $archive->close();
    ob_start();
    $response->baseResponse->sendContent();
    ob_end_clean();
    expect(is_file($path))->toBeFalse();
})->with([1, 2]);

test('zip generation failure removes the partial archive', function () {
    authorizeZipTest();
    $processing = SalaryProcessing::create($this->filters + ['status' => 'Approved']);
    $processing->items()->create(['user_id' => 1, 'salary_split' => [['name' => ['invalid'], 'amount' => 1]]]);
    $before = glob(storage_path('app/payslips-*'));
    $this->postJson(route('salary-slips.zip'), $this->filters)->assertStatus(500);
    expect(glob(storage_path('app/payslips-*')))->toBe($before);
});

test('zip POST rejects requests without a CSRF token outside the test bypass', function () {
    authorizeZipTest();
    $this->app['env'] = 'production';
    try {
        $this->postJson(route('salary-slips.zip'), $this->filters)->assertStatus(419);
    } finally {
        $this->app['env'] = 'testing';
    }
});
