@section('title')
    {{ $record ? 'Manage Salary Processing' : 'Add Salary Processing' }}
@endsection
<style>
    #userDetailsContent {
        padding: 22px !important;

        background: #ffffff !important;
    }


    /* =========================================
   Avatar Section
   ========================================= */

    #userDetailsContent .user-details-avatar-wrap {
        position: relative !important;

        width: 82px !important;
        height: 82px !important;

        margin: 0 auto 24px !important;
        padding: 4px !important;

        background: #eff6ff !important;

        border: 1px solid #dbeafe !important;
        border-radius: 50% !important;

        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.10) !important;
    }


    /* Decorative ring */
    #userDetailsContent .user-details-avatar-wrap::before {
        content: "" !important;

        position: absolute !important;
        inset: -5px !important;

        border: 1px solid #bfdbfe !important;
        border-radius: 50% !important;

        opacity: 0.7 !important;
    }


    /* Avatar */
    #userDetailsContent .user-details-avatar {
        position: relative !important;
        z-index: 1 !important;

        display: block !important;

        width: 100% !important;
        height: 100% !important;

        object-fit: cover !important;

        border-radius: 50% !important;

        background: #f8fafc !important;
    }


    /* =========================================
   Details Grid
   ========================================= */

    #userDetailsContent .row {
        margin-left: -6px !important;
        margin-right: -6px !important;
        gap: 5px;
    }


    /* Detail Item */
    #userDetailsContent .row>div {
        position: relative !important;

        padding: 13px 14px !important;
        margin-bottom: 12px !important;

        border: 1px solid #e8edf3 !important;
        border-radius: 9px !important;

        background: #f8fafc !important;

        transition: all 0.2s ease !important;
        width: 49%;
    }


    #userDetailsContent .row>div:hover {
        background: #ffffff !important;

        border-color: #bfdbfe !important;

        transform: translateY(-1px) !important;

        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05) !important;
    }


    /* =========================================
   Labels
   ========================================= */

    #userDetailsContent .row>div small {
        display: block !important;

        margin-bottom: 5px !important;

        color: #64748b !important;

        font-size: 10px !important;
        font-weight: 600 !important;

        text-transform: uppercase !important;
        letter-spacing: 0.45px !important;
    }


    /* =========================================
   Values
   ========================================= */

    #userDetailsContent .row>div strong {
        display: block !important;

        color: #1e293b !important;

        font-size: 13px !important;
        font-weight: 600 !important;

        line-height: 1.4 !important;

        word-break: break-word !important;
    }



    #userDetailsContent .row>div strong:empty {
        color: #94a3b8 !important;
    }

    #salarySplitModal .modal-content {
        border: 0 !important;
        border-radius: 14px !important;

        overflow: hidden !important;

        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.16) !important;
    }


    /* Modal Body */
    #salarySplitModal .modal-body {
        padding: 20px !important;
        background: #ffffff !important;
    }


    /* =========================================
   Salary Table
   ========================================= */

    #salarySplitModal #salarySplitContent {
        width: 100% !important;

        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;

        overflow: hidden !important;
    }


    #salarySplitModal #salarySplitContent .table {
        margin-bottom: 0 !important;

        color: #334155 !important;

        vertical-align: middle !important;
    }


    /* =========================================
   Table Header
   ========================================= */

    #salarySplitModal #salarySplitContent thead {
        background: #f8fafc !important;
    }


    #salarySplitModal #salarySplitContent thead th {
        padding: 12px 14px !important;

        color: #64748b !important;

        background: #f8fafc !important;

        border-bottom: 1px solid #e2e8f0 !important;

        font-size: 10px !important;
        font-weight: 700 !important;

        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }


    /* =========================================
   Table Rows
   ========================================= */

    #salarySplitModal #salarySplitContent tbody tr {
        background: #ffffff !important;

        transition: all 0.2s ease !important;
    }


    #salarySplitModal #salarySplitContent tbody tr:hover {
        background: #f8fbff !important;
    }


    #salarySplitModal #salarySplitContent tbody td {
        padding: 12px 14px !important;

        color: #334155 !important;

        border-bottom: 1px solid #f1f5f9 !important;

        font-size: 13px !important;
        font-weight: 500 !important;
    }


    /* Remove last border */
    #salarySplitModal #salarySplitContent tbody tr:last-child td {
        border-bottom: 0 !important;
    }


    /* =========================================
   Salary Component Name
   ========================================= */

    #salarySplitModal #salarySplitContent tbody td:nth-child(2) {
        color: #1e293b !important;

        font-weight: 600 !important;
    }


    /* =========================================
   Amount
   ========================================= */

    #salarySplitModal #salarySplitContent tbody td.text-end {
        color: #166534 !important;

        font-size: 13px !important;
        font-weight: 700 !important;

        white-space: nowrap !important;
    }


    /* =========================================
   Checkbox
   ========================================= */

    #salarySplitModal .salary-component-toggle {
        width: 17px !important;
        height: 17px !important;

        margin: 0 !important;

        cursor: pointer !important;

        border: 1px solid #cbd5e1 !important;
        border-radius: 5px !important;

        box-shadow: none !important;
    }


    #salarySplitModal .salary-component-toggle:checked {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
    }


    #salarySplitModal .salary-component-toggle:focus {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
    }


    /* =========================================
   Include Column
   ========================================= */

    #salarySplitModal #salarySplitContent th:first-child,
    #salarySplitModal #salarySplitContent td:first-child {
        width: 75px !important;

        text-align: center !important;
    }


    /* =========================================
   Selected Row
   ========================================= */

    #salarySplitModal #salarySplitContent tbody tr:has(.salary-component-toggle:checked) {
        background: #f8fbff !important;
    }




    /* =========================================
   Unchecked Row
   ========================================= */

    #salarySplitModal #salarySplitContent tbody tr:has(.salary-component-toggle:not(:checked)) {
        opacity: 0.55 !important;
        background: #f8fafc !important;
    }


    #salarySplitModal #salarySplitContent tbody tr:has(.salary-component-toggle:not(:checked)) td:nth-child(2),
    #salarySplitModal #salarySplitContent tbody tr:has(.salary-component-toggle:not(:checked)) td.text-end {
        text-decoration: line-through !important;
        color: #94a3b8 !important;
    }
</style>
<x-app-layout>
    @php
        $selectedYear = old('year', $filters['year'] ?? date('Y'));
        $selectedMonth = old('month', $filters['month'] ?? date('n'));
        $selectedDepot = old('depot_id', $filters['depot_id'] ?? null);
        $attendanceImportId = old('attendance_consolidate_import_id', $filters['attendance_consolidate_import_id'] ?? null);
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
            <input type="hidden" name="attendance_consolidate_import_id" value="{{ $attendanceImportId }}">

            <div class="main-table-container mb-3">
                <div class="row">
                    <div class="col-lg-4 o-f-inp mb-2">
                        <label for="salary_year">Year <span class="text-danger">*</span></label>
                        <select name="year" id="salary_year" class="form-select shadow-none salary-filter">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-2">
                        <label for="salary_month">Month <span class="text-danger">*</span></label>
                        <select name="month" id="salary_month" class="form-select shadow-none salary-filter">
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" {{ (int) $selectedMonth === (int) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-2">
                        <label for="depot_id">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none salary-filter">
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" {{ (int) $selectedDepot === (int) $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
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
                                <th class="text-center">Total Shifts</th>
                                <th class="text-center">Extra Shifts Worked</th>
                                <th class="text-center">Gross Salary</th>
                                <th class="text-center">PF</th>
                                <th class="text-center">Professional Tax</th>
                                <th class="text-center">ESI</th>
                                <th class="text-center">TDS</th>
                                <th class="text-center">Salary Advance</th>
                                <th class="text-center">LWF</th>
                                <th class="text-center">Penalty</th>
                                <th class="text-center">Total Deduction</th>
                                <th class="text-center">Net Total</th>
                            </tr>
                        </thead>
                        <tbody id="salaryRows">
                            @include('salary-processing.partials.rows', ['rows' => $rows])
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

                <div class="col-lg-12 mt-3 modal-btns-last">
                    <a href="{{ route('salary-processing.index') }}" class="modal-btn-1">Cancel</a>
                    <button type="submit" class="modal-btn-2" data-loading-text="Loading...">Submit</button>
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
                    <div class="modal-btns-last p-3">
                        <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="modal-btn-2" id="saveSalarySplit">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="attendanceInfoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content cnt-modal-cs">
                    <div class="modal-header">
                        <h5 class="modal-title">Attendance Details</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="attendanceInfoContent"></div>
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
                $('#salary_year, #salary_month, #depot_id, #payment_method').each(function () {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ width: '100%' });
                    }
                });
                var usersUrl = "{{ route('salary-processing.users') }}";
                var csrf = "{{ csrf_token() }}";

                // Mirror the table header while the payroll rows scroll vertically.
                var payrollTable = document.querySelector('.salary-table-scroll .payroll-table');
                var payrollScroller = payrollTable.closest('.salary-table-scroll');
                var floatingHeader = document.createElement('div');
                floatingHeader.className = 'salary-floating-header';
                floatingHeader.setAttribute('aria-hidden', 'true');
                var headerTable = payrollTable.cloneNode(false);
                headerTable.classList.remove('mt-3');
                headerTable.appendChild(payrollTable.tHead.cloneNode(true));
                floatingHeader.appendChild(headerTable);
                document.body.appendChild(floatingHeader);

                function updatePayrollHeader() {
                    var rect = payrollTable.getBoundingClientRect();
                    var viewport = payrollScroller.getBoundingClientRect();
                    var navigation = document.getElementById('header');
                    var top = navigation ? Math.max(navigation.getBoundingClientRect().bottom, 0) : 0;
                    var height = payrollTable.tHead.getBoundingClientRect().height;
                    var left = Math.max(viewport.left, 0);
                    var right = Math.min(viewport.right, document.documentElement.clientWidth);
                    floatingHeader.style.display = rect.top < top && rect.bottom > top && right > left ? 'block' : 'none';
                    floatingHeader.style.top = Math.min(top, rect.bottom - height) + 'px';
                    floatingHeader.style.left = left + 'px';
                    floatingHeader.style.width = Math.max(right - left, 0) + 'px';
                    headerTable.style.width = rect.width + 'px';
                    headerTable.style.minWidth = rect.width + 'px';
                    headerTable.style.marginLeft = (rect.left - left) + 'px';
                    Array.from(payrollTable.tHead.rows[0].cells).forEach(function (cell, index) {
                        headerTable.tHead.rows[0].cells[index].style.width = cell.getBoundingClientRect().width + 'px';
                    });
                }

                var headerFrame;
                function schedulePayrollHeader() {
                    if (headerFrame) return;
                    headerFrame = requestAnimationFrame(function () {
                        headerFrame = null;
                        updatePayrollHeader();
                    });
                }
                window.addEventListener('scroll', schedulePayrollHeader, { passive: true, capture: true });
                window.addEventListener('resize', schedulePayrollHeader);
                if (window.ResizeObserver) {
                    var payrollObserver = new ResizeObserver(schedulePayrollHeader);
                    payrollObserver.observe(payrollTable);
                    if (document.getElementById('header')) payrollObserver.observe(document.getElementById('header'));
                }
                updatePayrollHeader();

                function isDutyIncentive(item) {
                    return String(item.name || '').trim().toLowerCase() === 'duty incentive';
                }

                function roundMoney(value) {
                    return Math.round((value + Number.EPSILON) * 100) / 100;
                }

                function displayMoney(value) {
                    var amount = Number(value) || 0;
                    var rounded = Math.sign(amount) * Math.floor(Math.abs(amount) + 0.5);
                    return rounded.toLocaleString('en-US', { maximumFractionDigits: 0 });
                }

                function escapeHtml(value) {
                    return $('<div>').text(value == null ? '' : value).html();
                }

                function labelPayrollCells() {
                    var labels = Array.from(payrollTable.tHead.rows[0].cells).map(function (cell) {
                        return cell.textContent.trim();
                    });
                    Array.from(payrollTable.tBodies[0].rows).forEach(function (row) {
                        if (row.cells.length !== labels.length) return;
                        Array.from(row.cells).forEach(function (cell, index) {
                            cell.setAttribute('data-label', labels[index]);
                        });
                    });
                }

                labelPayrollCells();

                function rowHtml(row, index) {
                    row = Object.assign({}, row);
                    var difference = 0;
                    if (row.pf_applicable === false || row.pf_applicable === 0) {
                        difference -= Number(row.pf || 0);
                        row.pf = 0;
                    }
                    ['esi', 'tds', 'salary_advance', 'lwf', 'penalty'].forEach(function (field) {
                        var original = Number(row[field] || 0);
                        row[field] = Math.round(original);
                        difference += row[field] - original;
                    });
                    row.total_deduction = roundMoney(Number(row.total_deduction ?? row.deduction ?? 0) + difference);
                    row.net_total = roundMoney(Number(row.net_total ?? row.net_salary ?? 0) - difference);
                    var split = JSON.stringify(row.salary_split || []).replace(/'/g, '&#039;');
                    var details = JSON.stringify(row.user_details || {}).replace(/'/g, '&#039;');
                    var selectedInputs = (row.salary_split || []).filter(function (item) {
                        return item.selected !== false;
                    }).map(function (item) {
                        return '<input type="hidden" class="selected-component-input" name="items[' + index + '][selected_components][]" value="' + item.id + '">';
                    }).join('');
                    return '<tr data-pf-applicable="' + ((row.pf_applicable === false || row.pf_applicable === 0) ? 0 : 1) + '" data-base-deduction="' + (Number(row.total_deduction ?? row.deduction ?? 0) - ['esi', 'tds', 'salary_advance', 'lwf', 'penalty'].reduce(function (sum, field) { return sum + Number(row[field] || 0); }, 0)) + '" data-total-earned="' + Number(row.total_earned ?? (Number(row.net_total ?? row.net_salary ?? 0) + Number(row.total_deduction ?? row.deduction ?? 0))) + '" data-basic="' + row.basic_salary + '" data-lop="' + Number(row.lop || 0) + '" data-deduction="' + row.deduction + '" data-incentive="' + row.incentive + '" data-working-days="' + row.total_working_days + '" data-calendar-days="' + row.calendar_days + '" data-total-days="' + row.total_attendance_days + '" data-unpaid-leave-days="' + Number(row.unpaid_leave_days || 0) + '">' +
                        '<td class="text-center">' + (index + 1) + '<input type="hidden" name="items[' + index + '][user_id]" value="' + row.user_id + '"></td>' +
                        '<td class="text-center">' + escapeHtml(row.name) + ' <button type="button" class="btn btn-link p-0 view-user-details" data-details=\'' + details + '\'>[Details]</button> <button type="button" class="btn btn-link p-0 view-attendance attendance-details-btn" data-attendance=\'' + JSON.stringify({ present_days: row.present_days, wo_days: row.total_attendance_days, absent_days: row.absent_days, week_off_days: row.week_off_days, per_day_salary: row.salary_day_rate }).replace(/'/g, '&#039;') + '\'>[Attendance]</button></td>' +
                        '<td class="text-center">' + row.total_attendance_days + '</td>' +
                        '<td class="text-center extra-days-worked">' + Number(row.extra_days_worked || 0).toFixed(2) + '</td>' +
                        '<td class="text-center"><span class="gross-salary">' + displayMoney(row.gross_salary ?? row.basic_salary) + '</span> <button type="button" class="btn btn-link p-0 view-split" data-split=\'' + split + '\'>[View Split]</button>' + selectedInputs + '</td>' +
                        '<td class="text-center pf">' + displayMoney(row.pf) + '</td>' +
                        '<td class="text-center professional-tax">' + displayMoney(row.professional_tax) + '</td>' +
                        ['esi', 'tds', 'salary_advance', 'lwf', 'penalty'].map(function (field, i) {
                            return '<td><input type="text" inputmode="numeric" pattern="[0-9]{1,10}([.][0-9]{1,2})?" maxlength="13" class="form-control manual-deduction" aria-label="' + ['ESI', 'TDS', 'Salary Advance', 'LWF', 'Penalty'][i] + '" name="items[' + index + '][' + field + ']" value="' + Math.round(Number(row[field] || 0)) + '">' + (field === 'esi' ? '<input type="hidden" class="recalculate-esi-flag" name="items[' + index + '][recalculate_esi]" value="0">' : '') + '</td>';
                        }).join('') +
                        '<td class="text-center total-deduction">' + displayMoney(row.total_deduction ?? row.deduction) + '<input type="hidden" class="deduction-input" name="items[' + index + '][deduction]" value="' + Number(row.deduction || 0).toFixed(2) + '"><input type="hidden" name="items[' + index + '][unauthorized_leaves]" value="' + Number(row.unpaid_leave_days || 0) + '"></td>' +
                        '<td class="text-center net-total">' + displayMoney(row.net_total ?? row.net_salary) + '</td>' +
                        '</tr>';
                }

                function reloadUsers() {
                    if (!$('#depot_id').val()) {
                        $('#salaryRows').html('<tr><td colspan="14" class="text-center text-muted">Select a depot.</td></tr>');
                        return;
                    }

                    $.get(usersUrl, {
                        depot_id: $('#depot_id').val(),
                        year: $('#salary_year').val(),
                        month: $('#salary_month').val()
                    }).done(function (rows) {
                        if (rows.length && rows[0].attendance_import_id) {
                            $('input[name="attendance_consolidate_import_id"]').val(rows[0].attendance_import_id);
                        }
                        $('#salaryRows').html(rows.length ? rows.map(function (row, index) {
                            return rowHtml(row, index);
                        }).join('') : '<tr><td colspan="14" class="text-center text-muted">No consolidated attendance rows found for this depot and month.</td></tr>');
                        labelPayrollCells();
                    }).fail(function () {
                        showToast('error', 'Unable to load users.');
                    });
                }

                $(document).on('change', '.salary-filter', reloadUsers);

                function manualDeductionTotal(row) {
                    var total = 0;
                    row.find('.manual-deduction').each(function () {
                        total += Math.round(Number(this.value) || 0);
                    });
                    return total;
                }

                function recalculateManualDeductions(row) {
                    var total = roundMoney(Number(row.data('base-deduction') || 0) + manualDeductionTotal(row));
                    row.find('.total-deduction').contents().first().replaceWith(displayMoney(total));
                    row.find('.net-total').text(displayMoney(roundMoney(Number(row.data('total-earned') || 0) - total)));
                }

                function numericDeductionValue(value) {
                    var parts = value.replace(/[^0-9.]/g, '').split('.');
                    var whole = parts.shift().slice(0, 10);
                    return parts.length ? (whole || '0') + '.' + parts.join('').slice(0, 2) : whole;
                }

                function roundDeductionInput(input) {
                    input.value = String(Math.round(Number(numericDeductionValue(input.value)) || 0));
                }

                $(document).on('blur', '.manual-deduction', function () {
                    roundDeductionInput(this);
                    recalculateManualDeductions($(this).closest('tr'));
                });

                function recalculateEmployeeEsi(row) {
                    var fixedGross = Number(row.data('basic') || 0);
                    var earnedGross = Number(row.data('total-earned') || 0);
                    var amount = fixedGross <= 21000 ? Math.round(earnedGross * 0.0075) : 0;
                    row.find('input[name$="[esi]"]').val(String(amount));
                    row.find('.recalculate-esi-flag').val('1');
                    recalculateManualDeductions(row);
                }

                $(document).on('input', '.manual-deduction', function () {
                    if (this.name.endsWith('[esi]')) $(this).closest('tr').find('.recalculate-esi-flag').val('0');
                    var numericValue = numericDeductionValue(this.value);
                    if (this.value !== numericValue) this.value = numericValue;
                    recalculateManualDeductions($(this).closest('tr'));
                });

                $(document).on('click', '.view-split', function () {
                    var button = $(this);
                    var split = $(this).data('split') || [];
                    var html = split.length ? '<table class="table table-sm"><thead><tr><th>Include</th><th>Salary Component</th><th class="text-end">Amount</th></tr></thead><tbody>' +
                        split.map(function (item) {
                            return '<tr><td><input type="checkbox" class="form-check-input salary-component-toggle" value="' + item.id + '" data-amount="' + item.amount + '" data-name="' + $('<div>').text(item.name).html() + '" ' + (item.selected !== false ? 'checked' : '') + '></td><td>' + $('<div>').text(item.name).html() + '</td><td class="text-end">' + displayMoney(item.calculated_amount ?? item.amount) + '</td></tr>';
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
                    var gross = selected.filter(function (item) { return String(item.type).toLowerCase() === 'earning' && !isDutyIncentive(item); }).reduce(function (sum, item) { return sum + Number(item.amount || 0); }, 0);
                    var templateDeduction = selected.filter(function (item) { return String(item.type).toLowerCase() === 'deduction' && !isDutyIncentive(item); }).reduce(function (sum, item) { return sum + Number(item.amount || 0); }, 0);
                    var incentive = 0;
                    var baseGross = gross;
                    var calendarDays = Number(row.data('calendar-days')) || 0;
                    var attendanceDays = Number(row.data('total-days')) || 0;
                    var earned = calendarDays > 0 ? (baseGross / calendarDays) * Math.min(attendanceDays, calendarDays) : 0;
                    var basicVda = selected.filter(function (item) { return ['basic', 'vda'].indexOf(String(item.name || '').trim().toLowerCase()) !== -1; }).reduce(function (sum, item) { return sum + Number(item.amount || 0); }, 0);
                    var ratio = calendarDays > 0 ? Math.min(Math.max(attendanceDays, 0), calendarDays) / calendarDays : 0;
                    var pf = Number(row.data('pf-applicable')) === 1 ? roundMoney(basicVda * ratio * 0.12) : 0;
                    var extraDays = Math.max(attendanceDays - calendarDays, 0);
                    incentive = calendarDays > 0 ? (baseGross / calendarDays) * extraDays : 0;
                    split.forEach(function (item) {
                        if (isDutyIncentive(item)) item.amount = incentive;
                        item.calculated_amount = isDutyIncentive(item) ? incentive : Number(item.amount || 0) * ratio;
                    });
                    button.data('split', split);
                    var totalEarned = roundMoney(earned + incentive);
                    var professionalTax = totalEarned <= 15000 ? 0 : (totalEarned <= 20000 ? 150 : 200);
                    var dailyRate = calendarDays > 0 ? baseGross / calendarDays : 0;
                    var lop = roundMoney(dailyRate * Number(row.data('unpaid-leave-days') || 0));
                    var deduction = templateDeduction * ratio + lop;
                    row.data('lop', lop);
                    var attendanceButton = row.find('.view-attendance');
                    var attendance = attendanceButton.data('attendance') || {};
                    attendance.per_day_salary = roundMoney(dailyRate);
                    attendanceButton.data('attendance', attendance);
                    var baseDeduction = roundMoney(deduction + pf + professionalTax);
                    row.data('base-deduction', baseDeduction);
                    row.data('total-earned', totalEarned);
                    row.data('basic', baseGross);
                    row.find('.gross-salary').text(displayMoney(totalEarned));
                    row.find('.pf').text(displayMoney(pf));
                    row.find('.professional-tax').text(displayMoney(professionalTax));
                    row.find('.deduction-input').val(deduction.toFixed(2));
                    recalculateEmployeeEsi(row);
                    $('#salarySplitModal').modal('hide');
                });

                $(document).on('click', '.view-attendance', function () {
                    var data = $(this).data('attendance') || {};
                    var labels = { present_days: 'Present Days', absent_days: 'Absent Days', week_off_days: 'Week Off', wo_days: 'Total Days', per_day_salary: 'Salary Per Day' };
                    var html = '<div class="attendance-summary">' + Object.keys(labels).map(function (key) {
                        var value = key === 'per_day_salary' ? '₹' + displayMoney(data[key]) : Number(data[key] || 0).toFixed(2);
                        return '<div class="attendance-summary-item"><span class="attendance-summary-label">' + labels[key] + '</span><span class="attendance-summary-value">' + value + '</span></div>';
                    }).join('') + '</div>';
                    $('#attendanceInfoContent').html(html);
                    $('#attendanceInfoModal').modal('show');
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
                    var form = $(this);
                    form.find('.manual-deduction').each(function () { roundDeductionInput(this); });
                    var itemInputs = form.find(':input[name^="items["]');
                    var items = {};
                    itemInputs.serializeArray().forEach(function (input) {
                        var match = input.name.match(/^items\[(\d+)\]\[([^\]]+)\](\[\])?$/);
                        if (!match) return;
                        var item = items[match[1]] || (items[match[1]] = {});
                        if (match[3]) {
                            (item[match[2]] || (item[match[2]] = [])).push(input.value);
                        } else {
                            item[match[2]] = input.value;
                        }
                    });
                    form.find('input[name="items_json"]').remove();
                    $('<input>', {
                        type: 'hidden',
                        name: 'items_json',
                        value: JSON.stringify(Object.keys(items).map(function (key) { return items[key]; }))
                    }).appendTo(form);
                    itemInputs.prop('disabled', true);
                    var submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html(submitBtn.data('loading-text'));
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .attendance-details-btn {
                font-size: 10px;
                line-height: 1;
                white-space: nowrap;
            }

            .salary-table-scroll {
                overflow-x: visible;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .salary-floating-header {
                position: fixed;
                overflow: hidden;
                z-index: 990;
                pointer-events: none;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .12);
            }

            .salary-floating-header .payroll-table {
                margin: 0 !important;
                table-layout: fixed;
                font-size: 12px;
            }

            .salary-floating-header .payroll-table th {
                min-width: 0 !important;
                overflow-wrap: anywhere;
                font-size: 11px;
                padding: 8px 3px;
                vertical-align: middle;
                white-space: normal;
                background: linear-gradient(135deg, #082647 0%, #1c3e64 100%);
                color: #fff;
            }

            @media print {
                .salary-floating-header { display: none !important; }
            }

            .salary-table-scroll .payroll-table {
                font-size: 12px;
                min-width: 0;
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

            .salary-table-scroll .payroll-table th,
            .salary-table-scroll .payroll-table td {
                min-width: 0 !important;
                overflow-wrap: anywhere;
                padding: 8px 3px;
            }

            .salary-table-scroll .payroll-table th {
                width: 7%;
                font-size: 11px;
            }

            .salary-table-scroll .payroll-table th:first-child {
                width: 3%;
            }

            .salary-table-scroll .payroll-table th:nth-child(2) {
                width: 13%;
            }

            @media (max-width: 991.98px) {
                .salary-floating-header { display: none !important; }
                .salary-table-scroll .payroll-table thead { display: none; }
                .salary-table-scroll .payroll-table,
                .salary-table-scroll .payroll-table tbody { display: block; }
                .salary-table-scroll .payroll-table tbody tr {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 8px;
                    margin-bottom: 12px;
                    padding: 12px;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                }
                .salary-table-scroll .payroll-table tbody td {
                    display: block;
                    min-width: 0;
                    border: 0;
                    text-align: left !important;
                }
                .salary-table-scroll .payroll-table tbody td::before {
                    content: attr(data-label);
                    display: block;
                    margin-bottom: 4px;
                    font-size: 11px;
                    font-weight: 600;
                    color: #64748b;
                }
                .salary-table-scroll .payroll-table tbody td:nth-child(2),
                .salary-table-scroll .payroll-table tbody td[colspan] { grid-column: 1 / -1; }
                .salary-table-scroll .payroll-table .view-split,
                .salary-table-scroll .payroll-table .view-user-details { margin-left: 0; }
            }

            .salary-table-scroll .payroll-table .manual-deduction {
                display: block;
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                height: 36px;
                padding: 6px 8px;
                font-size: 12px;
                text-align: right;
                border-radius: 6px;
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

            #attendanceInfoModal .modal-dialog {
                max-width: 430px;
            }

            #attendanceInfoModal .modal-content {
                border: 0;
                border-radius: 14px;
                box-shadow: 0 12px 35px rgba(15, 23, 42, .2);
            }

            #attendanceInfoModal .modal-header {
                padding: 18px 22px;
                border-bottom: 1px solid #eef1f5;
            }

            #attendanceInfoModal .modal-title {
                font-size: 17px;
                font-weight: 600;
            }

            #attendanceInfoModal .modal-body {
                padding: 18px 22px 22px;
            }

            #attendanceInfoModal .attendance-summary {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            #attendanceInfoModal .attendance-summary-item {
                padding: 12px 14px;
                border: 1px solid #edf0f4;
                border-radius: 9px;
                background: #fafbfc;
            }

            #attendanceInfoModal .attendance-summary-label {
                display: block;
                color: #6b7280;
                font-size: 11px;
                margin-bottom: 4px;
            }

            #attendanceInfoModal .attendance-summary-value {
                display: block;
                color: #1f2937;
                font-size: 15px;
                font-weight: 600;
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
