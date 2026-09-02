@section('title')
    View Leave
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>View Leave</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('leaves.index') }}">Leave Management</a></li>
                    <li class="breadcrumb-item active">View</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-12 mb-2">
                            <h5 class="title-w-sec">Leave Details</h5>
                        </div>

                        @php
                            $details = [
                                'Leave Code' => $record->code ?: '-',
                                'Leave System' => \App\Models\Leave::TYPES[$record->leave_for] ?? $record->leave_for,
                                'Employee' => $record->user?->name ?? '-',
                                'Role' => $record->user?->roles?->pluck('name')->implode(', ') ?: '-',
                                'Leave Type' => $record->leaveType?->short_name ?: $record->leaveType?->leave_name ?: $record->driver_leave_type ?: '-',
                                'From Date' => $record->from_date?->format('d M Y') ?? $record->leave_date?->format('d M Y') ?? '-',
                                'To Date' => $record->to_date?->format('d M Y') ?? $record->leave_date?->format('d M Y') ?? '-',
                                'Day Type' => \App\Models\Leave::DAY_TYPES[$record->day_type] ?? $record->day_type ?? '-',
                                'Number of Days' => $record->number_of_days !== null ? rtrim(rtrim((string) $record->number_of_days, '0'), '.') : '-',
                                'Status' => $record->status,
                            ];

                            if ($record->leave_for === 'driver') {
                                $details['Shift'] = $record->shift ?: '-';
                                $details['Assigned Vehicle / Route'] = $record->assigned_vehicle_route ?: '-';
                            }
                        @endphp

                        @foreach ($details as $label => $value)
                            <div class="col-lg-3 o-f-inp mb-3">
                                <label>{{ $label }}</label>
                                <div class="value-leave-dt">{{ $value }}</div>
                            </div>
                        @endforeach

                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3">
                            <label>Reason</label>
                            <div class="value-leave-dt min-h-box">{{ $record->reason ?: '-' }}</div>
                        </div>
                        <div class="col-lg-6 o-f-inp mb-3">
                            <label>Remarks</label>
                            <div class="value-leave-dt min-h-box">{{ $record->remarks ?: '-' }}</div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="leave-balance-box">
                        <div class="leave-balance-header">
                            <span>Financial Year</span>
                            <strong>{{ $financialYearStart->toDateString() }} to {{ $financialYearEnd->toDateString() }}</strong>
                        </div>
                        <div class="leave-balance-grid">
                            @forelse ($balances as $balance)
                                <div class="leave-balance-row {{ $record->leave_type_id === $balance['leave_type_id'] ? 'selected' : '' }}">
                                    <div class="leave-balance-name">
                                        {{ $balance['label'] }}
                                        @if ($record->leave_type_id === $balance['leave_type_id'])
                                            <span>Selected</span>
                                        @endif
                                    </div>
                                    <div class="leave-balance-metrics">
                                        <div>
                                            <small>Limit</small>
                                            <strong>{{ $balance['limit'] === null ? 'No yearly limit' : rtrim(rtrim(number_format($balance['limit'], 2), '0'), '.') }}</strong>
                                        </div>
                                        <div>
                                            <small>Used</small>
                                            <strong>{{ rtrim(rtrim(number_format($balance['used'], 2), '0'), '.') }}</strong>
                                        </div>
                                        <div>
                                            <small>Available</small>
                                            <strong>{{ $balance['remaining'] === null ? '-' : rtrim(rtrim(number_format($balance['remaining'], 2), '0'), '.') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div>No leave balance found for this user.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 ">
                <div class=" btns-group-container">
                    <a href="{{ route('leaves.index') }}" class="bk-btn">Back</a>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <style>
            .min-h-box {
                min-height: 90px;
                white-space: pre-wrap;
            }

            .leave-balance-box {
                border: 1px solid #d7e3f5;
                background: #f8fbff;
                border-radius: 8px;
                padding: 12px;
                color: #344767;
            }

            .leave-balance-header {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
                padding-bottom: 10px;
                margin-bottom: 10px;
                border-bottom: 1px solid #e2e8f0;
            }

            .leave-balance-header span,
            .leave-balance-metrics small {
                font-size: 12px;
                color: #64748b;
            }

            .leave-balance-header strong {
                font-size: 13px;
                color: #0f172a;
            }

            .leave-balance-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 8px;
            }

            .leave-balance-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid #e5e7eb;
                background: #fff;
                border-radius: 8px;
                padding: 10px 12px;
            }

            .leave-balance-row.selected {
                border-color: #2563eb;
                background: #eff6ff;
            }

            .leave-balance-name {
                font-weight: 700;
                color: #111827;
                min-width: 52px;
            }

            .leave-balance-name span {
                display: block;
                width: fit-content;
                margin-top: 4px;
                padding: 2px 6px;
                border-radius: 4px;
                background: #2563eb;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
            }

            .leave-balance-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(54px, 1fr));
                gap: 8px;
                text-align: right;
                flex: 1;
            }

            .leave-balance-metrics strong {
                display: block;
                color: #0f172a;
                font-size: 14px;
                line-height: 1.2;
            }
        </style>
    @endsection
</x-app-layout>
