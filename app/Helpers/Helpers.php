<?php

use App\Models\GeneralSetting;
use App\Models\Prefix;

if (! function_exists('get_prefix')) {
    function get_prefix(string $module, string $default = 'RECORD'): string
    {
        return Prefix::query()
            ->where('module', $module)
            ->where('is_active', true)
            ->value('prefix') ?? $default;
    }
}

if (! function_exists('get_financial_year')) {
    function get_financial_year(): string
    {
        return (string) (GeneralSetting::query()->value('financial_year') ?? now()->year);
    }
}

if (! function_exists('generate_code')) {
    function generate_code(string $module, int $id, int $pad = 3, string $default = 'RECORD'): string
    {
        $prefix = get_prefix($module, $default);
        $year = get_financial_year();

        return $prefix . '' . $year . '#' . str_pad((string) $id, $pad, '0', STR_PAD_LEFT);
    }
}
