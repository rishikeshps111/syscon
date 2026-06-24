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

        <div class="main-table-container mb-3">
            <form id="salaryReportForm" method="GET" action="{{ route('salary-reports.index') }}">
                <input type="hidden" name="generate" value="1">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="year">Year <span class="text-danger">*</span></label>
                        <select name="year" id="year" class="form-select shadow-none" required>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="month">Month <span class="text-danger">*</span></label>
                        <select name="month" id="month" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" @selected((int) $filters['month'] === (int) $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="depot_id">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) $filters['depot_id'] === (int) $depot->id)>
                                    {{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none" required>
                            <option value="">--- Select ---</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) $filters['role_id'] === (int) $role->id)>
                                    {{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-12 mt-2 d-flex gap-2 justify-content-end">
                        <button type="button" id="resetReportFilters" class="btn btn-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary" data-loading-text="Generating...">Generate
                            Report</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal fade" id="salaryReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generated Salary Report</h5>
                        <div class="d-flex flex-wrap gap-2 ms-auto me-3">
                            <a href="#" id="downloadReportExcel" class="btn btn-success disabled" aria-disabled="true"
                                data-loading-text="Downloading...">
                                Download Excel
                            </a>
                            <a href="#" id="downloadReportPdf" class="btn btn-danger disabled" aria-disabled="true"
                                data-loading-text="Downloading...">
                                Download PDF
                            </a>
                            <button type="button" id="sendReportMail" class="btn btn-primary" disabled>
                                Send PDF Mail
                            </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="salaryReportModalBody">
                        <div class="text-center py-5 text-muted">Generate a report to view details.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var reportModal = new bootstrap.Modal(document.getElementById('salaryReportModal'));
                var reportFilters = {};

                function setLoading(element, text) {
                    var $element = $(element);
                    var loadingText = text || $element.data('loading-text') || 'Loading...';

                    if (!$element.data('original-html')) {
                        $element.data('original-html', $element.html());
                    }

                    $element.addClass('disabled').attr('aria-disabled', 'true');

                    if ($element.is('button')) {
                        $element.prop('disabled', true);
                    }

                    $element.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText);
                }

                function resetLoading(element) {
                    var $element = $(element);

                    $element.removeClass('disabled').removeAttr('aria-disabled');

                    if ($element.is('button')) {
                        $element.prop('disabled', false);
                    }

                    if ($element.data('original-html')) {
                        $element.html($element.data('original-html'));
                    }
                }

                function setActionState(enabled, response) {
                    $('#downloadReportExcel')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? response.download_excel_url : '#');
                    $('#downloadReportPdf')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? response.download_pdf_url : '#');
                    $('#sendReportMail').prop('disabled', !enabled).data('url', enabled ? response.send_mail_url : '');
                }

                $('#salaryReportForm').on('submit', function (event) {
                    event.preventDefault();

                    var form = this;
                    var button = $(form).find('button[type="submit"]').first();
                    setLoading(button);
                    $('#salaryReportModalBody').html('<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generating report...</div>');
                    setActionState(false, {});
                    reportModal.show();

                    $.get($(form).attr('action'), $(form).serialize())
                        .done(function (response) {
                            reportFilters = response.filters || {};
                            $('#salaryReportModalBody').html(response.html);
                            setActionState(response.success, response);

                            if (response.message) {
                                showToast(response.success ? 'success' : 'warning', response.message);
                            }
                        })
                        .fail(function (xhr) {
                            $('#salaryReportModalBody').html('<div class="alert alert-danger mb-0">Unable to generate salary report.</div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to generate salary report.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#resetReportFilters').on('click', function () {
                    $('#month, #depot_id, #role_id').val('');
                    $('#year').val('{{ date('Y') }}');
                    $('#salaryReportModalBody').html('<div class="text-center py-5 text-muted">Generate a report to view details.</div>');
                    setActionState(false, {});
                });

                $('#downloadReportExcel, #downloadReportPdf').on('click', function (event) {
                    var link = this;

                    if ($(link).hasClass('disabled')) {
                        event.preventDefault();
                        return;
                    }

                    setLoading(link);

                    setTimeout(function () {
                        resetLoading(link);
                    }, 3500);
                });

                $('#sendReportMail').on('click', function () {
                    var button = this;
                    var url = $(button).data('url');

                    if (!url) {
                        return;
                    }

                    setLoading(button, 'Sending...');

                    $.post(url, $.extend({}, reportFilters, { _token: '{{ csrf_token() }}' }))
                        .done(function (response) {
                            showToast('success', response.message || 'Salary report mail sent successfully.');
                        })
                        .fail(function (xhr) {
                            showToast('error', xhr.responseJSON?.message || 'Unable to send salary report mail.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .salary-report-scroll {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .salary-report-table {
                min-width: 1500px;
                font-size: 12px;
            }

            .salary-report-table th,
            .salary-report-table td {
                vertical-align: middle;
                white-space: nowrap;
            }
        </style>
    @endsection
</x-app-layout>
