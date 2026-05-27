@section('title')
    Print Attendance
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Print Attendance</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance-management.index') }}">Attendance
                            Management</a></li>
                    <li class="breadcrumb-item active">{{ $monthName }} {{ $year }}</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="d-flex justify-content-between gap-2">
                <h6 class="mb-0">{{ $monthName }} {{ $year }}</h6>
                <div>
                    <a class="btn btn-primary exp-btn"
                        href="{{ route('attendance-management.export', ['year' => $year, 'month' => $month] + request()->only(['role', 'user_id', 'status'])) }}">Export
                        Excel</a>
                    <a class="btn btn-danger exp-btn"
                        href="{{ route('attendance-management.pdf', ['year' => $year, 'month' => $month] + request()->only(['role', 'user_id', 'status'])) }}">Download
                        PDF</a>
                    <a class="btn btn-secondary" href="{{ route('attendance-management.index') }}">Back</a>
                </div>
            </div>
            <form method="GET" id="printFilterForm">
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="roleFilter">Role</label>
                            <select id="roleFilter" name="role" class="form-select shadow-none print-filter-select">
                                <option value="">---Select---</option>
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedRole === $value ? 'selected' : '' }}>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="userFilter">User</label>
                            <select id="userFilter" name="user_id" class="form-select shadow-none print-filter-select"
                                data-selected="{{ $selectedUser }}">
                                <option value="">---Select---</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="statusFilter">Status</label>
                            <select id="statusFilter" name="status" class="form-select shadow-none print-filter-select">
                                <option value="">---Select---</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 d-flex align-items-end gap-2 mb-3">
                        <button type="submit" class="search-btn filter-btn">Filter</button>
                        <a href="{{ route('attendance-management.print', ['year' => $year, 'month' => $month]) }}"
                            class="reset-btn">Reset</a>
                    </div>
                </div>
            </form>


        </div>

        <div class="main-table-container">
            <div class="table-over">
                <table class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center">SL NO</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">User</th>
                            <th class="text-center">Role</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Half Day</th>
                            <th class="text-center">Shift</th>
                            <th class="text-center">Leave Application</th>
                            <th class="text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="text-center">{{ $record->attendance_date?->format('d-m-Y') }}</td>
                                <td>{{ trim(($record->user?->code ? $record->user->code . ' - ' : '') . ($record->user?->name ?? '')) }}
                                </td>
                                <td class="text-center">{{ $record->user?->roles?->pluck('name')->implode(', ') ?: '-' }}
                                </td>
                                <td class="text-center">{{ $statuses[$record->status] ?? $record->status }}</td>
                                <td class="text-center">
                                    {{ $record->half_day_period ? ($halfDayPeriods[$record->half_day_period] ?? $record->half_day_period) : '-' }}
                                </td>
                                <td class="text-center">{{ $record->shift ?: '-' }}</td>
                                <td>
                                    @if($record->leave)
                                        {{ $record->leave->code ?: '#' . $record->leave->id }} -
                                        {{ $record->leave->leave_for === 'driver' ? $record->leave->driver_leave_type : ($record->leave->leaveType?->leave_name ?? 'Leave') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $record->remarks ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.print-filter-select').select2({ width: '100%', allowClear: true, placeholder: '---Select---' });

                function loadUsers() {
                    var role = $('#roleFilter').val();
                    var selected = $('#userFilter').data('selected') ? String($('#userFilter').data('selected')) : '';
                    $('#userFilter').empty().append('<option value="">---Select---</option>');

                    if (!role) {
                        $('#userFilter').trigger('change');
                        return;
                    }

                    $.get("{{ route('attendance-management.users-by-role') }}", { role: role }, function (users) {
                        users.forEach(function (user) {
                            var option = new Option(user.text, user.id, false, selected === String(user.id));
                            $('#userFilter').append(option);
                        });
                        $('#userFilter').trigger('change');
                    });
                }

                $('#roleFilter').on('change', function () {
                    $('#userFilter').data('selected', '');
                    loadUsers();
                });

                loadUsers();
            });
        </script>
    @endsection
</x-app-layout>
