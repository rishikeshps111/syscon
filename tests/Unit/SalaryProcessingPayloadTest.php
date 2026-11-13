<?php

use App\Http\Controllers\SalaryProcessingController;
use App\Models\SalaryProcessing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

beforeEach(function () {
    Schema::create('depots', fn (Blueprint $table) => $table->id());
    Schema::create('users', fn (Blueprint $table) => $table->id());
    Schema::create('attendance_consolidate_imports', function (Blueprint $table) {
        $table->id();
        $table->integer('year');
        $table->integer('month');
        $table->integer('depot_id');
    });
    DB::table('depots')->insert(['id' => 1]);
    DB::table('attendance_consolidate_imports')->insert(['id' => 1, 'year' => 2026, 'month' => 10, 'depot_id' => 1]);
    DB::table('users')->insert(array_map(fn ($id) => ['id' => $id], range(1, 250)));
});

function salaryPayloadRequest(array $overrides = []): Request
{
    return Request::create('/salary-processing', 'POST', array_replace([
        'year' => 2026, 'month' => 10, 'depot_id' => 1,
        'attendance_consolidate_import_id' => 1,
        'payment_method' => 'Bank Transfer',
        'items' => [['user_id' => 1]],
    ], $overrides));
}

function validateSalaryPayload(Request $request): array
{
    return (new ReflectionMethod(SalaryProcessingController::class, 'validatedPayload'))
        ->invoke(new SalaryProcessingController(), $request);
}

test('compact salary payload preserves a large batch and payment method', function (string $paymentMethod, string $method) {
    $items = array_map(fn ($id) => [
        'user_id' => (string) $id, 'deduction' => '123.45', 'incentive' => '20',
        'unauthorized_leaves' => '1.5', 'selected_components' => ['10', '12'],
    ], range(1, 250));
    // These items would require 1,500 individual form variables.
    $parameters = salaryPayloadRequest(['items_json' => json_encode($items), 'payment_method' => $paymentMethod])->all();
    unset($parameters['items']);
    $request = Request::create('/salary-processing', $method, $parameters);

    $data = validateSalaryPayload($request);

    expect($data['items'])->toBe($items)
        ->and($data['payment_method'])->toBe($paymentMethod);
})->with(array_keys(SalaryProcessing::PAYMENT_METHODS))->with(['POST', 'PUT']);

test('legacy salary item arrays remain supported', function () {
    expect(validateSalaryPayload(salaryPayloadRequest())['items'])->toBe([['user_id' => 1]]);
});

test('invalid compact salary payloads fail validation', function ($payload) {
    expect(fn () => validateSalaryPayload(salaryPayloadRequest(['items_json' => $payload])))
        ->toThrow(ValidationException::class);
})->with(['broken json' => ['['], 'object' => ['{"user_id":1}'], 'null' => ['null'], 'empty list' => ['[]'], 'array input' => [[]], 'invalid employee' => ['[{"user_id":999}]']]);

test('payment method remains required with compact salary items', function () {
    try {
        validateSalaryPayload(salaryPayloadRequest(['items_json' => '[{"user_id":1}]', 'payment_method' => '']));
        $this->fail('Expected payment method validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('payment_method');
    }
});


test('manual deductions survive form arrays and compact JSON payloads', function (bool $compact) {
    $items = [['user_id' => 1, 'recalculate_esi' => '1', 'esi' => '78.90', 'tds' => '123.45', 'salary_advance' => '200', 'lwf' => '0', 'penalty' => null]];
    $input = $compact ? ['items_json' => json_encode($items)] : ['items' => $items];
    expect(validateSalaryPayload(salaryPayloadRequest($input))['items'])->toBe($items);
})->with([false, true]);

test('manual deduction validation rejects invalid monetary values', function (string $field, mixed $value) {
    try {
        validateSalaryPayload(salaryPayloadRequest(['items' => [['user_id' => 1, $field => $value]]]));
        $this->fail('Invalid deduction accepted');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('items.0.'.$field);
    }
})->with(['esi', 'tds', 'salary_advance', 'lwf', 'penalty'])->with([-1, 'invalid', '1.234', '10000000000']);


test('ESI recalculation flag rejects invalid values', function (mixed $value) {
    try {
        validateSalaryPayload(salaryPayloadRequest(['items' => [['user_id' => 1, 'recalculate_esi' => $value]]]));
        $this->fail('Invalid recalculation flag accepted');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('items.0.recalculate_esi');
    }
})->with(['invalid', 2]);
