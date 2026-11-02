<?php

use App\Support\IfscBankName;

uses(Tests\TestCase::class);

test('bank names resolve locally from valid normalized IFSC prefixes', function (?string $ifsc, ?string $bank) {
    \Illuminate\Support\Facades\Http::preventStrayRequests();
    expect(IfscBankName::lookup($ifsc))->toBe($bank);
})->with([
    ['SBIN0001234', 'State Bank of India'],
    [' hdfc0000001 ', 'HDFC Bank'],
    ['icic0000001', 'ICICI Bank'],
    ['UTIB0000001', 'Axis Bank'],
    ['ZZZZ0000001', null], [null, null], ['', null], ['   ', null],
    ['SBIN', null], ['SBIN1001234', null], ['SBIN00012345', null], ['SB1N0001234', null],
    ['SBIN0 01234', null], ['SBIN0001234extra', null],
]);
