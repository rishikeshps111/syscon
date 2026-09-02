@section('title')
    {{ isset($record) ? 'Edit Shift-Based Leave' : 'Shift-Based Leave' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Shift-Based Leave</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('leaves.index') }}">Leave Management</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form class="js-loading-form" method="POST" enctype="multipart/form-data"
            action="{{ isset($record) ? route('leaves.update', $record->id) : route('leaves.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif
            <input type="hidden" name="leave_for" value="driver">

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container mb-3">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="code">Leave Code</label>
                                <input type="text" id="code" class="form-control shadow-none"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="user_id">Driver / Housekeeping Employee <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" class="form-select shadow-none select2 @error('user_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" @selected(old('user_id', $record->user_id ?? '') == $driver->id)>{{ trim(($driver->code ? $driver->code . ' - ' : '') . $driver->name) }}</option>
                                    @endforeach
                                </select>
                                @error('user_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="leave_type_id">Leave Type <span class="text-danger">*</span></label>
                                <select id="leave_type_id" name="leave_type_id" class="form-select shadow-none select2 @error('leave_type_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($driverLeaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" @selected(old('leave_type_id', $record->leave_type_id ?? '') == $leaveType->id)>{{ $leaveType->short_name ?: $leaveType->leave_name }}</option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="from_date">From Date <span class="text-danger">*</span></label>
                                <input type="date" id="from_date" name="from_date" class="form-control shadow-none @error('from_date') is-invalid @enderror"
                                    value="{{ old('from_date', isset($record) && $record->from_date ? $record->from_date->format('Y-m-d') : ($record->leave_date ?? null)?->format('Y-m-d')) }}">
                                @error('from_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="to_date">To Date <span class="text-danger">*</span></label>
                                <input type="date" id="to_date" name="to_date" class="form-control shadow-none @error('to_date') is-invalid @enderror"
                                    value="{{ old('to_date', isset($record) && $record->to_date ? $record->to_date->format('Y-m-d') : ($record->leave_date ?? null)?->format('Y-m-d')) }}">
                                @error('to_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="day_type">Day Type <span class="text-danger">*</span></label>
                                <select id="day_type" name="day_type" class="form-select shadow-none @error('day_type') is-invalid @enderror">
                                    @foreach($dayTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('day_type', $record->day_type ?? 'full_day') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('day_type')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="number_of_days">Number of Days <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0.5" id="number_of_days" name="number_of_days" readonly
                                    class="form-control shadow-none @error('number_of_days') is-invalid @enderror"
                                    value="{{ old('number_of_days', $record->number_of_days ?? '') }}">
                                @error('number_of_days')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="shift">Shift <span class="text-danger">*</span></label>
                                <select id="shift" name="shift" class="form-select shadow-none @error('shift') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($shifts as $value => $label)
                                        <option value="{{ $value }}" @selected(old('shift', $record->shift ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('shift')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="assigned_vehicle_route">Assigned Work Area / Vehicle / Route <span class="text-danger">*</span></label>
                                <input type="text" id="assigned_vehicle_route" name="assigned_vehicle_route"
                                    class="form-control shadow-none @error('assigned_vehicle_route') is-invalid @enderror"
                                    value="{{ old('assigned_vehicle_route', $record->assigned_vehicle_route ?? '') }}">
                                @error('assigned_vehicle_route')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-12 mb-3">
                                <div class="leave-balance-box" id="leave_balance_box">
                                    Select an employee to view financial year leave usage.
                                </div>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select shadow-none @error('status') is-invalid @enderror">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $record->status ?? 'Pending') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-12 o-f-inp mb-3">
                                <label for="reason">Reason <span class="text-danger">*</span></label>
                                <textarea id="reason" name="reason" class="form-control shadow-none @error('reason') is-invalid @enderror">{{ old('reason', $record->reason ?? '') }}</textarea>
                                @error('reason')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 ">
                        <div class="modal-btns-last">
                            <a href="{{ route('leaves.index') }}" class="modal-btn-1">Cancel</a>
                            <button type="submit" class="modal-btn-2 js-loading-submit" data-loading-text="Saving...">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({ width: '100%', placeholder: '---Select---', allowClear: true });

                var leaveTypes = @json($driverLeaveTypes->keyBy('id')->map(fn ($leaveType) => [
                    'allow_half_day' => (bool) $leaveType->allow_half_day,
                ]));
                var balanceUrl = @json(route('leaves.balances'));
                var excludeLeaveId = @json($record->id ?? null);
                var balanceRequest = null;

                function formatDays(value) {
                    if (value === null || value === undefined || value === '') {
                        return '-';
                    }

                    return Number(value).toFixed(2).replace(/\.?0+$/, '');
                }

                function calculateDays() {
                    var fromDate = $('#from_date').val();
                    var toDate = $('#to_date').val();
                    var dayType = $('#day_type').val();

                    if (! fromDate || ! toDate) {
                        $('#number_of_days').val('');
                        return;
                    }

                    var from = new Date(fromDate + 'T00:00:00');
                    var to = new Date(toDate + 'T00:00:00');

                    if (to < from) {
                        $('#number_of_days').val('');
                        return;
                    }

                    if (dayType === 'half_day') {
                        $('#number_of_days').val('0.5');
                        if (fromDate !== toDate) {
                            $('#to_date').val(fromDate);
                        }
                        return;
                    }

                    var millisecondsPerDay = 24 * 60 * 60 * 1000;
                    $('#number_of_days').val(Math.round((to - from) / millisecondsPerDay) + 1);
                }

                function syncHalfDayOption() {
                    var leaveType = leaveTypes[$('#leave_type_id').val()];
                    if ($('#day_type').val() === 'half_day' && leaveType && ! leaveType.allow_half_day) {
                        $('#day_type').val('full_day');
                    }

                    calculateDays();
                }

                function renderBalances(response) {
                    if (! response || ! response.balances || ! response.balances.length) {
                        $('#leave_balance_box').text('No leave balance found for the selected driver.');
                        return;
                    }

                    var selectedLeaveTypeId = Number($('#leave_type_id').val());
                    var rows = response.balances.map(function (balance) {
                        var selected = selectedLeaveTypeId === Number(balance.leave_type_id);
                        var limit = balance.limit === null ? 'No yearly limit' : formatDays(balance.limit);
                        var usedWithCurrentLeave = Number(balance.used || 0) + Number(balance.requested || 0);
                        var remaining = balance.remaining_after_request === null ? '-' : formatDays(balance.remaining_after_request);

                        return '<div class="leave-balance-row' + (selected ? ' selected' : '') + '">' +
                            '<div class="leave-balance-name">' + balance.label + (selected ? '<span>Selected</span>' : '') + '</div>' +
                            '<div class="leave-balance-metrics">' +
                                '<div><small>Limit</small><strong>' + limit + '</strong></div>' +
                                '<div><small>Used</small><strong>' + formatDays(usedWithCurrentLeave) + '</strong></div>' +
                                '<div><small>Remaining</small><strong>' + remaining + '</strong></div>' +
                            '</div>' +
                        '</div>';
                    });

                    $('#leave_balance_box').html(
                        '<div class="leave-balance-header">' +
                            '<span>Financial Year</span>' +
                            '<strong>' + response.financial_year.from + ' to ' + response.financial_year.to + '</strong>' +
                        '</div>' +
                        '<div class="leave-balance-grid">' + rows.join('') + '</div>'
                    );
                }

                function loadBalances() {
                    var userId = $('#user_id').val();

                    if (! userId) {
                        $('#leave_balance_box').text('Select a driver to view financial year leave usage.');
                        return;
                    }

                    if (balanceRequest) balanceRequest.abort();
                    var requestedUserId = userId;
                    balanceRequest = $.get(balanceUrl, {
                        user_id: userId,
                        leave_for: 'driver',
                        leave_type_id: $('#leave_type_id').val(),
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        day_type: $('#day_type').val(),
                        exclude_leave_id: excludeLeaveId
                    }).done(function (response) {
                        if (String($('#user_id').val()) === String(requestedUserId)) renderBalances(response);
                    }).fail(function (xhr) {
                        if (xhr.statusText !== 'abort') $('#leave_balance_box').text('Unable to load leave usage.');
                    });
                }

                $('#from_date, #to_date, #day_type').on('change', function () {
                    calculateDays();
                    loadBalances();
                });
                $('#leave_type_id').on('change', function () {
                    syncHalfDayOption();
                    loadBalances();
                });
                $('#user_id').on('change', loadBalances);

                syncHalfDayOption();
                loadBalances();

                $('.js-loading-form').on('submit', function () {
                    $(this).find('.js-loading-submit').prop('disabled', true).html('Saving...');
                });
            });
        </script>
        <style>
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
