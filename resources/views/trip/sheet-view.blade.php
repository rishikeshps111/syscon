@section('title')
    View Trip Sheet
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>View Trip Sheet</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">View Trip Sheet</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>From Date</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d/m/Y') }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>To Date</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->to_date?->format('d/m/Y') }}"
                        disabled>
                </div>
                <div class="col-lg-12 o-f-inp">
                    <label>Stops</label>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @forelse($record->route?->stops ?? [] as $stop)
                            @if(!$loop->first)
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
            <div class="row mb-3">
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="dateFrom">From Date</label>
                    <input type="date" id="dateFrom" name="date_from" class="form-control shadow-none"
                        value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="dateTo">To Date</label>
                    <input type="date" id="dateTo" name="date_to" class="form-control shadow-none"
                        value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-lg-6 d-flex justify-content-end align-items-end mb-3">
                    <div class="btn-flex">
                        <button type="button" class="btn btn-danger" id="resetSheetFilters">Reset</button>
                        <a href="{{ route('trips.sheet.view', array_merge(['trip' => $record->id], $filters, ['export' => 'csv'])) }}"
                            class="btn btn-info" id="exportSheetCsv">Export CSV</a>
                        <a href="{{ route('trips.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent" id="sheetViewTable">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Date</th>
                            <th class="text-center nowrap">Code</th>
                            <th class="text-center nowrap">Status</th>
                            <th class="text-center nowrap">Side</th>
                            <th class="text-center nowrap">Departure Time</th>
                            <th class="text-center nowrap">Arrival Time</th>
                            <th class="text-center nowrap">Actual Start Time</th>
                            <th class="text-center nowrap">Actual Reach Time</th>
                            <th class="text-center nowrap">Starting Km</th>
                            <th class="text-center nowrap">Charge</th>
                            <th class="text-center nowrap">Vehicle Verified</th>
                            <th class="text-center nowrap">Driver Verified</th>
                            <th class="text-center nowrap">Supervisor Verified</th>
                            <th class="text-center nowrap">Driver Final Verified</th>
                            <th class="text-center nowrap">Notes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var sheetViewUrl = "{{ route('trips.sheet.view', $record->id) }}";
                var table = $('#sheetViewTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    pageLength: 10,
                    ordering: true,
                    responsive: true,
                    ajax: {
                        url: sheetViewUrl,
                        data: function (data) {
                            data.date_from = $('#dateFrom').val();
                            data.date_to = $('#dateTo').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center' },
                        { data: 'code', name: 'trip_sheets.code', className: 'text-center' },
                        { data: 'status', name: 'trip_sheets.status', className: 'text-center' },
                        { data: 'side', name: 'side', className: 'text-center' },
                        { data: 'departure_time', name: 'departure_time', className: 'text-center' },
                        { data: 'arrival_time', name: 'arrival_time', className: 'text-center' },
                        { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        { data: 'starting_km', name: 'starting_km', className: 'text-center' },
                        { data: 'starting_electric_charge', name: 'starting_electric_charge', className: 'text-center' },
                        { data: 'is_vehicle_verified', name: 'is_vehicle_verified', className: 'text-center' },
                        { data: 'is_driver_verified', name: 'is_driver_verified', className: 'text-center' },
                        { data: 'is_verified_by_supervisor', name: 'is_verified_by_supervisor', className: 'text-center' },
                        { data: 'is_verified_by_driver', name: 'is_verified_by_driver', className: 'text-center' },
                        { data: 'notes', name: 'notes', orderable: false, className: 'text-center' }
                    ],
                    language: {
                        emptyTable: 'No trip sheet entries found.'
                    },
                    columnDefs: [
                        { orderable: false, targets: [15] }
                    ],
                    order: [[1, 'asc']]
                });

                $('#dateFrom, #dateTo').on('change', function () {
                    updateExportUrl();
                    table.ajax.reload();
                });

                $('#resetSheetFilters').on('click', function () {
                    $('#dateFrom, #dateTo').val('');
                    updateExportUrl();
                    table.ajax.reload();
                });

                function updateExportUrl() {
                    var params = new URLSearchParams({ export: 'csv' });

                    if ($('#dateFrom').val()) {
                        params.set('date_from', $('#dateFrom').val());
                    }

                    if ($('#dateTo').val()) {
                        params.set('date_to', $('#dateTo').val());
                    }

                    $('#exportSheetCsv').attr('href', sheetViewUrl + '?' + params.toString());
                }

                updateExportUrl();
            });
        </script>
    @endsection
</x-app-layout>
