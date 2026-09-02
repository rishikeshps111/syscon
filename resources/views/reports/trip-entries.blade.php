@section('title', $reportTitle)
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $reportTitle }}</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item">Reports</li><li class="breadcrumb-item active">{{ $reportTitle }}</li></ol></nav>
        </div>

        <div class="main-table-container">
            <form id="tripEntryReportForm" action="{{ route($indexRoute) }}" method="GET">
                <input type="hidden" name="generate" value="1">
                <div class="row ">
                    <div class="col-lg-12 col-md-6 mb-3 o-f-inp">
                        <label for="report_entity_id">{{ $selectorLabel }} <span class="text-danger">*</span></label>
                        <select id="report_entity_id" name="{{ $filterKey }}" class="form-select shadow-none select2-filter" required>
                            <option value="">---Select {{ $selectorLabel }}---</option>
                            @foreach($selectorOptions as $option)
                                <option value="{{ $option['id'] }}" @selected((string)($filters[$filterKey] ?? '') === (string)$option['id'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 mb-3 o-f-inp">
                        <div class="col-box-cs">
                             <label>Report Columns</label>
                        <div id="selectedTripColumnsInputs" class="col-box-top"></div>
                        <div class="report-column-picker col-box-middle">
                            <div class="col-box-bottom"><strong id="selectedTripColumnsCount">{{ count($selectedColumns) }}</strong> <span>columns selected</span></div>
                            <button type="button" class="col-box-button" data-bs-toggle="modal" data-bs-target="#tripColumnModal">Choose Columns</button>
                        </div>
                        <div id="selectedTripColumnsPreview" class="report-column-preview col-box-preview"></div>
                        </div>
                       
                    </div>
                    <div class="col-lg-4 mb-3 ms-auto">
                        <div class="btns-group-container">
                            <button type="button" id="resetTripReport" class="fil-btn">Reset</button>
                            <button type="submit" class="exp-btn m-0" data-loading-text="Generating...">Generate</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal fade" id="tripEntryReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header between-cs-modal" >
                        <h5 class="modal-title">Generated {{ $reportTitle }}</h5>
                       <div class="report-excel">
                            <a href="#" id="downloadTripEntryReport" class=" disabled ms-auto me-3" aria-disabled="true">Download Excel</a>
                       </div>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="tripEntryReportBody"><div class="text-center py-5 text-muted">Generate a report to view details.</div></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="tripColumnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header between-cs-modal">
                        <h5 class="modal-title">Choose Trip Entry and DOR Columns</h5>
                        <div class="check-btns-modal">
                            <button type="button" id="checkAllTripColumns" class="btnCheck1">Check All</button>
                            <button type="button" id="uncheckAllTripColumns" class="btnCheck2">Uncheck All</button>
                        </div>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body check-modl"><div class="report-column-grid">
                        @foreach($reportColumns as $value => $label)
                            <label class="report-column-option"><input type="checkbox" class="trip-column-check" value="{{ $value }}" data-label="{{ $label }}" @checked(in_array($value, $selectedColumns, true))><span>{{ $label }}</span></label>
                        @endforeach
                    </div></div>
                    <div class="modal-btns-last p-3"><button type="button" class="modal-btn-2" data-bs-dismiss="modal">Apply Columns</button></div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var form = $('#tripEntryReportForm');
                var reportModal = new bootstrap.Modal(document.getElementById('tripEntryReportModal'));
                $('.select2-filter').select2({ width: '100%', placeholder: '---Select {{ $selectorLabel }}---', allowClear: true });

                function syncColumns() {
                    var selected = $('.trip-column-check:checked').map(function () { return { value: this.value, label: $(this).data('label') }; }).get();
                    $('#selectedTripColumnsInputs').html(selected.map(function (item) { return '<input type="hidden" name="columns[]" value="' + item.value + '">'; }).join(''));
                    $('#selectedTripColumnsCount').text(selected.length);
                    $('#selectedTripColumnsPreview').text(selected.length ? selected.slice(0, 6).map(function (item) { return item.label; }).join(', ') + (selected.length > 6 ? ' +' + (selected.length - 6) + ' more' : '') : 'No columns selected');
                }

                function loading(button, enabled, label) {
                    var item = $(button);
                    if (enabled) {
                        item.data('original-html', item.html()).prop('disabled', true).addClass('disabled').html('<span class="spinner-border spinner-border-sm me-1"></span>' + label);
                    } else {
                        item.prop('disabled', false).removeClass('disabled').html(item.data('original-html'));
                    }
                }

                syncColumns();
                $('.trip-column-check').on('change', syncColumns);
                $('#checkAllTripColumns').on('click', function () { $('.trip-column-check').prop('checked', true); syncColumns(); });
                $('#uncheckAllTripColumns').on('click', function () { $('.trip-column-check').prop('checked', false); syncColumns(); });

                $('#resetTripReport').on('click', function () {
                    $('#report_entity_id').val('').trigger('change.select2');
                    $('.trip-column-check').prop('checked', true);
                    syncColumns();
                    $('#downloadTripEntryReport').addClass('disabled').attr('href', '#');
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    if (!$('#report_entity_id').val()) { showToast('warning', 'Please select a {{ strtolower($selectorLabel) }}.'); return; }
                    if (!$('.trip-column-check:checked').length) { showToast('warning', 'Please choose at least one column.'); return; }
                    var button = form.find('button[type="submit"]');
                    loading(button, true, 'Generating...');
                    $('#tripEntryReportBody').html('<div class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Generating report...</div>');
                    reportModal.show();
                    $.get(form.attr('action'), form.serialize()).done(function (response) {
                        $('#tripEntryReportBody').html(response.html);
                        $('#downloadTripEntryReport').toggleClass('disabled', !response.success).attr('href', response.success ? response.download_excel_url : '#');
                        showToast(response.success ? 'success' : 'warning', response.message);
                    }).fail(function (xhr) {
                        $('#tripEntryReportBody').html('<div class="alert alert-danger">Unable to generate trip report.</div>');
                        showToast('error', xhr.responseJSON?.message || 'Unable to generate trip report.');
                    }).always(function () { loading(button, false); });
                });

                $('#downloadTripEntryReport').on('click', function (event) {
                    if ($(this).hasClass('disabled')) { event.preventDefault(); return; }
                    var link = this;
                    loading(link, true, 'Downloading...');
                    setTimeout(function () { loading(link, false); }, 3500);
                });
            });
        </script>
    @endsection

    @section('styles')
        <style>
            .report-column-picker { align-items:center; border:1px solid #dfe3ea; border-radius:6px; display:flex; justify-content:space-between; min-height:42px; padding:7px 10px; }
            .report-column-preview, .report-column-picker span { color:#6b7280; font-size:12px; }
            .report-column-preview { margin-top:6px; }
            .report-column-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
            .report-column-option { align-items:flex-start; border:1px solid #e5e7eb; border-radius:6px; display:flex; gap:8px; padding:9px 10px; }
            .report-column-option input { margin-top:3px; }
            .trip-entry-report-scroll { overflow-x:auto; width:100%; }
            .trip-entry-report-table { font-size:12px; min-width:1000px; }
            .trip-entry-report-table th, .trip-entry-report-table td { white-space:nowrap; }
        </style>
    @endsection
</x-app-layout>
