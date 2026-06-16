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
                            <div class="col-lg-12 mb-3">
                                <div class="filter-btns-top justify-content-start">
                                    <button type="button" id="resetFilters" class="reset-btn border-0">Reset</button>
                                    <button type="button" id="exportDorReport" class="exp-btn">Export</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-over mt-3">
                        <table id="dorReportTable" class="align-middle mb-0 table tble-cstm bg-transparent">
                            <thead>
                                <tr class="payroll-table">
                                    <th class="text-center nowrap">SL No</th>
                                    <th class="text-center nowrap">Trip Sheet Code</th>
                                    <th class="text-center nowrap">Date</th>
                                    <th class="text-center nowrap">Side</th>
                                    <th class="text-center nowrap">Depot Name</th>
                                    <th class="text-center nowrap">Vehicle No</th>
                                    <th class="text-center nowrap">Driver</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var form = $('#dorReportFilterForm');
                var filters = $('#date_from, #date_to, #depot_id, #trip_id, #vehicle_id, #driver_profile_id');

                $('.select2-filter').select2({
                    width: '100%',
                    placeholder: '---Select---',
                    allowClear: true
                });

                var table = $('#dorReportTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('reports.dor.index') }}",
                        data: function (data) {
                            Object.assign(data, currentFilters());
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_sheet_code', name: 'trip_sheets.code', className: 'text-center nowrap' },
                        { data: 'sheet_date', name: 'trip_sheets.date', className: 'text-center nowrap' },
                        { data: 'side', name: 'trip_sheet_entries.side', className: 'text-center' },
                        { data: 'depot_name', name: 'trip_sheet_entry_dors.depot_name', className: 'text-center' },
                        { data: 'vehicle_no', name: 'trip_sheet_entry_dors.bus_no', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver', name: 'trip_sheet_entry_dors.driver_badge_no', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[2, 'desc']]
                });

                filters.on('change', function () {
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function () {
                    filters.val('');
                    $('.select2-filter').trigger('change.select2');
                    table.ajax.reload();
                });

                $('#exportDorReport').on('click', function () {
                    var button = $(this);
                    var originalText = button.text();

                    button.prop('disabled', true).text('Exporting...');

                    $.ajax({
                        url: "{{ route('reports.dor.export') }}",
                        type: 'GET',
                        data: currentFilters(),
                        xhrFields: { responseType: 'blob' },
                        success: function (data, status, xhr) {
                            var fileName = 'dor-report.xlsx';
                            var disposition = xhr.getResponseHeader('Content-Disposition') || '';
                            var match = disposition.match(/filename="?([^"]+)"?/);

                            if (match && match[1]) {
                                fileName = match[1];
                            }

                            var blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                            var url = window.URL.createObjectURL(blob);
                            var link = document.createElement('a');
                            link.href = url;
                            link.download = fileName;
                            document.body.appendChild(link);
                            link.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(link);
                        },
                        error: function () {
                            if (typeof showToast === 'function') {
                                showToast('error', 'Export failed.');
                            }
                        },
                        complete: function () {
                            button.prop('disabled', false).text(originalText);
                        }
                    });
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    table.ajax.reload();
                });

                function currentFilters() {
                    return {
                        date_from: $('#date_from').val(),
                        date_to: $('#date_to').val(),
                        depot_id: $('#depot_id').val(),
                        trip_id: $('#trip_id').val(),
                        vehicle_id: $('#vehicle_id').val(),
                        driver_profile_id: $('#driver_profile_id').val()
                    };
                }
            });
        </script>
    @endsection
</x-app-layout>
