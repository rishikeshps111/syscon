<?php

use App\Http\Controllers\SalaryProcessingController;

function salaryProcessingPrivateMethod(string $methodName): ReflectionMethod
{
    $method = new ReflectionMethod(SalaryProcessingController::class, $methodName);
    $method->setAccessible(true);

    return $method;
}

test('salary processing net salary includes editable incentive and deduction', function () {
    $controller = new SalaryProcessingController();
    $method = salaryProcessingPrivateMethod('applyUnauthorizedLeave');

    $row = $method->invoke($controller, [
        'basic_salary' => 30000,
        'incentive' => 2500,
        'deduction' => 1200,
        'total_working_days' => 30,
    ], 2);

    expect($row['lop'])->toBe(2000.0)
        ->and($row['net_salary'])->toBe(29300.0);
});

test('selected incentive components are separated from gross salary', function () {
    $controller = new SalaryProcessingController();
    $method = salaryProcessingPrivateMethod('applySelectedComponents');

    $row = $method->invoke($controller, [
        'basic_salary' => 0,
        'incentive' => 0,
        'salary_split' => [
            ['id' => 10, 'name' => 'Basic', 'type' => 'earning', 'amount' => 20000],
            ['id' => 11, 'name' => 'Trip Incentive', 'type' => 'earning', 'amount' => 1500],
        ],
    ], [10, 11]);

    expect($row['basic_salary'])->toBe(20000.0)
        ->and($row['incentive'])->toBe(1500.0);
});
