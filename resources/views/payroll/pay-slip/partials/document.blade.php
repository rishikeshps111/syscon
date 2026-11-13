@php
    $money = fn ($value) => number_format((float) $value, 0);
    $shifts = rtrim(rtrim(number_format($slip['present_shifts'], 2, '.', ''), '0'), '.');
    $earningLabels = array_keys($slip['earnings']);
    $deductionLabels = array_keys($slip['deductions']);
@endphp
<div class="syscon-slip">
    <table class="slip-brand"><tr>
        <td class="slip-logo">@if ($slip['logo'])<img src="{{ $slip['logo'] }}" alt="Syscon logo">@endif</td>
        <td class="slip-company">SYSCON FUNCTIONAL NETWORKS PRIVATE LIMITED</td>
    </tr></table>
    <table>
        <colgroup><col style="width:20%"><col style="width:22%"><col style="width:25%"><col style="width:33%"></colgroup>
        <tbody>
            <tr class="slip-period"><td colspan="4">Salary Slip of {{ $slip['period'] }}</td></tr>
            <tr><td style="width:20%">Employee Code</td><td class="slip-value" style="width:22%">{{ $slip['employee_code'] }}</td><td style="width:25%">Employee Name</td><td class="slip-value" style="width:33%"><strong>{{ $slip['employee_name'] }}</strong></td></tr>
            <tr><td>Designation</td><td class="slip-value"><strong>{{ $slip['designation'] }}</strong></td><td>Department</td><td class="slip-value">{{ $slip['department'] }}</td></tr>
            <tr><td>UAN Number</td><td class="slip-value">{{ $slip['uan'] }}</td><td>Present Shifts</td><td class="slip-value">{{ $shifts }}</td></tr>
            <tr><td>ESIC Number</td><td class="slip-value">{{ $slip['esic'] }}</td><td>A/c No:</td><td class="slip-value">{{ $slip['account_number'] }}</td></tr>
            <tr><td>Location</td><td class="slip-value"><strong>{{ $slip['location'] }}</strong></td><td>IFSC</td><td class="slip-value">{{ $slip['ifsc'] }}</td></tr>
            <tr class="slip-spacer"><td colspan="4"></td></tr>
            <tr class="slip-section"><th>Earnings</th><th class="slip-amount">Amount</th><th>Deductions</th><th class="slip-amount">Amount</th></tr>
            @for ($index = 0; $index < max(count($earningLabels), count($deductionLabels)); $index++)
                @php($earning = $earningLabels[$index] ?? null)
                @php($deduction = $deductionLabels[$index] ?? null)
                <tr>
                    <td>{{ $earning }}</td><td class="slip-amount">{{ $earning !== null ? $money($slip['earnings'][$earning]) : '' }}</td>
                    <td>{{ $deduction }}</td><td class="slip-amount">{{ $deduction !== null ? $money($slip['deductions'][$deduction]) : '' }}</td>
                </tr>
            @endfor
            <tr class="slip-spacer"><td colspan="4"></td></tr>
            <tr class="slip-total"><td>Gross Earnings</td><td class="slip-amount">{{ $money($slip['gross']) }}</td><td>Gross Deductions</td><td class="slip-amount">{{ $money($slip['total_deduction']) }}</td></tr>
            <tr class="slip-spacer"><td colspan="4"></td></tr>
            <tr><td colspan="2" rowspan="4" class="slip-employer-blank"></td><th class="slip-section slip-employer-label">Employer Contributions</th><th class="slip-section slip-amount">Amount</th></tr>
            <tr><td class="slip-employer-label">PF</td><td class="slip-amount">{{ $money($slip['employer_pf']) }}</td></tr>
            <tr><td class="slip-employer-label">Employer ESIC</td><td class="slip-amount">{{ $money($slip['employer_esi']) }}</td></tr>
            <tr class="slip-total"><td class="slip-employer-label">Total Employer Contribution</td><td class="slip-amount">{{ $money($slip['employer_total']) }}</td></tr>
            <tr class="slip-spacer"><td colspan="4"></td></tr>
            <tr class="slip-net"><td colspan="4">Net Salary - Rs. {{ $money($slip['net']) }}</td></tr>
            @if ($slip['policy'])
                <tr class="slip-policy"><td colspan="4">This company maintains Workmen's Compensation Policy under policy number {{ $slip['policy'] }}</td></tr>
            @endif
        </tbody>
    </table>
</div>
