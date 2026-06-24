@php
    $hasReport = $report && $report['processing'];
    $items = $report['items'] ?? collect();
    $componentNames = $report['componentNames'] ?? collect();
    $money = fn ($value) => number_format((float) $value, 2);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="mb-1">Full Salary Sheet</h5>
        <div class="text-muted">
            <strong>Year:</strong> {{ $report['year'] }} |
            <strong>Month:</strong> {{ $report['monthName'] }} |
            <strong>Depo:</strong> {{ $report['depot']?->name ?? '-' }} |
            <strong>Role:</strong> {{ $report['role']?->name ?? '-' }}
        </div>
    </div>
</div>

@if (! $hasReport)
    <div class="alert alert-warning mb-0">
        No completed salary processing found for the selected Year, Month, Depo and Role.
    </div>
@elseif ($items->isEmpty())
    <div class="alert alert-warning mb-0">
        Salary processing exists, but no completed user salary records are available.
    </div>
@else
    <div class="table-over salary-report-scroll">
        <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent salary-report-table">
            <thead>
                <tr>
                    <th class="text-center">SL No</th>
                    <th class="text-center">User Code</th>
                    <th class="text-center">User Name</th>
                    <th class="text-center">Aadhaar No</th>
                    <th class="text-center">Total Leave Taken</th>
                    <th class="text-center">Unauthorized Leaves</th>
                    <th class="text-center">Total Shifts Completed</th>
                    <th class="text-center">Total Working Days</th>
                    @foreach ($componentNames as $componentName)
                        <th class="text-center">{{ $componentName }}</th>
                    @endforeach
                    <th class="text-center">Gross Salary</th>
                    <th class="text-center">Incentive</th>
                    <th class="text-center">Deduction</th>
                    <th class="text-center">LOP</th>
                    <th class="text-center">Net Salary</th>
                    <th class="text-center">Payment Method</th>
                    <th class="text-center">Approval Status</th>
                    <th class="text-center">Approved By</th>
                    <th class="text-center">Approved At</th>
                    <th class="text-center">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $components = collect($item->salary_split ?: [])->where('type', 'earning')->keyBy('name');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ $item->user?->code ?: '-' }}</td>
                        <td>{{ $item->user?->name ?: '-' }}</td>
                        <td class="text-center">{{ $item->aadhaar_no ?: '-' }}</td>
                        <td class="text-center">{{ $item->total_leave_taken }}</td>
                        <td class="text-center">{{ $item->unauthorized_leaves }}</td>
                        <td class="text-center">{{ $item->total_shifts_completed }}</td>
                        <td class="text-center">{{ $item->total_working_days }}</td>
                        @foreach ($componentNames as $componentName)
                            <td class="text-end">{{ $money($components->get($componentName)['amount'] ?? 0) }}</td>
                        @endforeach
                        <td class="text-end">{{ $money($item->basic_salary) }}</td>
                        <td class="text-end">{{ $money($item->incentive) }}</td>
                        <td class="text-end">{{ $money($item->deduction) }}</td>
                        <td class="text-end">{{ $money($item->lop) }}</td>
                        <td class="text-end">{{ $money($item->net_salary) }}</td>
                        <td class="text-center">{{ $report['processing']->payment_method ?: '-' }}</td>
                        <td class="text-center">{{ $report['processing']->status === 'Approved' ? 'Approved' : 'Pending' }}</td>
                        <td class="text-center">{{ $report['processing']->approver?->name ?: '-' }}</td>
                        <td class="text-center">{{ $report['processing']->approved_at?->format('d-m-Y h:i A') ?: '-' }}</td>
                        <td>{{ $report['processing']->remarks ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="text-muted mt-2">PDF mail recipient: {{ $mailTo }}</div>
@endif
