@section('title')
    {{ $record ? 'Manage Salary Processing' : 'Add Salary Processing' }}
@endsection
<x-app-layout>
    @php
        $selectedYear = old('year', $filters['year'] ?? date('Y'));
        $selectedMonth = old('month', $filters['month'] ?? date('n'));
        $selectedDepot = old('depot_id', $filters['depot_id'] ?? null);
        $selectedRole = old('role_id', $filters['role_id'] ?? null);
        $selectedRoleName = optional($roles->firstWhere('id', (int) $selectedRole))->name;
    @endphp
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Processing</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item"><a href="{{ route('salary-processing.index') }}">Salary Processing</a>
                    </li>
                    <li class="breadcrumb-item active">{{ $record ? 'Manage' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form id="salaryProcessingForm" method="POST"
            action="{{ $record ? route('salary-processing.update', $record->id) : route('salary-processing.store') }}">
            @csrf
            @if ($record)
                @method('PUT')
            @endif

            <div class="main-table-container mb-3">
                <div class="row">
                    <div class="col-lg-3 o-f-inp mb-2">
                        <label for="year">Year <span class="text-danger">*</span></label>
                        <select name="year" id="year" class="form-select shadow-none">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-2">
                        <label for="month">Month <span class="text-danger">*</span></label>
                        <select name="month" id="month" class="form-select shadow-none">
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" {{ (int) $selectedMonth === (int) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-2">
                        <label for="depot_id">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none salary-filter">
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" {{ (int) $selectedDepot === (int) $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-2">
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none salary-filter">
                            <option value="">--- Select ---</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" {{ (int) $selectedRole === (int) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            <div class="top-choose-box">
                <div class="table-over salary-table-scroll">
                    <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent mt-3 payroll-table">
                        <thead>
                            <tr>
                                <th class="text-center nowrap">SL No</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Total Leave Taken</th>
                                <th
                                    class="text-center driver-only {{ $selectedRoleName === 'Driver' ? '' : 'd-none' }}">
                                    Total Shifts Completed</th>
                                <th
                                    class="text-center non-driver-only {{ $selectedRoleName === 'Driver' ? 'd-none' : '' }}">
                                    Total Working Days</th>
                                <th class="text-center">LOP</th>
                                <th class="text-center">Gross Salary</th>
                                <th class="text-center">Deduction</th>
                                <th class="text-center">Incentive</th>
                                <th class="text-center">Unauthorized Leaves</th>
                                <th class="text-center">Net Salary</th>
                            </tr>
                        </thead>
                        <tbody id="salaryRows">
                            @include('salary-processing.partials.rows', ['rows' => $rows, 'isDriver' => $selectedRoleName === 'Driver'])
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">

                    <div class="col-lg-6 o-f-inp mb-2">
                        <label for="salary_date">Salary Date</label>
                        <input type="date" id="salary_date" class="form-control shadow-none" name="salary_date"
                            value="{{ old('salary_date', optional($record?->salary_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-lg-6 o-f-inp mb-2">
                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select shadow-none">
                            <option value="">--- Select ---</option>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" {{ old('payment_method', $record->payment_method ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 o-f-inp mb-2">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" class="form-control shadow-none" name="remarks"
                            rows="5">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                    </div>
                </div>

                <div class="col-lg-12 mt-3 text-center">
                    <a href="{{ route('salary-processing.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary mx-auto" data-loading-text="Loading...">Submit</button>
                </div>
            </div>
        </form>

        <div class="modal fade" id="salarySplitModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content cnt-modal-cs">
                    <div class="modal-header">
                        <h5 class="modal-title">Salary Split</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="salarySplitContent"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveSalarySplit">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content cnt-modal-cs">
                    <div class="modal-header">
                        <h5 class="modal-title">User Basic Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="userDetailsContent"></div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var usersUrl = "{{ route('salary-processing.users') }}";
                var csrf = "{{ csrf_token() }}";

                function selectedRoleName() {
                    return $('#role_id option:selected').data('role-name') || '';
                }

                function escapeHtml(value) {
                    return $('<div>').text(value == null ? '' : value).html();
                }

                function rowHtml(row, index, isDriver) {
                    var split = JSON.stringify(row.salary_split || []).replace(/'/g, '&#039;');
                    var details = JSON.stringify(row.user_details || {}).replace(/'/g, '&#039;');
                    var selectedInputs = (row.salary_split || []).filter(function (item) {
                        return item.selected !== false;
                    }).map(function (item) {
                        return '<input type="hidden" class="selected-component-input" name="items[' + index + '][selected_components][]" value="' + item.id + '">';
                    }).join('');
                    return '<tr data-basic="' + row.basic_salary + '" data-deduction="' + row.deduction + '" data-incentive="' + row.incentive + '" data-working-days="' + row.total_working_days + '">' +
                        '<td class="text-center">' + (index + 1) + '<input type="hidden" name="items[' + index + '][user_id]" value="' + row.user_id + '"></td>' +
                        '<td class="text-center">' + escapeHtml(row.name) + ' <button type="button" class="btn btn-link p-0 view-user-details" data-details=\'' + details + '\'>[Details]</button></td>' +
                        '<td class="text-center">' + row.total_leave_taken + '</td>' +
                        '<td class="text-center driver-only ' + (isDriver ? '' : 'd-none') + '">' + (isDriver ? row.total_shifts_completed : '-') + '</td>' +
                        '<td class="text-center non-driver-only ' + (isDriver ? 'd-none' : '') + '">' + row.total_working_days + '</td>' +
                        '<td class="text-center lop">' + Number(row.lop).toFixed(2) + '</td>' +
                        '<td class="text-center"><span class="gross-salary">' + Number(row.basic_salary).toFixed(2) + '</span> <button type="button" class="btn btn-link p-0 view-split" data-split=\'' + split + '\'>[View Split]</button>' + selectedInputs + '</td>' +
                        '<td class="text-center"><input type="number" step="0.01" min="0" class="form-control shadow-none salary-adjustment deduction-input" name="items[' + index + '][deduction]" value="' + Number(row.deduction || 0).toFixed(2) + '"></td>' +
                        '<td class="text-center"><input type="number" step="0.01" min="0" class="form-control shadow-none salary-adjustment incentive-input" name="items[' + index + '][incentive]" value="' + Number(row.incentive || 0).toFixed(2) + '"></td>' +
                        '<td class="text-center"><input type="number" step="0.01" min="0" class="form-control shadow-none unauthorized-leaves" name="items[' + index + '][unauthorized_leaves]" value="' + Number(row.unauthorized_leaves || 0).toFixed(2) + '"></td>' +
                        '<td class="text-center net-salary">' + Number(row.net_salary).toFixed(2) + '</td>' +
                        '</tr>';
                }

                function reloadUsers() {
                    if (!$('#depot_id').val() || !$('#role_id').val()) {
                        $('#salaryRows').html('<tr><td colspan="11" class="text-center text-muted">Select depo and role.</td></tr>');
                        return;
                    }

                    $.get(usersUrl, {
                        depot_id: $('#depot_id').val(),
                        role_id: $('#role_id').val(),
                        year: $('#year').val(),
                        month: $('#month').val()
                    }).done(function (rows) {
                        var isDriver = selectedRoleName() === 'Driver';
                        $('.driver-only').toggleClass('d-none', !isDriver);
                        $('.non-driver-only').toggleClass('d-none', isDriver);
                        $('#salaryRows').html(rows.length ? rows.map(function (row, index) {
                            return rowHtml(row, index, isDriver);
                        }).join('') : '<tr><td colspan="11" class="text-center text-muted">No users found for selected depo and role.</td></tr>');
                    }).fail(function () {
                        showToast('error', 'Unable to load users.');
                    });
                }

                $(document).on('change', '.salary-filter', reloadUsers);

                function recalculateRow(row) {
                    var basic = Number(row.data('basic')) || 0;
                    var deduction = Number(row.find('.deduction-input').val()) || 0;
                    var incentive = Number(row.find('.incentive-input').val()) || 0;
                    var workingDays = Number(row.data('working-days')) || 0;
                    var unauthorized = Number(row.find('.unauthorized-leaves').val()) || 0;
                    var lop = workingDays > 0 ? (basic / workingDays) * unauthorized : 0;
                    var net = basic + incentive - deduction - lop;
                    row.find('.lop').text(lop.toFixed(2));
                    row.find('.net-salary').text(net.toFixed(2));
                }

                $(document).on('input', '.unauthorized-leaves, .salary-adjustment', function () {
                    recalculateRow($(this).closest('tr'));
                });

                $(document).on('click', '.view-split', function () {
                    var button = $(this);
                    var split = ($(this).data('split') || []).filter(function (item) {
                        return String(item.type).toLowerCase() === 'earning';
                    });
                    var html = split.length ? '<table class="table table-sm"><thead><tr><th>Include</th><th>Salary Component</th><th class="text-end">Amount</th></tr></thead><tbody>' +
                        split.map(function (item) {
                            return '<tr><td><input type="checkbox" class="form-check-input salary-component-toggle" value="' + item.id + '" data-amount="' + item.amount + '" data-name="' + $('<div>').text(item.name).html() + '" ' + (item.selected !== false ? 'checked' : '') + '></td><td>' + $('<div>').text(item.name).html() + '</td><td class="text-end">' + Number(item.amount).toFixed(2) + '</td></tr>';
                        }).join('') + '</tbody></table>' : '<p class="text-muted mb-0">No salary split available.</p>';
                    $('#salarySplitContent').html(html).data('button', button);
                    $('#salarySplitModal').modal('show');
                });

                $('#saveSalarySplit').on('click', function () {
                    var button = $('#salarySplitContent').data('button');

                    if (!button || !button.length) {
                        return;
                    }

                    var row = button.closest('tr');
                    var split = button.data('split') || [];
                    var selectedIds = $('.salary-component-toggle:checked').map(function () { return Number(this.value); }).get();
                    split.forEach(function (item) { item.selected = selectedIds.indexOf(Number(item.id)) !== -1; });
                    button.data('split', split);
                    row.find('.selected-component-input').remove();
                    selectedIds.forEach(function (id) {
                        var userInput = row.find('input[name$="[user_id]"]');
                        var name = userInput.attr('name').replace('[user_id]', '[selected_components][]');
                        button.after($('<input>', { type: 'hidden', class: 'selected-component-input', name: name, value: id }));
                    });
                    var selected = split.filter(function (item) { return item.selected; });
                    var incentive = selected.filter(function (item) { return String(item.name).toLowerCase().indexOf('incent') !== -1; }).reduce(function (sum, item) { return sum + Number(item.amount || 0); }, 0);
                    var gross = selected.filter(function (item) { return String(item.name).toLowerCase().indexOf('incent') === -1; }).reduce(function (sum, item) { return sum + Number(item.amount || 0); }, 0);
                    row.data('basic', gross);
                    row.data('incentive', incentive);
                    row.find('.gross-salary').text(gross.toFixed(2));
                    row.find('.incentive-input').val(incentive.toFixed(2));
                    recalculateRow(row);
                    $('#salarySplitModal').modal('hide');
                });

                $(document).on('click', '.view-user-details', function () {
                    var details = $(this).data('details') || {};
                    var labels = { name: 'Name', code: 'Employee Code', role: 'Role', phone: 'Phone', email: 'Email', aadhaar_number: 'Aadhaar Number', depot: 'Depot', designation: 'Designation', employment_type: 'Employment Type', joining_date: 'Joining Date' };
                    var avatarUrl = details.avatar_url || '{{ asset('assets/img/user.png') }}';
                    var html = '<div class="user-details-avatar-wrap"><img src="' + escapeHtml(avatarUrl) + '" alt="' + escapeHtml((details.name || 'User') + ' image') + '" class="user-details-avatar"></div>' +
                        '<div class="row">' + Object.keys(labels).map(function (key) {
                        return '<div class="col-sm-6 mb-3"><small class="text-muted d-block">' + labels[key] + '</small><strong>' + $('<div>').text(details[key] || '-').html() + '</strong></div>';
                    }).join('') + '</div>';
                    $('#userDetailsContent').html(html);
                    $('#userDetailsModal').modal('show');
                });

                $('#salaryProcessingForm').on('submit', function () {
                    var submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html(submitBtn.data('loading-text'));
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .salary-table-scroll {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .salary-table-scroll .payroll-table {
                font-size: 12px;
                min-width: 1050px;
                table-layout: fixed;
                width: 100%;
            }

            .salary-table-scroll .payroll-table th,
            .salary-table-scroll .payroll-table td {
                padding: 8px 5px;
                vertical-align: middle;
                white-space: normal;
                word-break: normal;
            }

            .salary-table-scroll .payroll-table .unauthorized-leaves,
            .salary-table-scroll .payroll-table .salary-adjustment {
                min-width: 75px;
                padding: 5px;
            }

            .salary-table-scroll .payroll-table .view-split,
            .salary-table-scroll .payroll-table .view-user-details {
                display: block;
                font-size: 11px;
                margin: 2px auto 0;
            }

            .user-details-avatar-wrap {
                align-items: center;
                display: flex;
                justify-content: center;
                margin-bottom: 18px;
            }

            .user-details-avatar {
                border: 1px solid #d9dee3;
                border-radius: 50%;
                height: 92px;
                object-fit: cover;
                width: 92px;
            }
        </style>
    @endsection
</x-app-layout>
