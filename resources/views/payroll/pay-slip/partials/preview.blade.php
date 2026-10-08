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
    $extraDays = (float) ($item->extra_days_worked ?? 0);
    $grossSalary = (float) ($item->gross_salary ?? $item->basic_salary ?? 0);
    $earnedSalary = (float) ($item->earned_salary ?? 0);
    $extraDutyIncentive = (float) ($item->extra_duty_incentive ?? $item->incentive ?? 0);
    $totalEarned = (float) ($item->total_earned ?? ($earnedSalary + $extraDutyIncentive));
    $pf = (float) ($item->pf ?? 0);
    $professionalTax = (float) ($item->professional_tax ?? 0);
    $esi = (float) ($item->esi ?? 0);
    $totalDeduction = (float) ($item->total_deduction ?? $item->deduction ?? 0);
    $netTotal = (float) ($item->net_total ?? $item->net_salary ?? 0);
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
            <span>Total Days</span>
            <strong>{{ number_format($totalDays, 2) }}</strong>
            <small>{{ number_format($extraDays, 2) }} extra days worked</small>
        </div>
        <div class="pay-slip-net">
            <span>Net Total</span>
            <strong>{{ $money($netTotal) }}</strong>
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
                    <dt>Extra Days Worked</dt>
                    <dd>{{ number_format($extraDays, 2) }}</dd>
                </div>
                <div>
                    <dt>Salary Per Day</dt>
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
                <strong>{{ $money($grossSalary) }}</strong>
            </div>
            <div class="pay-slip-total-row"><span>Earned Salary</span><strong>{{ $money($earnedSalary) }}</strong>
            </div>
            <div class="pay-slip-total-row"><span>Extra Duty Incentive</span><strong>{{ $money($extraDutyIncentive) }}</strong></div>
            <div class="pay-slip-total-row"><span>Total Earned</span><strong>{{ $money($totalEarned) }}</strong></div>
            <div class="pay-slip-total-row"><span>PF</span><strong>{{ $money($pf) }}</strong></div>
            <div class="pay-slip-total-row"><span>Professional Tax</span><strong>{{ $money($professionalTax) }}</strong></div>
            <div class="pay-slip-total-row"><span>ESI</span><strong>{{ $money($esi) }}</strong></div>
            <div class="pay-slip-total-row">
                <span>Total Deduction</span>
                <strong>{{ $money($totalDeduction) }}</strong>
            </div>
            <div class="pay-slip-total-row pay-slip-grand-total">
                <span>Net Total</span>
                <strong>{{ $money($netTotal) }}</strong>
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
