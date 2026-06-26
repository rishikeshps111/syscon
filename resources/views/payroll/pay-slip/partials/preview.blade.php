@php
    $money = fn ($value) => number_format((float) $value, 2);
    $components = collect($item->salary_split ?: [])->where('type', 'earning')->values();
    $status = $processing->status ?: 'Pending';
    $statusClass = $status === 'Approved' ? 'success' : 'warning';
@endphp

<div class="pay-slip-preview">
    <div class="pay-slip-header">
        <div>
            <div class="pay-slip-brand">SYSCON</div>
            <h4>Pay Slip</h4>
            <p>{{ $monthName }} {{ $processing->year }}</p>
        </div>
        <div class="pay-slip-header-meta">
            <span class="pay-slip-status pay-slip-status-{{ $statusClass }}">{{ $status }}</span>
            <strong>{{ $processing->depot?->name ?? '-' }}</strong>
            <small>{{ $processing->role?->name ?? '-' }}</small>
        </div>
    </div>

    <div class="pay-slip-summary-strip">
        <div>
            <span>Employee</span>
            <strong>{{ $item->user?->name ?: '-' }}</strong>
            <small>{{ $item->user?->code ?: 'No code' }}</small>
        </div>
        <div>
            <span>Total Working Days</span>
            <strong>{{ $item->total_working_days }}</strong>
            <small>{{ $item->total_shifts_completed }} shifts completed</small>
        </div>
        <div class="pay-slip-net">
            <span>Net Salary</span>
            <strong>{{ $money($item->net_salary) }}</strong>
            <small>INR</small>
        </div>
    </div>

    <div class="pay-slip-grid">
        <div class="pay-slip-panel">
            <div class="pay-slip-panel-title">Employee Details</div>
            <dl class="pay-slip-detail-list">
                <div>
                    <dt>Employee Code</dt>
                    <dd>{{ $item->user?->code ?: '-' }}</dd>
                </div>
                <div>
                    <dt>Name</dt>
                    <dd>{{ $item->user?->name ?: '-' }}</dd>
                </div>
                <div>
                    <dt>Aadhaar No</dt>
                    <dd>{{ $item->aadhaar_no ?: '-' }}</dd>
                </div>
                <div>
                    <dt>Depo</dt>
                    <dd>{{ $processing->depot?->name ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="pay-slip-panel">
            <div class="pay-slip-panel-title">Attendance</div>
            <dl class="pay-slip-detail-list">
                <div>
                    <dt>Total Working Days</dt>
                    <dd>{{ $item->total_working_days }}</dd>
                </div>
                <div>
                    <dt>Total Leave Taken</dt>
                    <dd>{{ $item->total_leave_taken }}</dd>
                </div>
                <div>
                    <dt>Unauthorized Leaves</dt>
                    <dd>{{ $item->unauthorized_leaves }}</dd>
                </div>
                <div>
                    <dt>Total Shifts Completed</dt>
                    <dd>{{ $item->total_shifts_completed }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="pay-slip-grid pay-slip-grid-wide">
        <div class="pay-slip-panel">
            <div class="pay-slip-panel-title">Earnings</div>
            <div class="table-responsive">
                <table class="table pay-slip-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($components as $component)
                            <tr>
                                <td>{{ $component['name'] ?? 'Component' }}</td>
                                <td class="text-end">{{ $money($component['amount'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No earning components found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pay-slip-panel pay-slip-totals">
            <div class="pay-slip-panel-title">Salary Summary</div>
            <div class="pay-slip-total-row">
                <span>Gross Salary</span>
                <strong>{{ $money($item->basic_salary) }}</strong>
            </div>
            <div class="pay-slip-total-row">
                <span>Incentive</span>
                <strong>{{ $money($item->incentive) }}</strong>
            </div>
            <div class="pay-slip-total-row">
                <span>Deduction</span>
                <strong>{{ $money($item->deduction) }}</strong>
            </div>
            <div class="pay-slip-total-row">
                <span>LOP</span>
                <strong>{{ $money($item->lop) }}</strong>
            </div>
            <div class="pay-slip-total-row pay-slip-grand-total">
                <span>Net Salary</span>
                <strong>{{ $money($item->net_salary) }}</strong>
            </div>
        </div>
    </div>

    <div class="pay-slip-panel pay-slip-payment">
        <div class="pay-slip-panel-title">Payment & Approval</div>
        <dl class="pay-slip-detail-list pay-slip-payment-list">
            <div>
                <dt>Payment Method</dt>
                <dd>{{ $processing->payment_method ?: '-' }}</dd>
            </div>
            <div>
                <dt>Approved By</dt>
                <dd>{{ $processing->approver?->name ?: '-' }}</dd>
            </div>
            <div>
                <dt>Approved At</dt>
                <dd>{{ $processing->approved_at?->format('d-m-Y h:i A') ?: '-' }}</dd>
            </div>
            <div>
                <dt>Remarks</dt>
                <dd>{{ $processing->remarks ?: '-' }}</dd>
            </div>
        </dl>
    </div>
</div>
