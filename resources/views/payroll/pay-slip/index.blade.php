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
            <form id="paySlipForm" method="GET" action="{{ route('salary-slips.preview') }}" novalidate>
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="paySlipYear">Year <span class="text-danger">*</span></label>
                        <select name="year" id="paySlipYear" class="form-select shadow-none pay-slip-filter" required>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="paySlipMonth">Month <span class="text-danger">*</span></label>
                        <select name="month" id="paySlipMonth" class="form-select shadow-none pay-slip-filter" required>
                            <option value="">--- Select ---</option>
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="paySlipDepot">Depo <span class="text-danger">*</span></label>
                        <select name="depot_id" id="paySlipDepot" class="form-select shadow-none pay-slip-filter"
                            required>
                            <option value="">--- Select ---</option>
                            @foreach ($depots as $depot)
                                <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-4 o-f-inp mb-3">
                        <label for="paySlipType">Type</label>
                        <select id="paySlipType" class="form-select shadow-none">
                            <option value="individual">Individual</option>
                            <option value="bulk">Bulk</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-8 o-f-inp mb-3" id="paySlipUserField">
                        <label for="paySlipUser">User <span class="text-danger">*</span></label>
                        <select name="user_id" id="paySlipUser" class="form-select shadow-none" required disabled>
                            <option value="">Select filters first</option>
                        </select>
                    </div>
                    <div class="col-lg-4 btns-group-container mb-3" style="justify-content:flex-start !important;">
                        <button type="button" id="resetPaySlip" class="fil-btn">Reset</button>
                        <button type="submit" id="generatePaySlip" class="exp-btn m-0"
                            data-loading-text="Generating...">
                            Generate Pay Slip
                        </button>
                        <button type="button" id="downloadPaySlipZip" class="exp-btn m-0 d-none"
                            style="font-size: 11px !important" data-loading-text="Downloading...">Download Payslips
                            ZIP</button>
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
                var userSelect = $('#paySlipUser');
                var paySlipModal = null;
                $('#paySlipForm select').select2({ width: '100%' });
                var previewVersion = 0;

                function clearPreview() {
                    ++previewVersion;
                    setDownloadState(false, '#');
                    $('#paySlipModalBody').html('<div class="text-center py-5 text-muted">Generate a pay slip to view details.</div>');
                    if (paySlipModal) paySlipModal.hide();
                }

                function updateType() {
                    var bulk = $('#paySlipType').val() === 'bulk';
                    $('#paySlipUserField, #generatePaySlip').toggleClass('d-none', bulk);
                    $('#downloadPaySlipZip').toggleClass('d-none', !bulk);
                    userSelect.prop('required', !bulk);
                    clearPreview();
                }
                $('#paySlipType').on('change', updateType);
                userSelect.on('change', clearPreview);
                updateType();

                function showPaySlipModal() {
                    if (!paySlipModal && window.bootstrap) {
                        paySlipModal = new bootstrap.Modal(document.getElementById('paySlipModal'));
                    }
                    if (paySlipModal) {
                        paySlipModal.show();
                    }
                }

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
                    return Boolean($('#paySlipYear').val() && $('#paySlipMonth').val() && $('#paySlipDepot').val());
                }

                function resetUsers(message) {
                    userSelect.prop('disabled', true).html('<option value="">' + message + '</option>').val('').trigger('change.select2');
                }

                function setDownloadState(enabled, url) {
                    $('#downloadPaySlipPdf')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? url : '#');
                }

                var usersRequest = null;
                var usersRequestVersion = 0;

                function loadUsers() {
                    var version = ++usersRequestVersion;
                    if (usersRequest) usersRequest.abort();
                    clearPreview();

                    if (!userFiltersReady()) {
                        resetUsers('Select year, month and depot first');
                        return;
                    }

                    resetUsers('Loading users...');

                    usersRequest = $.getJSON(@json(route('salary-slips.users')), {
                        year: $('#paySlipYear').val(),
                        month: $('#paySlipMonth').val(),
                        depot_id: $('#paySlipDepot').val()
                    }).done(function (users) {
                        if (version !== usersRequestVersion) return;
                        if (!users.length) {
                            resetUsers('No users found');
                            showToast('warning', 'No approved salary processing found for the selected period and depot.');
                            return;
                        }

                        userSelect.prop('disabled', false).html('<option value="">--- Select ---</option>' + users.map(function (user) {
                            return '<option value="' + user.id + '">' + $('<div>').text(user.text).html() + '</option>';
                        }).join('')).trigger('change.select2');
                    }).fail(function (xhr, status) {
                        if (status === 'abort' || version !== usersRequestVersion) return;
                        resetUsers('Unable to load users');
                        showToast('error', xhr.responseJSON?.message || 'Unable to load users.');
                    });
                }


                $('#paySlipYear, #paySlipMonth, #paySlipDepot').on('change', function () {
                    loadUsers();
                });

                $('#resetPaySlip').on('click', function () {
                    ++usersRequestVersion;
                    if (usersRequest) usersRequest.abort();
                    $('#paySlipMonth, #paySlipDepot').val('').trigger('change.select2');
                    $('#paySlipYear').val(@json(date('Y'))).trigger('change.select2');
                    $('#paySlipType').val('individual').trigger('change.select2');
                    updateType();
                    resetUsers('Select year, month and depot first');
                    $('#paySlipModalBody').html('<div class="text-center py-5 text-muted">Generate a pay slip to view details.</div>');
                    setDownloadState(false, '#');
                });

                $('#paySlipForm').on('submit', function (event) {
                    event.preventDefault();
                    if ($('#paySlipType').val() === 'bulk') {
                        $('#downloadPaySlipZip').trigger('click');
                        return;
                    }

                    if (!userFiltersReady() || !userSelect.val()) {
                        showToast('warning', 'Select year, month, depot and employee.');
                        return;
                    }

                    var form = $(this);
                    var button = $('#generatePaySlip');
                    var version = ++previewVersion;
                    setLoading(button);
                    $('#paySlipModalBody').html('<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generating pay slip...</div>');
                    setDownloadState(false, '#');
                    showPaySlipModal();

                    $.get(form.attr('action'), form.serialize())
                        .done(function (response) {
                            if (version !== previewVersion) return;
                            $('#paySlipModalBody').html(response.html);
                            setDownloadState(response.success, response.download_pdf_url);
                            showToast('success', 'Pay slip generated successfully.');
                        })
                        .fail(function (xhr) {
                            if (version !== previewVersion) return;
                            $('#paySlipModalBody').html('<div class="alert alert-danger mb-0">Unable to generate pay slip.</div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to generate pay slip.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#downloadPaySlipZip').on('click', async function () {
                    if (!userFiltersReady()) {
                        showToast('warning', 'Select year, month and depot.');
                        return;
                    }
                    var button = this;
                    var filters = {
                        year: $('#paySlipYear').val(), month: $('#paySlipMonth').val(),
                        depot_id: $('#paySlipDepot').val()
                    };
                    setLoading(button);
                    try {
                        var response = await fetch(@json(route('salary-slips.zip')), {
                            method: 'POST', credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                            body: JSON.stringify(filters)
                        });
                        if (!response.ok) {
                            var error = await response.json().catch(function () { return {}; });
                            throw new Error(error.message || 'Unable to download payslip ZIP.');
                        }
                        var blob = await response.blob();
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = 'payslips-depot-' + filters.depot_id + '-' + filters.year + '-' + String(filters.month).padStart(2, '0') + '.zip';
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                    } catch (error) {
                        showToast('error', error.message);
                    } finally {
                        resetLoading(button);
                    }
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
            #paySlipForm #generatePaySlip.d-none,
            #paySlipForm #downloadPaySlipZip.d-none {
                display: none !important;
            }

            #paySlipModalBody { padding: 20px; background: #fff; }
            @media (max-width: 575px) { #paySlipModalBody { padding: 8px; } }
        </style>
    @endsection
</x-app-layout>