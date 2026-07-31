@php($money = fn ($value) => number_format((float) $value, 2))
<div class="row mb-3">
    @foreach([
        'Period' => \Carbon\Carbon::create($processing->year, $processing->month, 1)->format('F Y'),
        'Depo' => $processing->depot?->name ?? '-',
        'Role' => $processing->role?->name ?? '-',
        'Salary Date' => $processing->salary_date?->format('d-m-Y') ?? '-',
        'Payment Method' => $processing->payment_method ?? '-',
        'Created By' => $processing->creator?->name ?? '-',
        'Approved By' => $processing->approver?->name ?? '-',
        'Approved At' => $processing->approved_at?->format('d-m-Y h:i A') ?? '-',
    ] as $label => $value)
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="border rounded p-3 h-100">
                <small class="text-muted d-block mb-1">{{ $label }}</small>
                <strong>{{ $value }}</strong>
            </div>
        </div>
    @endforeach
</div>

@if($processing->remarks)
    <div class="alert alert-light border"><strong>Remarks:</strong> {{ $processing->remarks }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Component Details</th>
                <th class="text-end">Gross</th>
                <th class="text-end">Deduction</th>
                <th class="text-end">Incentive</th>
                <th class="text-end">LOP</th>
                <th class="text-end">Net Salary</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->user?->name ?? '-' }}</strong>
                        <small class="text-muted d-block">{{ $item->user?->code ?? '-' }}</small>
                        @if($processing->role?->name === 'Staff')
                            <small class="text-muted d-block">{{ $item->user?->staffProfile?->designation?->name ?? '-' }}</small>
                        @endif
                        <small class="text-muted d-block">Aadhaar: {{ $item->aadhaar_no ?? '-' }}</small>
                    </td>
                    <td>
                        @forelse(collect($item->salary_split ?: [])->where('selected', '!==', false) as $component)
                            <div class="d-flex justify-content-between gap-3 border-bottom py-1">
                                <span>
                                    {{ $component['name'] ?? 'Component' }}
                                    <small class="text-muted">({{ ucfirst($component['type'] ?? 'earning') }})</small>
                                </span>
                                <strong>{{ $money($component['amount'] ?? 0) }}</strong>
                            </div>
                        @empty
                            <span class="text-muted">No component details</span>
                        @endforelse
                    </td>
                    <td class="text-end">{{ $money($item->basic_salary) }}</td>
                    <td class="text-end">{{ $money($item->deduction) }}</td>
                    <td class="text-end">{{ $money($item->incentive) }}</td>
                    <td class="text-end">{{ $money($item->lop) }}</td>
                    <td class="text-end"><strong>{{ $money($item->net_salary) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No employee salary records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
