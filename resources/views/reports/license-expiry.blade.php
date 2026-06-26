@section('title')
    License Expiry Report
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>License Expiry Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">License Expiry Report</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <form id="licenseExpiryReportForm" method="GET" action="{{ route('reports.license-expiry.index') }}">
                <input type="hidden" name="generate" value="1">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="expiry_filter">Filter By <span class="text-danger">*</span></label>
                        <select id="expiry_filter" name="expiry_filter" class="form-select shadow-none select2-filter" required>
                            <option value="">--- Select ---</option>
                            @foreach($expiryFilters as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['expiry_filter'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('expiry_filter')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-3 col-md-6 o-f-inp mb-2">
                        <label for="depot_id">Filter by Depo</label>
                        <select id="depot_id" name="depot_id" class="form-select shadow-none select2-filter">
                            <option value="">All</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" @selected((int) ($filters['depot_id'] ?? 0) === (int) $depot->id)>{{ $depot->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 o-f-inp mb-2">
                        <label for="name">Filter by Name</label>
                        <input type="text" id="name" name="name" class="form-control shadow-none"
                            value="{{ $filters['name'] ?? '' }}" placeholder="Driver name">
                    </div>
                    <div class="col-lg-2 col-md-4 o-f-inp mb-2">
                        <label for="phone">Filter by Phone No</label>
                        <input type="text" id="phone" name="phone" class="form-control shadow-none"
                            value="{{ $filters['phone'] ?? '' }}" placeholder="Phone no">
                    </div>
                    <div class="col-lg-2 col-md-4 o-f-inp mb-2">
                        <label for="license">Filter by License</label>
                        <input type="text" id="license" name="license" class="form-control shadow-none"
                            value="{{ $filters['license'] ?? '' }}" placeholder="License no">
                    </div>
                    <div class="col-lg-12 col-md-4 mb-2">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" id="resetReportFilters" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary" data-loading-text="Generating...">Generate Report</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal fade" id="licenseExpiryReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generated License Expiry Report</h5>
                        <div class="d-flex flex-wrap gap-2 ms-auto me-3">
                            <a href="#" id="downloadLicenseExpiryExcel" class="btn btn-success disabled" aria-disabled="true"
                                data-loading-text="Downloading...">
                                Download Excel
                            </a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="licenseExpiryReportModalBody">
                        <div class="text-center py-5 text-muted">Generate a report to view details.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var reportModal = new bootstrap.Modal(document.getElementById('licenseExpiryReportModal'));
                var form = $('#licenseExpiryReportForm');

                $('.select2-filter').select2({
                    width: '100%',
                    placeholder: '---Select---',
                    allowClear: true
                });

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

                function setDownloadState(enabled, url) {
                    $('#downloadLicenseExpiryExcel')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? url : '#');
                }

                $('#resetReportFilters').on('click', function () {
                    $('#expiry_filter, #depot_id').val('').trigger('change.select2');
                    $('#name, #phone, #license').val('');
                    $('#licenseExpiryReportModalBody').html('<div class="text-center py-5 text-muted">Generate a report to view details.</div>');
                    setDownloadState(false, '#');
                });

                form.on('submit', function (event) {
                    event.preventDefault();

                    if (!$('#expiry_filter').val()) {
                        showToast('warning', 'Please select a filter before generating the report.');
                        return;
                    }

                    var button = form.find('button[type="submit"]').first();
                    setLoading(button);
                    $('#licenseExpiryReportModalBody').html('<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generating report...</div>');
                    setDownloadState(false, '#');
                    reportModal.show();

                    $.get(form.attr('action'), form.serialize())
                        .done(function (response) {
                            $('#licenseExpiryReportModalBody').html(response.html);
                            setDownloadState(response.success, response.download_excel_url);

                            if (response.message) {
                                showToast(response.success ? 'success' : 'warning', response.message);
                            }
                        })
                        .fail(function (xhr) {
                            $('#licenseExpiryReportModalBody').html('<div class="alert alert-danger mb-0">Unable to generate license expiry report.</div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to generate license expiry report.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#downloadLicenseExpiryExcel').on('click', function (event) {
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
            .license-expiry-report-scroll {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .license-expiry-report-table {
                font-size: 12px;
                min-width: 1100px;
            }

            .license-expiry-report-table th,
            .license-expiry-report-table td {
                vertical-align: middle;
                white-space: nowrap;
            }
        </style>
    @endsection
</x-app-layout>
