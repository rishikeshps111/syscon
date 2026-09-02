@php
    $title = $mode === 'manage' ? 'Manage Attendance' : 'Add Attendance';
    $submitRoute = $mode === 'manage' ? route('attendance-management.update') : route('attendance-management.store');
    $selectorRoute = $mode === 'manage'
        ? route('attendance-management.manage', ['year' => $year, 'month' => $month])
        : route('attendance-management.create');
    $selectedDate = $attendanceDate?->format('Y-m-d') ?? '';
    $firstDate = sprintf('%04d-%02d-01', $year, $month);
    $lastDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
    $calendarMarkedDates = $recordedDates ?? collect();
@endphp
@section('title')
    {{ $title }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $title }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance-management.index') }}">Attendance Management</a></li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <form id="attendanceSelectorForm" method="GET" action="{{ $selectorRoute }}">
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="yearSelector">Choose Year <span class="text-danger">*</span></label>
                            <select id="yearSelector" name="year" class="form-select shadow-none attendance-selector">
                                @foreach($years as $optionYear)
                                    <option value="{{ $optionYear }}" {{ (int) $year === (int) $optionYear ? 'selected' : '' }}>{{ $optionYear }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="monthSelector">Select Month <span class="text-danger">*</span></label>
                            <select id="monthSelector" name="month" class="form-select shadow-none attendance-selector">
                                @foreach($months as $value => $label)
                                    <option value="{{ $value }}" {{ (int) $month === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="dateSelector">Date <span class="text-danger">*</span></label>
                            <input type="text" id="dateSelector" name="attendance_date" value="{{ $selectedDate }}"
                                class="form-control shadow-none attendance-selector" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="userTypeSelector">User Type <span class="text-danger">*</span></label>
                            <select id="userTypeSelector" name="user_type" class="form-select shadow-none attendance-selector">
                                <option value="">---Select---</option>
                                @foreach($roles as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}" @selected(($selectedRole ?? '') === $roleValue)>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12 btns-group-container mb-3">
                        <button type="submit" class="add-btn">Load</button>
                    </div>
                </div>
            </form>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="attendanceEntryForm" class="js-loading-form" method="POST" action="{{ $submitRoute }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="user_type" value="{{ $selectedRole }}">
            <input type="hidden" id="attendanceDateHidden" name="attendance_date" value="{{ $selectedDate }}">

            @foreach($roles as $role => $roleLabel)
                @continue(($selectedRole ?? null) !== $role)
                @php
                    $roleUsers = $usersByRole->get($role, collect());
                @endphp
                <div class="main-table-container mb-4">
                    <h5 class="mb-3">{{ $roleLabel }}</h5>
                    <div class="table-over">
                        <table class="align-middle mb-0 table tble-cstm attendance-entry-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="text-center">User</th>
                                    <th class="text-center">Status <span class="text-danger">*</span></th>
                                    <th class="text-center">Half Day <span class="text-danger">*</span></th>
                                    @if(in_array($role, ['Driver', 'Housekeeping'], true))
                                        <th class="text-center">Shift <span class="text-danger">*</span></th>
                                    @endif
                                    @if($role === 'Driver')
                                        <th class="text-center duty-type-column">Duty Type <span class="text-danger">*</span></th>
                                    @endif
                                    <th class="text-center">Leave Application</th>
                                    <th class="text-center">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roleUsers as $user)
                                    @php
                                        $attendance = $attendances->get($user->id);
                                        $userLeaves = $leavesByUser->get($user->id, collect());
                                        $status = old("attendance.{$user->id}.status", $attendance?->status ?? 'present');
                                    @endphp
                                    <tr class="attendance-row">
                                        <td>
                                            <input type="hidden" name="attendance[{{ $user->id }}][user_id]" value="{{ $user->id }}">
                                            <input type="text" class="form-control shadow-none" value="{{ trim(($user->code ? $user->code . ' - ' : '') . $user->name) }}" readonly>
                                        </td>
                                        <td>
                                            <select name="attendance[{{ $user->id }}][status]" class="form-select shadow-none attendance-status">
                                                @foreach($statuses as $value => $label)
                                                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="attendance[{{ $user->id }}][half_day_period]" class="form-select shadow-none half-day-period">
                                                <option value="">---Select---</option>
                                                @foreach($halfDayPeriods as $value => $label)
                                                    <option value="{{ $value }}" {{ old("attendance.{$user->id}.half_day_period", $attendance?->half_day_period) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        @if(in_array($role, ['Driver', 'Housekeeping'], true))
                                            <td>
                                                <select name="attendance[{{ $user->id }}][shift]" class="form-select shadow-none">
                                                    <option value="">---Select---</option>
                                                    @foreach($shifts as $value => $label)
                                                        <option value="{{ $value }}" {{ old("attendance.{$user->id}.shift", $attendance?->shift) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endif
                                        @if($role === 'Driver')
                                            <td class="duty-type-column">
                                                <select name="attendance[{{ $user->id }}][duty_type]" class="form-select shadow-none duty-type">
                                                    <option value="">---Select---</option>
                                                    @foreach($dutyTypes as $value => $label)
                                                        <option value="{{ $value }}" {{ old("attendance.{$user->id}.duty_type", $attendance?->duty_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endif
                                        <td>
                                            <select name="attendance[{{ $user->id }}][leave_id]" class="form-select shadow-none attendance-leave">
                                                <option value="">---Select---</option>
                                                @foreach($userLeaves as $leave)
                                                    <option value="{{ $leave->id }}" {{ (int) old("attendance.{$user->id}.leave_id", $attendance?->leave_id) === (int) $leave->id ? 'selected' : '' }}>
                                                        {{ $leave->code ?: '#' . $leave->id }} -
                                                        {{ $leave->leave_for === 'driver' ? $leave->driver_leave_type : ($leave->leaveType?->leave_name ?? 'Leave') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="attendance[{{ $user->id }}][remarks]" class="form-control shadow-none" rows="1">{{ old("attendance.{$user->id}.remarks", $attendance?->remarks) }}</textarea>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($role === 'Driver' ? 7 : (in_array($role, ['Driver', 'Housekeeping'], true) ? 6 : 5)) }}" class="text-center">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="modal-btns-last">
                <a href="{{ route('attendance-management.index') }}" class="modal-btn-1">Back</a>
                <button type="submit" class="modal-btn-2 js-loading-submit">Submit</button>
            </div>
        </form>

        @if(! $selectedRole)
            <div class="main-table-container mt-3 text-center">
                Please select user type to load users.
            </div>
        @endif
    </section>

    @section('scripts')
        <style>
            .daterangepicker td.attendance-date-marked-red:not(.off) {
                color: #dc3545 !important;
                font-weight: 700;
            }

            .daterangepicker td.attendance-date-marked-green:not(.off) {
                color: #198754 !important;
                font-weight: 700;
            }

            .daterangepicker td.attendance-date-disabled {
                color: #adb5bd !important;
                text-decoration: line-through;
            }
        </style>
        <script>
            $(function () {
                var mode = @json($mode);
                var markedDates = @json($calendarMarkedDates->values());
                var markedDateSet = {};
                var firstDate = @json($firstDate);
                var lastDate = @json($lastDate);
                var selectedDate = @json($selectedDate);

                markedDates.forEach(function (date) {
                    markedDateSet[date] = true;
                });

                $('#yearSelector, #monthSelector, #userTypeSelector').select2({ width: '100%' });

                $('#dateSelector').daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    autoApply: true,
                    showDropdowns: false,
                    minDate: moment(firstDate),
                    maxDate: moment(lastDate),
                    startDate: selectedDate ? moment(selectedDate) : moment(firstDate),
                    locale: {
                        format: 'YYYY-MM-DD'
                    },
                    isInvalidDate: function (date) {
                        return mode === 'manage' && !markedDateSet[date.format('YYYY-MM-DD')];
                    },
                    isCustomDate: function (date) {
                        var value = date.format('YYYY-MM-DD');

                        if (!markedDateSet[value]) {
                            return mode === 'manage' ? 'attendance-date-disabled' : '';
                        }

                        return mode === 'manage' ? 'attendance-date-marked-green' : 'attendance-date-marked-red';
                    }
                });

                $('#dateSelector').on('apply.daterangepicker', function (event, picker) {
                    var value = picker.startDate.format('YYYY-MM-DD');

                    if (mode === 'create' && markedDateSet[value]) {
                        $(this).val('');
                        $('#attendanceDateHidden').val('');
                        showToast('warning', 'Attendance already marked.');
                        return;
                    }

                    if (mode === 'manage' && !markedDateSet[value]) {
                        $(this).val(selectedDate);
                        $('#attendanceDateHidden').val(selectedDate);
                        showToast('warning', 'Please select a marked attendance date.');
                        return;
                    }

                    $(this).val(value);
                    $('#attendanceDateHidden').val(value);
                });

                $('#dateSelector').on('showCalendar.daterangepicker', function () {
                    if (mode === 'create') {
                        $('.daterangepicker td.attendance-date-marked-red').off('click.attendanceMarked').on('click.attendanceMarked', function () {
                            showToast('warning', 'Attendance already marked.');
                        });
                    }
                });

                function syncLeaveFields() {
                    $('.attendance-row').each(function () {
                        var row = $(this);
                        var status = row.find('.attendance-status').val();
                        var leave = row.find('.attendance-leave');
                        var halfDayPeriod = row.find('.half-day-period');
                        var dutyType = row.find('.duty-type');
                        var needsLeave = status === 'absent' || status === 'half_day';
                        var needsHalfDayPeriod = status === 'half_day';
                        var needsDutyType = dutyType.length && status === 'present';

                        leave.prop('disabled', !needsLeave);
                        halfDayPeriod.prop('disabled', !needsHalfDayPeriod);
                        dutyType.prop('disabled', !needsDutyType);
                        row.find('.duty-type-column').toggle(!!needsDutyType);

                        if (!needsLeave) {
                            leave.val('');
                        }

                        if (!needsHalfDayPeriod) {
                            halfDayPeriod.val('');
                        }
                        if (!needsDutyType) {
                            dutyType.val('');
                        }
                    });
                }

                $('.attendance-status').on('change', syncLeaveFields);
                syncLeaveFields();

                $('#attendanceSelectorForm').on('submit', function (event) {
                    var selected = $('#dateSelector').val();

                    if (mode === 'create' && selected && markedDateSet[selected]) {
                        event.preventDefault();
                        $('#dateSelector').val('');
                        $('#attendanceDateHidden').val('');
                        showToast('warning', 'Attendance already marked.');
                    }
                });

                $('#attendanceEntryForm').on('submit', function (event) {
                    if (!$('#attendanceDateHidden').val()) {
                        event.preventDefault();
                        showToast('warning', 'Please select attendance date.');
                        return;
                    }

                    if (!@json((bool) $selectedRole)) {
                        event.preventDefault();
                        showToast('warning', 'Please select user type.');
                        return;
                    }

                    $(this).find('.js-loading-submit').prop('disabled', true).html('Loading...');
                });

                $('#yearSelector, #monthSelector, #userTypeSelector').on('change', function () {
                    var year = $('#yearSelector').val();
                    var month = $('#monthSelector').val();
                    var targetUrl = @json($mode === 'manage'
                        ? route('attendance-management.manage', ['year' => '__YEAR__', 'month' => '__MONTH__'])
                        : route('attendance-management.create'));

                    $('#dateSelector').val('');
                    $('#attendanceDateHidden').val('');

                    if (mode === 'manage') {
                        targetUrl = targetUrl.replace('__YEAR__', year).replace('__MONTH__', month);
                        $('#attendanceSelectorForm').attr('action', targetUrl);
                    }

                    $('#attendanceSelectorForm').trigger('submit');
                });
            });
        </script>
    @endsection
</x-app-layout>
