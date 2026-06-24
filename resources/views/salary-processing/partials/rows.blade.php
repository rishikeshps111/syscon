@forelse ($rows as $index => $row)
    <tr data-basic="{{ $row['basic_salary'] }}" data-deduction="{{ $row['deduction'] }}" data-incentive="{{ $row['incentive'] }}" data-working-days="{{ $row['total_working_days'] }}">
        <td class="text-center">
            {{ $index + 1 }}
            <input type="hidden" name="items[{{ $index }}][user_id]" value="{{ $row['user_id'] }}">
        </td>
        <td class="text-center">
            {{ $row['name'] }}
            <button type="button" class="btn btn-link p-0 view-user-details" data-details='@json($row['user_details'])'>[Details]</button>
        </td>
        <td class="text-center">{{ number_format((float) $row['total_leave_taken'], 2) }}</td>
        <td class="text-center driver-only {{ $isDriver ? '' : 'd-none' }}">{{ $isDriver ? $row['total_shifts_completed'] : '-' }}</td>
        <td class="text-center non-driver-only {{ $isDriver ? 'd-none' : '' }}">{{ $row['total_working_days'] }}</td>
        <td class="text-center lop">{{ number_format((float) $row['lop'], 2) }}</td>
        <td class="text-center">
            <span class="gross-salary">{{ number_format((float) $row['basic_salary'], 2) }}</span>
            <button type="button" class="btn btn-link p-0 view-split" data-split='@json($row['salary_split'])'>[View Split]</button>
            @foreach ($row['salary_split'] as $component)
                @if ($component['selected'] ?? true)
                    <input type="hidden" class="selected-component-input" name="items[{{ $index }}][selected_components][]" value="{{ $component['id'] }}">
                @endif
            @endforeach
        </td>
        <td class="text-center">
            <input type="number" step="0.01" min="0" class="form-control shadow-none salary-adjustment deduction-input"
                name="items[{{ $index }}][deduction]" value="{{ number_format((float) $row['deduction'], 2, '.', '') }}">
        </td>
        <td class="text-center">
            <input type="number" step="0.01" min="0" class="form-control shadow-none salary-adjustment incentive-input"
                name="items[{{ $index }}][incentive]" value="{{ number_format((float) $row['incentive'], 2, '.', '') }}">
        </td>
        <td class="text-center">
            <input type="number" step="0.01" min="0" class="form-control shadow-none unauthorized-leaves"
                name="items[{{ $index }}][unauthorized_leaves]" value="{{ number_format((float) $row['unauthorized_leaves'], 2, '.', '') }}">
        </td>
        <td class="text-center net-salary">{{ number_format((float) $row['net_salary'], 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center text-muted">Select depo and role.</td>
    </tr>
@endforelse
