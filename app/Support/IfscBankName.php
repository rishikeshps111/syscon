<?php

namespace App\Support;

class IfscBankName
{
    private static ?array $banks = null;

    public static function lookup(?string $ifsc): ?string
    {
        $ifsc = strtoupper(trim($ifsc ?? ''));
        if (! preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/D', $ifsc)) {
            return null;
        }
        // Local Razorpay IFSC bank-prefix snapshot. No branch or network lookup.
        // Source: https://github.com/razorpay/ifsc/blob/master/src/banknames.json
        // Retrieved 2026-10-31; license retained beside the data.
        self::$banks ??= json_decode(file_get_contents(resource_path('data/ifsc-bank-names.json')), true, 512, JSON_THROW_ON_ERROR);

        return self::$banks[substr($ifsc, 0, 4)] ?? null;
    }
}
