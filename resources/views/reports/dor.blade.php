@section('title')
    DOR Report
@endsection

<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>DOR Report</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">DOR Report</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <form id="dorReportFilterForm" action="{{ route('reports.dor.index') }}" method="GET">
                        <input type="hidden" name="generate" value="1">
                        <div class="row align-items-end">
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="date_from">From Date</label>
                                    <input type="date" id="date_from" name="date_from" class="form-control shadow-none"
                                        value="{{ $filters['date_from'] ?? '' }}">
                                    @error('date_from')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="date_to">To Date</label>
                                    <input type="date" id="date_to" name="date_to" class="form-control shadow-none"
                                        value="{{ $filters['date_to'] ?? '' }}">
                                    @error('date_to')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="depot_id">Depot</label>
                                    <select id="depot_id" name="depot_id" class="form-select shadow-none select2-filter">
                                        <option value="">All</option>
                                        @foreach($depots as $depot)
                                            <option value="{{ $depot->id }}" @selected((string) ($filters['depot_id'] ?? '') === (string) $depot->id)>{{ $depot->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('depot_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="trip_id">Trip</label>
                                    <select id="trip_id" name="trip_id" class="form-select shadow-none select2-filter">
                                        <option value="">All</option>
                                        @foreach($trips as $trip)
                                            <option value="{{ $trip->id }}" @selected((string) ($filters['trip_id'] ?? '') === (string) $trip->id)>
                                                {{ trim(($trip->code ? $trip->code . ' - ' : '') . $trip->trip_title) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trip_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="vehicle_id">Vehicle No</label>
                                    <select id="vehicle_id" name="vehicle_id" class="form-select shadow-none select2-filter">
                                        <option value="">All</option>
                                        @foreach($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" @selected((string) ($filters['vehicle_id'] ?? '') === (string) $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
                                        @endforeach
                                    </select>
                                    @error('vehicle_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="driver_profile_id">Driver</label>
                                    <select id="driver_profile_id" name="driver_profile_id" class="form-select shadow-none select2-filter">
                                        <option value="">All</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}" @selected((string) ($filters['driver_profile_id'] ?? '') === (string) $driver->id)>
                                                {{ trim(($driver->user?->code ? $driver->user->code . ' - ' : '') . ($driver->user?->name ?? '')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('driver_profile_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-8 mb-3">
                                <div class="o-f-inp">
                                    <label>DOR Columns</label>
                                    <div id="selectedDorColumnsInputs"></div>
                                    <div class="dor-column-picker">
                                        <div>
                                            <strong id="selectedDorColumnsCount">{{ count($selectedColumns) ?: count($dorColumns) }}</strong>
                                            <span>columns selected</span>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#dorColumnModal">
                                            Choose Columns
                                        </button>
                                    </div>
                                    <div id="selectedDorColumnsPreview" class="dor-column-preview"></div>
                                </div>
                            </div>
                            <div class="col-lg-12 mb-3">
                                <div class="filter-btns-top justify-content-end">
                                    <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                                    <button type="submit" class="btn btn-primary" data-loading-text="Generating...">Generate Report</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="text-muted small">Select filters if needed, then generate. Leave filters empty to show all DOR records.</div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="dorReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Generated DOR Report</h5>
                        <div class="d-flex flex-wrap gap-2 ms-auto me-3">
                            <a href="#" id="downloadDorReportExcel" class="btn btn-success disabled" aria-disabled="true"
                                data-loading-text="Downloading...">
                                Download Excel
                            </a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="dorReportModalBody">
                        <div class="text-center py-5 text-muted">Generate a report to view details.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="dorColumnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Choose DOR Columns</h5>
                        <div class="d-flex flex-wrap gap-2 ms-auto me-3">
                            <button type="button" id="checkAllDorColumns" class="btn btn-sm btn-primary">Check All</button>
                            <button type="button" id="uncheckAllDorColumns" class="btn btn-sm btn-secondary">Uncheck All</button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="dor-column-grid">
                            @foreach($dorColumns as $value => $label)
                                <label class="dor-column-option">
                                    <input type="checkbox" class="dor-column-check" value="{{ $value }}"
                                        data-label="{{ $label }}"
                                        @checked(empty($selectedColumns) || in_array($value, $selectedColumns, true))>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Apply Columns</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var reportModal = new bootstrap.Modal(document.getElementById('dorReportModal'));
                var form = $('#dorReportFilterForm');
                var filters = $('#date_from, #date_to, #depot_id, #trip_id, #vehicle_id, #driver_profile_id');

                $('.select2-filter').select2({
                    width: '100%',
                    placeholder: '---Select---',
                    allowClear: true
                });

                function syncSelectedColumns() {
                    var selected = $('.dor-column-check:checked').map(function () {
                        return {
                            value: $(this).val(),
                            label: $(this).data('label')
                        };
                    }).get();

                    $('#selectedDorColumnsInputs').html(selected.map(function (column) {
                        return '<input type="hidden" name="columns[]" value="' + column.value + '">';
                    }).join(''));

                    $('#selectedDorColumnsCount').text(selected.length);
                    $('#selectedDorColumnsPreview').text(
                        selected.length
                            ? selected.slice(0, 8).map(function (column) { return column.label; }).join(', ') + (selected.length > 8 ? ' +' + (selected.length - 8) + ' more' : '')
                            : 'No extra DOR columns selected'
                    );
                }

                syncSelectedColumns();

                $('.dor-column-check').on('change', syncSelectedColumns);

                $('#checkAllDorColumns').on('click', function () {
                    $('.dor-column-check').prop('checked', true);
                    syncSelectedColumns();
                });

                $('#uncheckAllDorColumns').on('click', function () {
                    $('.dor-column-check').prop('checked', false);
                    syncSelectedColumns();
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
                    $('#downloadDorReportExcel')
                        .toggleClass('disabled', !enabled)
                        .attr('aria-disabled', enabled ? 'false' : 'true')
                        .attr('href', enabled ? url : '#');
                }

                $('#resetFilters').on('click', function () {
                    filters.val('');
                    $('.select2-filter').trigger('change.select2');
                    $('.dor-column-check').prop('checked', true);
                    syncSelectedColumns();
                    $('#dorReportModalBody').html('<div class="text-center py-5 text-muted">Generate a report to view details.</div>');
                    setDownloadState(false, '#');
                });

                form.on('submit', function (event) {
                    event.preventDefault();

                    var button = form.find('button[type="submit"]').first();
                    setLoading(button);
                    $('#dorReportModalBody').html('<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Generating report...</div>');
                    setDownloadState(false, '#');
                    reportModal.show();

                    $.get(form.attr('action'), form.serialize())
                        .done(function (response) {
                            $('#dorReportModalBody').html(response.html);
                            setDownloadState(response.success, response.download_excel_url);

                            if (response.message) {
                                showToast(response.success ? 'success' : 'warning', response.message);
                            }
                        })
                        .fail(function (xhr) {
                            $('#dorReportModalBody').html('<div class="alert alert-danger mb-0">Unable to generate DOR report.</div>');
                            showToast('error', xhr.responseJSON?.message || 'Unable to generate DOR report.');
                        })
                        .always(function () {
                            resetLoading(button);
                        });
                });

                $('#downloadDorReportExcel').on('click', function (event) {
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
            .dor-report-scroll {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .dor-report-table {
                font-size: 12px;
                min-width: 1000px;
            }

            .dor-report-table th,
            .dor-report-table td {
                vertical-align: middle;
                white-space: nowrap;
            }

            .dor-column-picker {
                align-items: center;
                border: 1px solid #dfe3ea;
                border-radius: 6px;
                display: flex;
                justify-content: space-between;
                min-height: 42px;
                padding: 7px 10px;
            }

            .dor-column-picker strong {
                color: #1f2937;
                font-size: 16px;
            }

            .dor-column-picker span,
            .dor-column-preview {
                color: #6b7280;
                font-size: 12px;
            }

            .dor-column-preview {
                line-height: 1.35;
                margin-top: 6px;
            }

            .dor-column-grid {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                max-height: 60vh;
                overflow-y: auto;
                padding-right: 4px;
            }

            .dor-column-option {
                align-items: flex-start;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                display: flex;
                gap: 8px;
                min-height: 40px;
                padding: 9px 10px;
            }

            .dor-column-option input {
                margin-top: 3px;
            }

            .dor-column-option span {
                color: #374151;
                font-size: 13px;
                line-height: 1.25;
            }
        </style>
    @endsection
</x-app-layout>
