<div class="table-responsive">
    <table class="table tble-cstm align-middle">
        <thead>
            <tr>
                @if($showReview ?? false)
                    <th>File row</th>
                @endif
                <th>Sl No</th>
                <th>Emp Id</th>
                <th>Name Of The Employee</th>
                <th>P</th>
                <th>W/O</th>
                <th>A</th>
                <th>Total</th>
                <th>LOP Days</th>
                <th>Actual Worked Days</th>@if($showReview ?? false)
                <th>Review notes</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @if($showReview ?? false)
                        <td>{{ $row['source_row'] }}</td>
                    @endif
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['employee_ref_code'] }}</td>
                    <td>{{ $row['employee_name'] }}</td>
                    @php
                        $presentDays = (float) $row['present_days'];
                        $weekOffDays = (float) $row['week_off_days'];
                        $absentDays = (float) $row['absent_days'];
                        $lopDays = max($absentDays - $weekOffDays, 0);
                        $actualWorkedDays = max($presentDays - $lopDays, 0);
                    @endphp
                    <td>{{ number_format($presentDays, 2) }}</td>
                    <td>{{ number_format($weekOffDays, 2) }}</td>
                    <td>{{ number_format($absentDays, 2) }}</td>
                    <td>{{ number_format((float) $row['total_days'], 2) }}</td>
                    <td>{{ number_format($lopDays, 2) }}</td>
                    <td>{{ number_format($actualWorkedDays, 2) }}</td>
                    @if($showReview ?? false)
                        <td>@foreach($row['warnings'] ?? [] as $warning)
                        <div class="text-warning-emphasis">{{ $warning }}</div>@endforeach
                    </td>@endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>