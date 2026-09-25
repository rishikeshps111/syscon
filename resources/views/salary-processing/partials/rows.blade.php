@forelse ($rows as $index => $row)
    @php($attendanceDetails = [
        'total_days' => $row['total_attendance_days'],
        'week_off_days' => $row['week_off_days'] ?? 0,
        'absent_days' => $row['absent_days'] ?? 0,
        'actual_working_days' => $row['present_days'] ?? 0,
        'actual_worked_days' => $row['actual_worked_days'] ?? 0,
        'lop_days' => $row['unpaid_leave_days'] ?? 0,
        'per_day_salary' => $row['salary_day_rate'] ?? 0,
    ])
    <tr data-basic="{{ $row['basic_salary'] }}" data-deduction="{{ $row['deduction'] }}" data-working-days="{{ $row['total_working_days'] }}" data-unpaid-leave-days="{{ $row['unpaid_leave_days'] ?? 0 }}">
        <td class="text-center">{{ $index + 1 }}<input type="hidden" name="items[{{ $index }}][user_id]" value="{{ $row['user_id'] }}"></td>
        <td class="text-center">{{ $row['name'] }} <button type="button" class="btn btn-link p-0 view-user-details" data-details='@json($row['user_details'])'>[Details]</button> <button type="button" class="btn btn-link p-0 view-attendance attendance-details-btn" data-attendance='@json($attendanceDetails)'>[Attendance]</button></td>
        <td class="text-center">{{ number_format((float) $row['total_attendance_days'], 2) }}</td>
        <td class="text-center">{{ number_format((float) $row['present_days'], 2) }}</td>
        <td class="text-center">{{ number_format((float) $row['actual_worked_days'], 2) }}</td>
        <td class="text-center lop"><span class="lop-days">{{ number_format((float) ($row['unpaid_leave_days'] ?? 0), 2) }}</span><input type="hidden" name="items[{{ $index }}][unauthorized_leaves]" value="{{ $row['unpaid_leave_days'] ?? 0 }}"></td>
        <td class="text-center"><span class="gross-salary">{{ number_format((float) $row['basic_salary'], 2) }}</span> <button type="button" class="btn btn-link p-0 view-split" data-split='@json($row['salary_split'])'>[View Split]</button>
            @foreach ($row['salary_split'] as $component)
                @if ($component['selected'] ?? true)<input type="hidden" class="selected-component-input" name="items[{{ $index }}][selected_components][]" value="{{ $component['id'] }}">@endif
            @endforeach
        </td>
        <td class="text-center"><input type="number" step="0.01" class="form-control shadow-none deduction-input" name="items[{{ $index }}][deduction]" value="{{ number_format((float) $row['deduction'], 2, '.', '') }}" readonly></td>
        <td class="text-center net-salary">{{ number_format((float) $row['net_salary'], 2) }}</td>
    </tr>
@empty
    <tr><td colspan="9" class="text-center text-muted">Select a depot to load consolidated attendance.</td></tr>
@endforelse
