@forelse ($rows as $index => $row)
    @php($row = \App\Support\SalaryCalculation::wholeRupeeDeductions($row))
    @php($attendanceDetails = [
        'present_days' => $row['present_days'] ?? 0,
        'wo_days' => $row['total_attendance_days'] ?? 0,
        'absent_days' => $row['absent_days'] ?? 0,
        'week_off_days' => $row['week_off_days'] ?? 0,
        'per_day_salary' => $row['salary_day_rate'] ?? 0,
    ])
    <tr data-pf-applicable="{{ ($row['pf_applicable'] ?? true) ? 1 : 0 }}" data-base-deduction="{{ (float) ($row['total_deduction'] ?? $row['deduction'] ?? 0) - (float) ($row['esi'] ?? 0) - (float) ($row['tds'] ?? 0) - (float) ($row['salary_advance'] ?? 0) - (float) ($row['lwf'] ?? 0) - (float) ($row['penalty'] ?? 0) }}" data-total-earned="{{ $row['total_earned'] ?? ((float) ($row['net_total'] ?? $row['net_salary'] ?? 0) + (float) ($row['total_deduction'] ?? $row['deduction'] ?? 0)) }}" data-basic="{{ $row['basic_salary'] }}" data-lop="{{ $row['lop'] ?? 0 }}" data-deduction="{{ $row['deduction'] }}" data-working-days="{{ $row['total_working_days'] }}" data-calendar-days="{{ $row['calendar_days'] ?? 0 }}" data-total-days="{{ $row['total_attendance_days'] }}" data-unpaid-leave-days="{{ $row['unpaid_leave_days'] ?? 0 }}">
        <td class="text-center">{{ $index + 1 }}<input type="hidden" name="items[{{ $index }}][user_id]" value="{{ $row['user_id'] }}"></td>
        <td class="text-center">{{ $row['name'] }} <button type="button" class="btn btn-link p-0 view-user-details" data-details='@json($row['user_details'])'>[Details]</button> <button type="button" class="btn btn-link p-0 view-attendance attendance-details-btn" data-attendance='@json($attendanceDetails)'>[Attendance]</button></td>
        <td class="text-center">{{ number_format((float) $row['total_attendance_days'], 2) }}</td>
        <td class="text-center">{{ number_format((float) ($row['extra_days_worked'] ?? 0), 2) }}</td>
        <td class="text-center"><span class="gross-salary">{{ number_format((float) ($row['gross_salary'] ?? $row['basic_salary']), 0) }}</span> <button type="button" class="btn btn-link p-0 view-split" data-split='@json($row['salary_split'])'>[View Split]</button>
            @foreach ($row['salary_split'] as $component)
                @if ($component['selected'] ?? true)<input type="hidden" class="selected-component-input" name="items[{{ $index }}][selected_components][]" value="{{ $component['id'] }}">@endif
            @endforeach
        </td>
        <td class="text-center pf">{{ number_format((float) ($row['pf'] ?? 0), 0) }}</td>
        <td class="text-center professional-tax">{{ number_format((float) ($row['professional_tax'] ?? 0), 0) }}</td>
        @foreach (['esi' => 'ESI', 'tds' => 'TDS', 'salary_advance' => 'Salary Advance', 'lwf' => 'LWF', 'penalty' => 'Penalty'] as $field => $label)
            <td><input type="text" inputmode="numeric" pattern="[0-9]{1,10}([.][0-9]{1,2})?" maxlength="13" class="form-control manual-deduction" aria-label="{{ $label }}" name="items[{{ $index }}][{{ $field }}]" value="{{ number_format((float) ($row[$field] ?? 0), 0, '.', '') }}">
                @if ($field === 'esi')
                    <input type="hidden" class="recalculate-esi-flag" name="items[{{ $index }}][recalculate_esi]" value="0">
                @endif
            </td>
        @endforeach
        <td class="text-center total-deduction">{{ number_format((float) ($row['total_deduction'] ?? $row['deduction']), 0) }}<input type="hidden" class="deduction-input" name="items[{{ $index }}][deduction]" value="{{ number_format((float) $row['deduction'], 2, '.', '') }}"><input type="hidden" name="items[{{ $index }}][unauthorized_leaves]" value="{{ $row['unpaid_leave_days'] ?? 0 }}"></td>
        <td class="text-center net-total">{{ number_format((float) ($row['net_total'] ?? $row['net_salary']), 0) }}</td>
    </tr>
@empty
    <tr><td colspan="14" class="text-center text-muted">Select a depot to load consolidated attendance.</td></tr>
@endforelse
