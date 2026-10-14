<?php

use App\Http\Controllers\SalaryProcessingController;
use App\Models\Designation;
use App\Models\SalaryComponent;
use App\Models\SalaryTemplate;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class);

beforeEach(function () {
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });
    Schema::create('salary_components', function (Blueprint $table) {
        $table->id();
        $table->string('component_name');
        $table->string('type');
        $table->timestamps();
    });
    Schema::create('salary_templates', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('designation_id')->nullable();
        $table->timestamps();
    });
    Schema::create('salary_template_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('salary_template_id');
        $table->unsignedBigInteger('salary_component_id');
        $table->decimal('amount', 12, 2);
        $table->timestamps();
    });
});

test('salary processing uses each employee profile designation and matching template', function (string $roleName, string $relation, string $profileClass) {
    $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
    $component = SalaryComponent::create(['component_name' => 'Basic', 'type' => 'earning']);
    foreach ([101 => 12000, 102 => 18000] as $designationId => $amount) {
        $template = SalaryTemplate::create(['role_id' => $role->id, 'designation_id' => $designationId]);
        $template->items()->create(['salary_component_id' => $component->id, 'amount' => $amount]);
    }

    $controller = new SalaryProcessingController();
    $splitMethod = new ReflectionMethod($controller, 'salarySplit');
    $detailsMethod = new ReflectionMethod($controller, 'userDetails');

    foreach ([101 => 12000.0, 102 => 18000.0, 103 => null] as $designationId => $amount) {
        $profile = new $profileClass();
        $profile->forceFill(['designation_id' => $designationId]);
        $profile->setRelation('designation', new Designation(['name' => 'Designation '.$designationId]));
        $profile->setRelation('depot', null);
        $user = new User(['name' => 'Employee']);
        $user->setRelation($relation, $profile);
        if ($relation !== 'staffProfile') {
            $user->setRelation('staffProfile', null);
        }

        expect($detailsMethod->invoke($controller, $user, $roleName)['designation'])
            ->toBe('Designation '.$designationId);
        $split = $splitMethod->invoke($controller, $user, $roleName);
        if ($amount === null) {
            expect($split)->toBe([]);
        } else {
            expect($split)->toBe([
                ['id' => $component->id, 'name' => 'Basic', 'type' => 'earning', 'amount' => $amount, 'selected' => true],
            ]);
        }
    }
})->with([
    ['Staff', 'staffProfile', App\Models\StaffProfile::class],
    ['Driver', 'driverProfile', App\Models\DriverProfile::class],
    ['Housekeeping', 'housekeepingProfile', App\Models\HousekeepingProfile::class],
    ['Controller', 'controllerProfile', App\Models\ControllerProfile::class],
    ['Supervisor', 'supervisorProfile', App\Models\SupervisorProfile::class],
]);
