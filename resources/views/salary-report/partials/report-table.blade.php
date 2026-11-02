@php($hasReport = $report && $report['processing'] && count($report['rows']))
<div class="d-flex justify-content-between mb-3"><h5>{{ $report ? \App\Support\WageRegister::title($report) : 'Wage Register' }}</h5></div>
@if (!$hasReport)
    <div class="alert alert-warning">No approved salary processing found for the selected filters.</div>
@else
    <div class="salary-report-scroll">
        <table class="table table-bordered salary-report-table">
            <thead>
                <tr>
                    @foreach ($report['groups'] as [$start, $length, $label, $color])
                        <th colspan="{{ $length }}" style="background-color:#{{ $color }}">{{ $label }}</th>
                    @endforeach
                </tr>
                <tr>@foreach ($report['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>@foreach ($row as $value)<td class="{{ is_float($value) ? 'text-end' : '' }}">{{ is_float($value) ? number_format($value, 0) : $value }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($report['warnings'])
        <details class="alert alert-warning mt-3"><summary>Reconciliation notes (approved amounts unchanged)</summary>
            <ul>@foreach ($report['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul>
        </details>
    @endif
@endif
