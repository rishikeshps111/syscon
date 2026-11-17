@if (! $report || ! count($report['rows']))
    <div class="alert alert-warning">No approved PF contributors found for the selected filters.</div>
@else
    <h5>{{ $report['depot']?->name }} — {{ $report['monthName'] }} {{ $report['year'] }} — PF File ({{ $report['roleLabel'] }})</h5>
    <div class="table-responsive">
        <table class="table table-bordered tble-cstm">
            <thead><tr>@foreach ($report['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $column => $value)
                            <td>{{ $value === null ? '' : ($column === 5 ? \App\Support\ShiftQuantity::format($value) : ($column >= 2 ? number_format($value, 0) : $value)) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($report['warnings'])
        <div class="alert alert-warning"><ul class="mb-0">@foreach ($report['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>
    @endif
@endif
