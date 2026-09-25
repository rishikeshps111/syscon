@php
    $money = fn($value) => number_format((float) $value, 2);
    $components = collect($item->salary_split ?: []);
    $earnings = $components->where('type', 'earning')->values();
    $deductions = $components->where('type', 'deduction')->values();
    $status = $processing->status ?: 'Pending';
    $statusClass = $status === 'Approved' ? 'success' : 'warning';
    $totalDays = (float) ($item->total_attendance_days ?? $item->total_working_days ?? 0);
    $presentDays = (float) ($item->present_days ?? 0);
    $weekOffDays = (float) ($item->week_off_days ?? 0);
    $absentDays = (float) ($item->absent_days ?? 0);
    $lopDays = (float) ($item->unauthorized_leaves ?? 0);
    $workedDays = (float) ($item->actual_worked_days ?? max($presentDays - $lopDays, 0));
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
            <small>{{ $monthName }} payroll</small>
        </div>
    </div>

    <div class="pay-slip-summary-strip">
        <div>
            <span>Employee</span>
            <strong>{{ $item->user?->name ?: '-' }}</strong>
            <small>{{ $item->user?->code ?: 'No code' }}</small>
        </div>
        <div>
            <span>Actual Worked Days</span>
            <strong>{{ number_format($workedDays, 2) }}</strong>
            <small>{{ number_format($lopDays, 2) }} LOP days</small>
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
                <div>
                    <dt>Account Number</dt>
                    <dd>{{ $bankDetails['account_number'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt>IFSC Code</dt>
                    <dd>{{ $bankDetails['ifsc_code'] ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="pay-slip-panel">
            <div class="pay-slip-panel-title">Attendance</div>
            <dl class="pay-slip-detail-list">
                <div>
                    <dt>Total Days</dt>
                    <dd>{{ number_format($totalDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Present Days</dt>
                    <dd>{{ number_format($presentDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Week-off Days</dt>
                    <dd>{{ number_format($weekOffDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Absent Days</dt>
                    <dd>{{ number_format($absentDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Actual Worked Days</dt>
                    <dd>{{ number_format($workedDays, 2) }}</dd>
                </div>
                <div>
                    <dt>LOP Days</dt>
                    <dd>{{ number_format($lopDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Per-day Salary</dt>
                    <dd>₹{{ $money($item->salary_day_rate) }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="pay-slip-grid pay-slip-grid-wide">
        <div class="pay-slip-panel">
            <div class="pay-slip-panel-title">Salary Template Components</div>
            <div class="table-responsive">
                <table class="table pay-slip-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($earnings as $component)
                            <tr>
                                <td>{{ $component['name'] ?? 'Component' }}</td>
                                <td>{{ ucfirst($component['type'] ?? 'earning') }}</td>
                                <td class="text-end">{{ $money($component['amount'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No salary template components found.</td>
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
            <div class="pay-slip-total-row"><span>Template
                    Deductions</span><strong>{{ $money($item->template_deduction ?? $deductions->sum('amount')) }}</strong>
            </div>
            <div class="pay-slip-total-row"><span>LOP
                    Deduction</span><strong>{{ $money($item->lop_deduction ?? $item->lop) }}</strong></div>
            <div class="pay-slip-total-row">
                <span>Total Deduction</span>
                <strong>{{ $money($item->deduction) }}</strong>
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