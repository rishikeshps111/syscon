@section('title')
    Salary Report
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Report</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <form id="salaryReportFilterForm" method="GET">
                <div class="row align-items-end">
                    <div class="col-lg-2 col-md-4 o-f-inp mb-3">
                        <label for="yearFilter">Year</label>
                        <select id="yearFilter" class="form-select shadow-none report-filter">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 o-f-inp mb-3">
                        <label for="monthFilter">Month</label>
                        <select id="monthFilter" class="form-select shadow-none report-filter">
                            <option value="">All Months</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" @selected((int) $filters['month'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 o-f-inp mb-3">
                        <label for="depotFilter">Depo</label>
                        <select id="depotFilter" class="form-select shadow-none report-filter">
                            <option value="">All Depos</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) $filters['depot_id'] === $depot->id)>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 o-f-inp mb-3">
                        <label for="roleFilter">User Type</label>
                        <select id="roleFilter" class="form-select shadow-none report-filter">
                            <option value="">All User Types</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) $filters['role_id'] === $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-8 mb-3">
                        <div class="filter-btns-top justify-content-start">
                            <button type="button" id="resetFilters" class="reset-btn border-0">Reset</button>
                            <button type="button" id="exportSalaryReport" class="exp-btn">Export</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-over salary-report-scroll mt-3">
                <table id="salaryReportTable" class="align-middle mb-0 table tble-cstm bg-transparent">
                    <thead>
                        <tr>
                            <th class="text-center">SL No</th>
                            <th class="text-center">User Code</th>
                            <th class="text-center">User Name</th>
                            <th class="text-center">Month</th>
                            <th class="text-center">Year</th>
                            <th class="text-center">Depo</th>
                            <th class="text-center">User Type</th>
                            <th class="text-center">Gross Salary</th>
                            <th class="text-center">Deduction</th>
                            <th class="text-center">LOP</th>
                            <th class="text-center">Net Salary</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="salaryDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content cnt-modal-cs">
                    <div class="modal-header">
                        <h5 class="modal-title">Salary Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="salaryDetailContent">
                        <p class="text-center text-muted mb-0">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var currentYear = "{{ date('Y') }}";
                var filters = $('.report-filter');

                function filterData() {
                    return {
                        year: $('#yearFilter').val(),
                        month: $('#monthFilter').val(),
                        depot_id: $('#depotFilter').val(),
                        role_id: $('#roleFilter').val()
                    };
                }

                function escapeHtml(value) {
                    return $('<div>').text(value == null ? '-' : value).html();
                }

                function money(value) {
                    return Number(value || 0).toFixed(2);
                }

                function detailItem(label, value) {
                    return '<div class="col-lg-3 col-md-4 col-sm-6 mb-3"><small class="text-muted d-block">' +
                        escapeHtml(label) + '</small><strong>' + escapeHtml(value) + '</strong></div>';
                }

                function renderDetails(data) {
                    var components = data.salary.components.length
                        ? data.salary.components.map(function (component) {
                            return '<tr><td>' + escapeHtml(component.name) + '</td><td class="text-end">' + money(component.amount) + '</td></tr>';
                        }).join('')
                        : '<tr><td colspan="2" class="text-center text-muted">No earning components recorded.</td></tr>';

                    return '<h6 class="fw-bold mb-3">Employee and Processing</h6><div class="row">' +
                        detailItem('User Code', data.user.code) + detailItem('User Name', data.user.name) +
                        detailItem('Aadhaar No', data.user.aadhaar_no) + detailItem('User Type', data.processing.role) +
                        detailItem('Depo', data.processing.depot) + detailItem('Salary Period', data.processing.month + ' ' + data.processing.year) +
                        detailItem('Salary Date', data.processing.salary_date) + detailItem('Payment Method', data.processing.payment_method) +
                        detailItem('Status', data.processing.status) + detailItem('Approved By', data.processing.approved_by) +
                        detailItem('Approved At', data.processing.approved_at) + detailItem('Remarks', data.processing.remarks) +
                        '</div><hr><h6 class="fw-bold mb-3">Attendance</h6><div class="row">' +
                        detailItem('Total Leave Taken', data.attendance.total_leave_taken) +
                        detailItem('Unauthorized Leaves', data.attendance.unauthorized_leaves) +
                        detailItem('Total Shifts Completed', data.attendance.total_shifts_completed) +
                        detailItem('Total Working Days', data.attendance.total_working_days) +
                        '</div><hr><div class="row"><div class="col-lg-6 mb-3"><h6 class="fw-bold">Earning Components</h6>' +
                        '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Component</th><th class="text-end">Amount</th></tr></thead><tbody>' +
                        components + '</tbody></table></div></div><div class="col-lg-6"><h6 class="fw-bold mb-3">Salary Summary</h6><div class="row">' +
                        detailItem('Gross Salary', money(data.salary.gross_salary)) + detailItem('Incentive (Included)', money(data.salary.incentive)) +
                        detailItem('Deduction', money(data.salary.deduction)) + detailItem('LOP', money(data.salary.lop)) +
                        detailItem('Net Salary', money(data.salary.net_salary)) + '</div></div></div>';
                }

                var table = $('#salaryReportTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('salary-reports.index') }}",
                        data: function (data) {
                            $.extend(data, filterData());
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'user_code', name: 'user_code', orderable: false, className: 'text-center' },
                        { data: 'user_name', name: 'user_name', orderable: false, className: 'text-center' },
                        { data: 'month_name', name: 'salaryProcessing.month', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'year', name: 'salaryProcessing.year', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'depot_name', name: 'salaryProcessing.depot.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'role_name', name: 'salaryProcessing.role.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'basic_salary', name: 'basic_salary', className: 'text-center' },
                        { data: 'deduction', name: 'deduction', className: 'text-center' },
                        { data: 'lop', name: 'lop', className: 'text-center' },
                        { data: 'net_salary', name: 'net_salary', className: 'text-center' },
                        { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ]
                });

                filters.on('change', function () {
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function () {
                    $('#yearFilter').val(currentYear);
                    $('#monthFilter, #depotFilter, #roleFilter').val('');
                    table.ajax.reload();
                });

                $(document).on('click', '.view-salary', function () {
                    $('#salaryDetailContent').html('<p class="text-center text-muted mb-0">Loading...</p>');
                    $('#salaryDetailModal').modal('show');

                    $.get($(this).data('url')).done(function (data) {
                        $('#salaryDetailContent').html(renderDetails(data));
                    }).fail(function () {
                        $('#salaryDetailContent').html('<p class="text-center text-danger mb-0">Unable to load salary details.</p>');
                    });
                });

                $('#exportSalaryReport').on('click', function () {
                    var button = $(this);
                    var originalText = button.text();
                    button.prop('disabled', true).text('Exporting...');

                    $.ajax({
                        url: "{{ route('salary-reports.export') }}",
                        type: 'GET',
                        data: filterData(),
                        xhrFields: { responseType: 'blob' },
                        success: function (data, status, xhr) {
                            var disposition = xhr.getResponseHeader('Content-Disposition') || '';
                            var match = disposition.match(/filename="?([^";]+)"?/);
                            var fileName = match && match[1] ? match[1] : 'salary-report.xlsx';
                            var url = window.URL.createObjectURL(new Blob([data]));
                            var link = document.createElement('a');
                            link.href = url;
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(link);
                        },
                        error: function () {
                            showToast('error', 'Salary report export failed.');
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                $('#salaryReportFilterForm').on('submit', function (event) {
                    event.preventDefault();
                    table.ajax.reload();
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .salary-report-scroll {
                overflow-x: auto;
                width: 100%;
            }

            .salary-report-scroll table {
                min-width: 1350px;
            }

            #salaryDetailModal .modal-body {
                max-height: 75vh;
            }
        </style>
    @endsection
</x-app-layout>
