@section('title')
    Completed Trips
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Completed Trips</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Completed Trips</li>
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
                            <div class="col-lg-4 mb-3">
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
                            <div class="col-lg-4 mb-3">
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
                            <div class="col-lg-4 mb-3">
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
                                    <div class="col-lg-7">
                                        <div class="table-search">
                                            <label for="searchFilter" class="nowrap">Search</label>
                                            <input type="text" id="searchFilter" class="form-control shadow-none" placeholder="Search by Trip No">
                                            <a href="{{ route('trips.completed.export') }}" class="btn btn-primary ms-1" id="exportCompletedTrips">Export Data</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-over">
                                    <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                        <thead>
                                            <tr>
                                                <th class="text-center nowrap">SL No</th>
                                                <th class="text-center nowrap">Trip Code</th>
                                                <th class="text-center nowrap">Title</th>
                                                <th class="text-center nowrap">Date</th>
                                                <th class="text-center nowrap">From</th>
                                                <th class="text-center nowrap">To</th>
                                                <th class="text-center nowrap">Driver Name</th>
                                                <th class="text-center nowrap">Status</th>
                                                <th class="text-center nowrap">View</th>
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
                        url: "{{ route('trips.completed.index') }}",
                        data: filters
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_code', name: 'trip_sheets.code', className: 'text-center' },
                        { data: 'title', name: 'trips.title', className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center nowrap' },
                        { data: 'from_location', name: 'from_location', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'to_location', name: 'to_location', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver_name', name: 'driver_name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'status', name: 'trip_sheets.status', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[3, 'desc']]
                });

                let searchTimer;
                $('#searchFilter').on('keyup', function () {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(reloadTable, 300);
                });

                $('#searchFilters').on('click', reloadTable);
                $('#dateFromFilter, #dateToFilter, #depotFilter, #vehicleFilter, #controllerFilter, #supervisorFilter, #driverFilter').on('change', reloadTable);

                $('#resetFilters').on('click', function () {
                    $('#dateFromFilter, #dateToFilter, #depotFilter, #vehicleFilter, #controllerFilter, #supervisorFilter, #driverFilter, #searchFilter').val('');
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
                        search_text: $('#searchFilter').val(),
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
