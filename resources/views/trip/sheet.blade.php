@section('title')
    Trip Sheet
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Trip Sheet</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">Trip Sheet</li>
                </ol>
            </nav>
        </div>

        <form method="POST" action="{{ route('trips.sheet.store', $record->id) }}" id="tripSheetForm">
            @csrf
            <div class="main-table-container mb-3">
                <div class="row">
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>Trip No</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>From Date</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->from_date?->format('d/m/Y') }}" disabled>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>To Date</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->to_date?->format('d/m/Y') }}" disabled>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>Trip</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                    </div>
                    <div class="col-lg-12 o-f-inp">
                        <label>Stops</label>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @forelse($record->route?->stops ?? [] as $stop)
                                @if(! $loop->first)
                                    <span class="d-inline-flex align-items-center text-muted">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </span>
                                @endif
                                <span class="btn btn-sm btn-outline-secondary disabled">{{ $stop->name }}</span>
                            @empty
                                <span class="btn btn-sm btn-light text-muted disabled">No stops selected</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-table-container">
                <div class="table-over field-table mt-3">
                    <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent" id="sheetTable">
                        <thead>
                            <tr>
                                <th class="text-center nowrap">SL No</th>
                                <th class="text-center nowrap">Date</th>
                                <th class="text-center nowrap">Departure Time</th>
                                <th class="text-center nowrap">Arrival Time</th>
                                <th class="text-center nowrap">Actual Start Time</th>
                                <th class="text-center nowrap">Actual Reach Time</th>
                                <th class="text-center nowrap">Verified By</th>
                                <th class="text-center nowrap">Approved By</th>
                                <th class="text-center nowrap">Shift</th>
                                <th class="text-center nowrap">Driver Name</th>
                                <th class="text-center nowrap">Vehicle No</th>
                                <th class="text-center nowrap">Notes</th>
                                <th class="text-center nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $rows = $entries->isNotEmpty() ? $entries : collect([null]);
                            @endphp
                            @foreach($rows as $index => $entry)
                                @include('trip.sheet-row', ['index' => $index, 'entry' => $entry])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-12 mt-3 text-center">
                    <button type="submit" class="btn btn-primary" id="tripSheetSubmitBtn">Save</button>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                initSheetSelect2();

                $(document).on('change', '.departure-time', function () {
                    $(this).closest('tr').find('.actual-start-time').val($(this).val());
                });

                $(document).on('change', '.arrival-time', function () {
                    $(this).closest('tr').find('.actual-reach-time').val($(this).val());
                });

                $(document).on('click', '.add-sheet-row', function () {
                    var row = buildSheetRow($(this).closest('tr'), false);
                    $(this).closest('tr').after(row);
                    reindexSheetRows();
                });

                $(document).on('click', '.remove-sheet-row', function () {
                    if ($('#sheetTable tbody tr').length === 1) {
                        return;
                    }
                    $(this).closest('tr').remove();
                    reindexSheetRows();
                });

                $(document).on('click', '.copy-sheet-row', function () {
                    var row = buildSheetRow($(this).closest('tr'), true);
                    $(this).closest('tr').after(row);
                    reindexSheetRows();
                });

                $('#tripSheetForm').on('submit', function () {
                    var button = $('#tripSheetSubmitBtn');
                    var originalText = button.data('original-text') || button.text();
                    button.data('original-text', originalText).prop('disabled', true).text('Please wait...');
                });
            });

            function buildSheetRow(sourceRow, keepValues) {
                sourceRow.find('.sheet-select').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });

                var row = sourceRow.clone();

                initSheetSelect2(sourceRow);
                cleanupSheetRowSelect2(row);

                if (!keepValues) {
                    row.find('input, textarea').val('');
                    row.find('select').val('');
                }

                initSheetSelect2(row);

                return row;
            }

            function cleanupSheetRowSelect2(row) {
                row.find('.select2').remove();
                row.find('.sheet-select')
                    .removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id')
                    .removeAttr('aria-hidden')
                    .removeAttr('tabindex');
                row.find('option').removeAttr('data-select2-id');
            }

            function initSheetSelect2(context) {
                var target = context ? $(context).find('.sheet-select') : $('.sheet-select');

                target.select2({
                    placeholder: '---Select---',
                    allowClear: true,
                    width: '100%'
                });
            }

            function reindexSheetRows() {
                $('#sheetTable tbody tr').each(function (index) {
                    $(this).find('.sheet-sl').text(index + 1);
                    $(this).find('input, select, textarea').each(function () {
                        $(this).attr('name', $(this).attr('name').replace(/entries\[\d+\]/, 'entries[' + index + ']'));
                    });
                });
            }
        </script>
    @endsection
</x-app-layout>
