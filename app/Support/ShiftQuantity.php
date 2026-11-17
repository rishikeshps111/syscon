<?php

namespace App\Support;

class ShiftQuantity
{
    public static function decimals(mixed $value): int
    {
        $quantity = round((float) $value, 2);

        return $quantity == floor($quantity) ? 0 : 2;
    }

    public static function format(mixed $value): string
    {
        return number_format((float) $value, self::decimals($value));
    }

    public static function excelFormat(mixed $value): string
    {
        return self::decimals($value) === 0 ? '#,##0' : '#,##0.00';
    }
}
