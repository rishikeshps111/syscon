<!doctype html>
<html><head><meta charset="utf-8"><title>Salary Slip {{ $slip['period'] }}</title>
@include('payroll.pay-slip.partials.styles')
<style>@page { margin: 24mm 15mm; } body { margin: 0; } .syscon-slip { font-size: 9px; } .syscon-slip .slip-brand td { font-size: 12px; padding: 10px; } .syscon-slip .slip-brand .slip-logo { width: 90px; } .syscon-slip .slip-logo img { width: 80px; } .syscon-slip td, .syscon-slip th { padding: 3.5px 5px; line-height: 1.3; } .syscon-slip .slip-period td { padding: 6px; } .syscon-slip .slip-net td { font-size: 11px; padding: 9px; } .syscon-slip .slip-policy td { font-size: 8px; padding: 6px; }</style>
</head><body>@include('payroll.pay-slip.partials.document', ['slip' => $slip])</body></html>
