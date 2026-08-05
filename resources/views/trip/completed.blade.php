@section('title')
    Completed Trip Sheet
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Completed Trip Sheet</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Completed Trip Sheet</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="collapse" id="filterCollapse">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateFromFilter">From Date</label>
                                    <input type="date" id="dateFromFilter" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateToFilter">To Date</label>
                                    <input type="date" id="dateToFilter" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="tripFilter">Filter by Trip</label>
                                    <select id="tripFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($trips as $trip)
                                            <option value="{{ $trip->id }}">{{ $trip->code }} - {{ $trip->trip_title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="searchFilter">SER Code</label>
                                    <input type="text" id="searchFilter" class="form-control shadow-none"
                                        placeholder="Search SER code">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="depotFilter">Filter by Depot</label>
                                    <select id="depotFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($depots as $depot)
                                            <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="vehicleFilter">Filter by Vehicle No</label>
                                    <select id="vehicleFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="controllerFilter">Filter by Controller</label>
                                    <select id="controllerFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($controllers as $controller)
                                            @if($controller->user?->name)
                                                <option value="{{ $controller->user->name }}">{{ $controller->user->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="supervisorFilter">Filter by Supervisor</label>
                                    <select id="supervisorFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($supervisors as $supervisor)
                                            @if($supervisor->user?->name)
                                                <option value="{{ $supervisor->user->name }}">{{ $supervisor->user->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="driverFilter">Filter by Driver</label>
                                    <select id="driverFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($drivers as $driver)
                                            @if($driver->user?->name)
                                                <option value="{{ $driver->id }}">{{ $driver->user->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="filter-btns-top">
                                    <a href="#!" class="reset-btn" id="resetFilters">Reset</a>
                                    <button type="button" class="search-btn" id="searchFilters">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-12 ms-auto">
                            <div class="btn-flex">
                                <a class="add-btn bg-filter" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                                    aria-expanded="false" aria-controls="filterCollapse">Filters</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <div class="table-search justify-content-end">
                                            <a href="{{ route('trips.completed.export') }}" class="btn btn-primary ms-1" id="exportCompletedTrips">Export Data</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-over">
                                    <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th class="text-center nowrap">SL No</th>
                                                <th class="text-center nowrap">Code</th>
                                                <th class="text-center nowrap">SER</th>
                                                <th class="text-center nowrap">Round</th>
                                                <th class="text-center nowrap">NAT</th>
                                                <th class="text-center nowrap">KMS</th>
                                                <th class="text-center nowrap">Departure</th>
                                                <th class="text-center nowrap">Arrival</th>
                                                <th class="text-center nowrap">Driver</th>
                                                <th class="text-center nowrap">Vehicle</th>
                                                <th class="text-center nowrap">Date</th>
                                                <th class="text-center nowrap">Actual Start</th>
                                                <th class="text-center nowrap">Actual Reach</th>
                                                <th class="text-center nowrap">Starting Km</th>
                                                <th class="text-center nowrap">Ending Km</th>
                                                <th class="text-center nowrap">Starting Charge</th>
                                                <th class="text-center nowrap">Ending Charge</th>
                                                <th class="text-center nowrap">Vehicle Verified</th>
                                                <th class="text-center nowrap">Driver Verified</th>
                                                <th class="text-center nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('.select2-filter').select2({
                    placeholder: '---Select---',
                    allowClear: true,
                    width: '100%'
                });

                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('completed.trips.index') }}",
                        data: filters
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'code', name: 'trip_sheet_entries.code', className: 'text-center' },
                        { data: 'service_code', name: 'service_code', className: 'text-center' },
                        { data: 'round_no', name: 'round_no', className: 'text-center' },
                        { data: 'trip_nature', name: 'trip_nature', className: 'text-center' },
                        { data: 'schedule_km', name: 'schedule_km', className: 'text-center' },
                        { data: 'departure_time', name: 'departure_time', className: 'text-center' },
                        { data: 'arrival_time', name: 'arrival_time', className: 'text-center' },
                        { data: 'driver_name', name: 'driver_name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'vehicle_no', name: 'vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center nowrap' },
                        { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        { data: 'starting_km', name: 'starting_km', className: 'text-center' },
                        { data: 'ending_km', name: 'ending_km', className: 'text-center' },
                        { data: 'starting_electric_charge', name: 'starting_electric_charge', className: 'text-center' },
                        { data: 'ending_electric_charge', name: 'ending_electric_charge', className: 'text-center' },
                        { data: 'is_vehicle_verified', name: 'is_vehicle_verified', className: 'text-center' },
                        { data: 'is_driver_verified', name: 'is_driver_verified', className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[9, 'desc']]
                });

                let searchTimer;
                $('#searchFilter').on('keyup', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(reloadTable, 300);
                });

                $('#searchFilters').on('click', reloadTable);
                $('#dateFromFilter, #dateToFilter, #depotFilter, #vehicleFilter, #controllerFilter, #supervisorFilter, #driverFilter, #tripFilter').on('change', reloadTable);

                $('#resetFilters').on('click', function () {
                    $('#dateFromFilter, #dateToFilter, #depotFilter, #vehicleFilter, #controllerFilter, #supervisorFilter, #driverFilter, #tripFilter, #searchFilter').val('');
                    $('.select2-filter').trigger('change.select2');
                    reloadTable();
                });

                $('#exportCompletedTrips').on('click', function (event) {
                    event.preventDefault();
                    window.location.href = this.href + '?' + $.param(currentFilters());
                });

                function filters(data) {
                    Object.assign(data, currentFilters());
                }

                function currentFilters() {
                    return {
                        ser_search: $('#searchFilter').val(),
                        trip_id: $('#tripFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val(),
                        depot_id: $('#depotFilter').val(),
                        vehicle_id: $('#vehicleFilter').val(),
                        controller_name: $('#controllerFilter').val(),
                        supervisor_name: $('#supervisorFilter').val(),
                        driver_profile_id: $('#driverFilter').val()
                    };
                }

                function reloadTable() {
                    table.ajax.reload();
                }
            });
        </script>
    @endsection
</x-app-layout>
