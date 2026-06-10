@section('title')
    OEM Trip Sheets
@endsection
<x-app-layout>
    <div class="page-title">
        <h3>OEM Trip Sheets</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('oems.index') }}">OEM</a></li>
                <li class="breadcrumb-item active">Trip Sheets</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="main-table-container">
            <div class="row align-items-end">
                <div class="col-lg-12 mb-3">
                    <div class="btn-flex justify-content-between">
                        <h5 class="title-w-sec mb-0">{{ $record->oem_name ?: 'OEM' }} - Trip Sheets</h5>
                        <a href="{{ route('oems.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="tripSheetDateFrom">Date From</label>
                    <input type="date" id="tripSheetDateFrom" class="form-control shadow-none">
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="tripSheetDateTo">Date To</label>
                    <input type="date" id="tripSheetDateTo" class="form-control shadow-none">
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label for="tripSheetVehicle">Vehicle</label>
                    <select id="tripSheetVehicle" class="form-select shadow-none">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 mb-3">
                    <div class="btn-flex justify-content-start">
                        <button type="button" id="filterTripSheets" class="add-btn border-0">Filter</button>
                        <button type="button" id="resetTripSheets" class="reset-btn border-0">Reset</button>
                        <a href="{{ route('oems.trip-sheets.export', $record->id) }}" id="exportTripSheets" class="add-btn">Export</a>
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table id="oemTripSheetsTable" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Date</th>
                            <th class="text-center nowrap">Trip Code</th>
                            <th class="text-center">Starting From</th>
                            <th class="text-center">Destination Point</th>
                            <th class="text-center">Start Time</th>
                            <th class="text-center">Actual Start Time</th>
                            <th class="text-center">Reach Time</th>
                            <th class="text-center">Actual Reach Time</th>
                            <th class="text-center">Shift</th>
                            <th class="text-center">Driver</th>
                            <th class="text-center">Vehicle</th>
                            <th class="text-center">Delay</th>
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
                var tripSheetTable = $('#oemTripSheetsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('oems.trip-sheets', $record->id) }}",
                        data: function (data) {
                            data.date_from = $('#tripSheetDateFrom').val();
                            data.date_to = $('#tripSheetDateTo').val();
                            data.vehicle_id = $('#tripSheetVehicle').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center' },
                        { data: 'trip_code', name: 'trip_sheets.code', className: 'text-center nowrap' },
                        { data: 'starting_from', name: 'starting_from', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'destination_point', name: 'destination_point', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'departure_time', name: 'departure_time', className: 'text-center' },
                        { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        { data: 'arrival_time', name: 'arrival_time', className: 'text-center' },
                        { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        { data: 'shift', name: 'side', className: 'text-center' },
                        { data: 'driver', name: 'driver', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'vehicle', name: 'vehicle', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'delay', name: 'delay', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: []
                });

                function tripSheetFilters() {
                    return {
                        date_from: $('#tripSheetDateFrom').val(),
                        date_to: $('#tripSheetDateTo').val(),
                        vehicle_id: $('#tripSheetVehicle').val()
                    };
                }

                function refreshTripSheetExportUrl() {
                    var url = new URL("{{ route('oems.trip-sheets.export', $record->id) }}", window.location.origin);
                    var filters = tripSheetFilters();

                    Object.keys(filters).forEach(function (key) {
                        if (filters[key]) {
                            url.searchParams.set(key, filters[key]);
                        }
                    });

                    $('#exportTripSheets').attr('href', url.toString());
                }

                $('#filterTripSheets').on('click', function () {
                    refreshTripSheetExportUrl();
                    tripSheetTable.ajax.reload();
                });

                $('#resetTripSheets').on('click', function () {
                    $('#tripSheetDateFrom, #tripSheetDateTo').val('');
                    $('#tripSheetVehicle').val('');
                    refreshTripSheetExportUrl();
                    tripSheetTable.ajax.reload();
                });

                $('#tripSheetDateFrom, #tripSheetDateTo, #tripSheetVehicle').on('change', refreshTripSheetExportUrl);
                refreshTripSheetExportUrl();
            });
        </script>
    @endsection
</x-app-layout>
