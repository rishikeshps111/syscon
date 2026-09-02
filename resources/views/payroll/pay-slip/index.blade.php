@section('title')
    Generate Pay Slip
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Generate Pay Slip</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Generate Pay Slip</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <form id="paySlipForm" method="GET" action="{{ route('salary-slips.preview') }}">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="year">Year <span class="text-danger">*</span></label>
                        <select name="year" id="year" class="form-select shadow-none pay-slip-filter" required>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="month">Month <span class="text-danger">*</span></label>
                        <select name="month" id="month" class="form-select shadow-none pay-slip-filter" required>
                            <option value="">--- Select ---</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="depot_id">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="depot_id" class="form-select shadow-none pay-slip-filter" required>
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none pay-slip-filter" required>
                            <option value="">--- Select ---</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-8 o-f-inp mb-3">
                        <label for="user_id">User <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-select shadow-none" required disabled>
                            <option value="">Select filters first</option>
                        </select>
                    </div>
                    <div class="col-lg-4 btns-group-container mb-3" style="justify-content:flex-start !important;">
                        <button type="button" id="resetPaySlip" class="fil-btn">Reset</button>
                        <button type="submit" id="generatePaySlip" class="exp-btn m-0"
                            data-loading-text="Generating...">
                            Generate Pay Slip
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal fade" id="paySlipModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header between-cs-modal">
                        <h5 class="modal-title">Generated Pay Slip</h5>
                        <div class="report-excel">
                            <a href="#" id="downloadPaySlipPdf" class="disabled" aria-disabled="true"
                                data-loading-text="Downloading...">
                                Download PDF
                            </a>
                        </div>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="paySlipModalBody">
                        <div class="text-center py-5 text-muted">Generate a pay slip to view details.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var userSelect = $('#user_id');
                var paySlipModal = new bootstrap.Modal(document.getElementById('paySlipModal'));

                function setLoading(element) {
                    var $element = $(element);
                    var loadingText = $element.data('loading-text') || 'Loading...';

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

                function userFiltersReady() {
                    return $('#depot_id').val() && $('#role_id').val();
                }

                function resetUsers(message) {
                    userSelect.prop('disabled', true).html('<option value="">' + message + '</option>').val('');
                }

                function setDownloadState(enabled, url) {
                    $('#downloadPaySlipPdf')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? url : '#');
                }

                function loadUsers() {
                    if (!userFiltersReady()) {
                        resetUsers('Select depo and role first');
                        return;
                    }

                    resetUsers('Loading users...');

                    $.get(@json(route('salary-slips.users')), {
                        depot_id: $('#depot_id').val(),
                        role_id: $('#role_id').val()
                    }).done(function (users) {
                        if (!users.length) {
                            resetUsers('No users found');
                            showToast('warning', 'No users found for the selected role and depo.');
                            return;
                        }

                        userSelect.prop('disabled', false).html('<option value="">--- Select ---</option>' + users.map(function (user) {
                            return '<option value="' + user.id + '">' + $('<div>').text(user.text).html() + '</option>';
                        }).join(''));
                    }).fail(function (xhr) {
                        resetUsers('Unable to load users');
                        showToast('error', xhr.responseJSON?.message || 'Unable to load users.');
                    });
                }

                $(document).on('change', '.pay-slip-filter', loadUsers);

                $('#resetPaySlip').on('click', function () {
                    $('#month, #depot_id, #role_id').val('');
                    $('#year').val(@json(date('Y')));
                    resetUsers('Select depo and role first');
                    $('#paySlipModalBody').html('<div class="text-center py-5 text-muted">Generate a pay slip to view details.</div>');
                    setDownloadState(false, '#');
                });

                $('#paySlipForm').on('submit', function (event) {
                    event.preventDefault();

                    if (!this.checkValidity()) {
                        this.reportValidity();
                        return;
                    }

                    var form = $(this);
                    var button = $('#generatePaySlip');
                    setLoading(button);
                    $('#paySlipModalBody').html('<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generating pay slip...</div>');
                    setDownloadState(false, '#');
                    paySlipModal.show();

                    $.get(form.attr('action'), form.serialize())
                        .done(function (response) {
                            $('#paySlipModalBody').html(response.html);
                            setDownloadState(response.success, response.download_pdf_url);
                            showToast('success', 'Pay slip generated successfully.');
                        })
                        .fail(function (xhr) {
                            $('#paySlipModalBody').html('<div class="alert alert-danger mb-0">Unable to generate pay slip.</div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to generate pay slip.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#downloadPaySlipPdf').on('click', function (event) {
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
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .pay-slip-preview {
                background: #f6f8fb;
                border: 1px solid #e6ebf2;
                border-radius: 8px;
                color: #111827;
                padding: 18px;
            }

            .pay-slip-header {
                align-items: flex-start;
                background: #ffffff;
                border: 1px solid #e6ebf2;
                border-radius: 8px;
                display: flex;
                gap: 16px;
                justify-content: space-between;
                margin-bottom: 14px;
                padding: 18px;
            }

            .pay-slip-brand {
                color: #2563eb;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0;
                margin-bottom: 6px;
            }

            .pay-slip-header h4 {
                font-size: 24px;
                font-weight: 800;
                margin: 0;
            }

            .pay-slip-header p,
            .pay-slip-header small,
            .pay-slip-summary-strip small {
                color: #6b7280;
                margin: 0;
            }

            .pay-slip-header-meta {
                align-items: flex-end;
                display: flex;
                flex-direction: column;
                gap: 5px;
                text-align: right;
            }

            .pay-slip-status {
                border-radius: 999px;
                font-size: 12px;
                font-weight: 700;
                padding: 4px 10px;
            }

            .pay-slip-status-success {
                background: #dcfce7;
                color: #166534;
            }

            .pay-slip-status-warning {
                background: #fef3c7;
                color: #92400e;
            }

            .pay-slip-summary-strip {
                display: grid;
                gap: 12px;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-bottom: 14px;
            }

            .pay-slip-summary-strip > div,
            .pay-slip-panel {
                background: #ffffff;
                border: 1px solid #e6ebf2;
                border-radius: 8px;
            }

            .pay-slip-summary-strip > div {
                min-height: 92px;
                padding: 14px;
            }

            .pay-slip-summary-strip span,
            .pay-slip-detail-list dt,
            .pay-slip-total-row span {
                color: #6b7280;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .pay-slip-summary-strip strong {
                display: block;
                font-size: 20px;
                font-weight: 800;
                line-height: 1.25;
                margin-top: 5px;
            }

            .pay-slip-net {
                background: #111827 !important;
                border-color: #111827 !important;
                color: #ffffff;
            }

            .pay-slip-net span,
            .pay-slip-net small {
                color: #cbd5e1;
            }

            .pay-slip-grid {
                display: grid;
                gap: 14px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin-bottom: 14px;
            }

            .pay-slip-grid-wide {
                grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
            }

            .pay-slip-panel {
                height: 100%;
                padding: 16px;
            }

            .pay-slip-panel-title {
                border-bottom: 1px solid #edf1f7;
                font-size: 14px;
                font-weight: 800;
                margin-bottom: 10px;
                padding-bottom: 10px;
            }

            .pay-slip-detail-list {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                margin: 0;
            }

            .pay-slip-detail-list div {
                min-width: 0;
            }

            .pay-slip-detail-list dd {
                font-weight: 700;
                margin: 2px 0 0;
                overflow-wrap: anywhere;
            }

            .pay-slip-table {
                border: 1px solid #edf1f7;
            }

            .pay-slip-table thead th {
                background: #f8fafc;
                border-bottom: 1px solid #edf1f7;
                color: #4b5563;
                font-size: 12px;
                text-transform: uppercase;
            }

            .pay-slip-table td,
            .pay-slip-table th {
                padding: 10px 12px;
            }

            .pay-slip-totals {
                background: #fbfdff;
            }

            .pay-slip-total-row {
                align-items: center;
                border-bottom: 1px solid #edf1f7;
                display: flex;
                justify-content: space-between;
                padding: 9px 0;
            }

            .pay-slip-total-row strong {
                font-size: 15px;
            }

            .pay-slip-grand-total {
                background: #111827;
                border: 0;
                border-radius: 8px;
                color: #ffffff;
                margin-top: 10px;
                padding: 14px;
            }

            .pay-slip-grand-total span {
                color: #cbd5e1;
            }

            .pay-slip-grand-total strong {
                font-size: 20px;
            }

            .pay-slip-payment {
                margin-bottom: 0;
            }

            .pay-slip-payment-list {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            @media (max-width: 991.98px) {
                .pay-slip-summary-strip,
                .pay-slip-grid,
                .pay-slip-grid-wide,
                .pay-slip-payment-list {
                    grid-template-columns: 1fr;
                }

                .pay-slip-header {
                    flex-direction: column;
                }

                .pay-slip-header-meta {
                    align-items: flex-start;
                    text-align: left;
                }
            }
        </style>
    @endsection
</x-app-layout>
