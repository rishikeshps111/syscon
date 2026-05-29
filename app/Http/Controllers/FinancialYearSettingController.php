<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFinancialYearSettingRequest;
use App\Models\GeneralSetting;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class FinancialYearSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('settings.view'), ['index']),
            new Middleware(PermissionMiddleware::using('settings.edit'), ['update']),
        ];
    }

    public function index()
    {
        $setting = GeneralSetting::query()->first() ?? new GeneralSetting([
            'financial_year' => now()->year,
            'financial_year_from_month' => 4,
            'financial_year_to_year' => now()->addYear()->year,
            'financial_year_to_month' => 3,
        ]);

        return view('settings.financial-year', [
            'setting' => $setting,
            'months' => $this->months(),
            'years' => range(now()->year - 5, now()->year + 10),
        ]);
    }

    public function update(UpdateFinancialYearSettingRequest $request)
    {
        $data = $request->validated();
        $data['financial_year'] = (string) $data['financial_year'];

        GeneralSetting::query()->firstOrCreate([])->update($data);

        return redirect()
            ->route('financial-year-settings.index')
            ->with('success', 'Financial year settings updated successfully.');
    }

    private function months(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }
}
