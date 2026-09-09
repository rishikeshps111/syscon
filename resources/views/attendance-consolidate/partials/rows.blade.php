<div class="table-responsive">
    <table class="table tble-cstm align-middle">
        <thead><tr>@if($showReview ?? false)<th>File row</th>@endif<th>Emp Id</th><th>Name Of The Employee</th><th>P</th><th>W/O</th><th>A</th><th>Total</th>@if($showReview ?? false)<th>Review notes</th>@endif</tr></thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                @if($showReview ?? false)<td>{{ $row['source_row'] }}</td>@endif<td>{{ $row['employee_ref_code'] }}</td><td>{{ $row['employee_name'] }}</td>
                <td>{{ $row['present_days'] }}</td><td>{{ $row['week_off_days'] }}</td><td>{{ $row['absent_days'] }}</td><td>{{ $row['total_days'] }}</td>
                @if($showReview ?? false)<td>@foreach($row['warnings'] ?? [] as $warning)<div class="text-warning-emphasis">{{ $warning }}</div>@endforeach</td>@endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
