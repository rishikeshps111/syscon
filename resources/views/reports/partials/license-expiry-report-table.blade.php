@if($rows->isNotEmpty())
    <div class="license-expiry-report-scroll">
        <table class="align-middle mb-0 table tble-cstm license-expiry-report-table">
            <thead>
                <tr class="payroll-table">
                    @foreach($columns as $label)
                        <th class="text-center nowrap">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach(array_keys($columns) as $column)
                            <td class="text-center nowrap">{{ $row[$column] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-5 text-muted">No license expiry records found for the selected filters.</div>
@endif
