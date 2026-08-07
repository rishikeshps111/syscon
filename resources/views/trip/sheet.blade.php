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

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                {{-- <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip Side</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}" disabled>
                </div> --}}
                @if($record->trip_side === 'both')
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->depot?->name ?? '-' }}"
                            disabled>
                    </div>
                @else
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>From Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->fromDepot?->name ?? '-' }}"
                            disabled>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>To Depot</label>
                        <input type="text" class="form-control shadow-none" value="{{ $record->toDepot?->name ?? '-' }}"
                            disabled>
                    </div>
                @endif
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>Date Range</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d M Y') }} - {{ $record->to_date?->format('d M Y') }}"
                        disabled>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>NAT</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->tripNature->title ?? '-' }}"
                        disabled>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label>KMS</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->schedule_km ?? '-' }}"
                        disabled>
                </div>
                <div class="col-lg-12 o-f-inp">
                    <label>Route Stops</label>
                    <div class="route-stops-horizontal mt-2">
                        @php
                            $routePoints = collect();
                            if ($record->route?->startPoint) {
                                $routePoints->push([
                                    'name' => $record->route->startPoint->name,
                                    'short_name' => $record->route->startPoint->short_name,
                                    'label' => 'Starting Depot',
                                ]);
                            }
                            foreach ($record->route?->stops ?? [] as $stop) {
                                $routePoints->push([
                                    'name' => $stop->location?->name ?? $stop->name,
                                    'short_name' => $stop->location?->short_name,
                                    'label' => 'Stop',
                                ]);
                            }
                            if ($record->route?->endPoint) {
                                $routePoints->push([
                                    'name' => $record->route->endPoint->name,
                                    'short_name' => $record->route->endPoint->short_name,
                                    'label' => 'Ending Depot',
                                ]);
                            }
                        @endphp

                        @forelse($routePoints as $point)
                            @if(!$loop->first)
                                <span class="route-stop-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                            @endif
                            <div class="route-stop-item">
                                <div class="fw-semibold">
                                    {{ $point['name'] }}{{ $point['short_name'] ? ' (' . $point['short_name'] . ')' : '' }}
                                </div>
                                <small class="text-muted">{{ $point['label'] }}</small>
                            </div>
                        @empty
                            <span class="text-muted">No route stops available.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="main-table-container">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="mb-0">Trip Sheet Entries</h5>
                <div class="btn-flex">
                    <a href="{{ route('trips.sheet.import.form', $record->id) }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-file-import me-1"></i> Import Trip Sheet
                    </a>
                    {{-- <a href="{{ route('trips.sheet.entries.create', $record->id) }}" class="btn btn-primary"
                        title="Add Entry">
                        <i class="fa-solid fa-plus me-1"></i> Add Entry
                    </a> --}}
                    <a href="{{ route('trips.sheet.view', ['trip' => $record->id, 'export' => 'csv']) }}"
                        class="add-btn">Export</a>
                    <a href="{{ route('trips.index') }}" class="btn btn-secondary" title="Back">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="row align-items-end mb-3 g-2" id="sheetEntryFilters">
                <div class="col-md-4 col-lg-2 o-f-inp">
                    <label for="entryDateFilter" class="form-label m-0">Date</label>
                    <input type="date" id="entryDateFilter" class="form-control shadow-none"
                        min="{{ $record->from_date?->format('Y-m-d') }}" max="{{ $record->to_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 col-lg-2 o-f-inp">
                    <label for="serSearchFilter" class="form-label m-0">SER Code</label>
                    <input type="text" id="serSearchFilter" class="form-control shadow-none"
                        placeholder="Search SER code">
                </div>
                <div class="col-md-4 col-lg-2 o-f-inp">
                    <label for="entryStatusFilter" class="form-label m-0">Status</label>
                    <select id="entryStatusFilter" class="form-select shadow-none sheet-entry-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 o-f-inp">
                    <label for="driverFilter" class="form-label m-0">Driver</label>
                    <select id="driverFilter" class="form-select shadow-none sheet-entry-select">
                        <option value="">All Drivers</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->user?->name ?: '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 o-f-inp">
                    <label for="vehicleFilter" class="form-label m-0">Vehicle</label>
                    <select id="vehicleFilter" class="form-select shadow-none sheet-entry-select">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no ?: $vehicle->vehicle_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2 d-flex gap-2">
                    <button type="button" id="resetSheetEntryFilters" class="btn btn-outline-secondary mb-1">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset
                    </button>
                </div>
                <div class="col-12 pt-2">
                    <div class="assignment-filter-group">
                        <span class="assignment-filter-title">Assignment</span>
                        <label class="assignment-filter-option">
                            <input class="form-check-input assignment-filter" type="checkbox" value="all" checked>
                            <span>All</span>
                        </label>
                        <label class="assignment-filter-option">
                            <input class="form-check-input assignment-filter" type="checkbox" value="unassigned">
                            <span>UnAssigned</span>
                        </label>
                        <label class="assignment-filter-option">
                            <input class="form-check-input assignment-filter" type="checkbox" value="assigned">
                            <span>Assigned</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="table-over">
                <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent" id="sheetEntryTable">
                    <thead>
                        <tr>
                            <th class="text-center">SL No</th>
                            <th class="text-center nowrap">Code</th>
                            <th class="text-center nowrap">Status</th>
                            <th class="text-center nowrap" style="min-width: 130px;">Date</th>
                            <th class="text-center nowrap">SER</th>
                            {{-- <th class="text-center nowrap">Round</th> --}}
                            {{-- <th class="text-center nowrap">NAT</th>
                            <th class="text-center nowrap">KMS</th> --}}
                            <th class="text-center nowrap">Departure</th>
                            <th class="text-center nowrap">Arrival</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Vehicle</th>
                            {{-- <th class="text-center nowrap">Trip Order Sequence No</th> --}}
                            {{-- <th class="text-center nowrap">Actual Start</th>
                            <th class="text-center nowrap">Actual Reach</th>
                            <th class="text-center nowrap">Starting Km</th>
                            <th class="text-center nowrap">Ending Km</th>
                            <th class="text-center nowrap">Starting Charge</th>
                            <th class="text-center nowrap">Ending Charge</th> --}}
                            <th class="text-center">Vehicle Verified</th>
                            <th class="text-center">Driver Verified</th>
                            <th class="text-center nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    <style>
        .route-stops-horizontal {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 4px;
        }

        .route-stop-item {
            min-width: max-content;
            padding: 8px 12px;
            border: 1px solid #d9dee8;
            border-radius: 8px;
            background: #fff;
            text-align: center;
        }

        .route-stop-arrow {
            color: #6c757d;
            flex: 0 0 auto;
        }

        .assignment-filter-group {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #d8e0eb;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 14px;
        }

        .assignment-filter-title {
            color: #344054;
            font-size: 14px;
            font-weight: 700;
            margin-right: 4px;
        }

        .assignment-filter-option {
            align-items: center;
            background: #fff;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            color: #344054;
            cursor: pointer;
            display: inline-flex;
            font-weight: 600;
            gap: 8px;
            margin: 0;
            min-width: 125px;
            padding: 9px 14px;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }

        .assignment-filter-option:hover {
            border-color: #86a8e7;
        }

        .assignment-filter-option:has(.assignment-filter:checked) {
            background: #eaf2ff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
            color: #0b5ed7;
        }

        .assignment-filter-option .form-check-input {
            flex: 0 0 auto;
            height: 18px;
            margin: 0;
            width: 18px;
        }
    </style>

    @section('scripts')
        <script>
            $(function () {
                var sheetEntryTable = $('#sheetEntryTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    pageLength: 10,
                    ordering: true,
                    responsive: true,
                    ajax: {
                        url: "{{ route('trips.sheet', $record->id) }}",
                        data: function (data) {
                            data.entry_date = $('#entryDateFilter').val();
                            data.ser_search = $('#serSearchFilter').val();
                            data.entry_status = $('#entryStatusFilter').val();
                            data.driver_profile_id = $('#driverFilter').val();
                            data.vehicle_id = $('#vehicleFilter').val();
                            data.assignment_status = $('.assignment-filter:checked').val() || 'all';
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'code', name: 'trip_sheet_entries.code', className: 'text-center' },
                        { data: 'status', name: 'trip_sheet_entries.status', className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center nowrap', width: '130px' },
                        { data: 'service_code', name: 'service_code', className: 'text-center' },
                        // { data: 'round_no', name: 'round_no', className: 'text-center' },
                        //{ data: 'trip_nature', name: 'trip_nature', className: 'text-center' },
                        //{ data: 'schedule_km', name: 'schedule_km', className: 'text-center' },
                        { data: 'departure_time', name: 'departure_time', className: 'text-center' },
                        { data: 'arrival_time', name: 'arrival_time', className: 'text-center' },
                        { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                        //{ data: 'trip_order_sequence_no', name: 'trip_order_sequence_no', className: 'text-center' },
                        // { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        // { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        // { data: 'starting_km', name: 'starting_km', className: 'text-center' },
                        // { data: 'ending_km', name: 'ending_km', className: 'text-center' },
                        // { data: 'starting_electric_charge', name: 'starting_electric_charge', className: 'text-center' },
                        // { data: 'ending_electric_charge', name: 'ending_electric_charge', className: 'text-center' },
                        { data: 'is_vehicle_verified', name: 'is_vehicle_verified', className: 'text-center' },
                        { data: 'is_driver_verified', name: 'is_driver_verified', className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    language: {
                        emptyTable: 'No trip sheet entries found.'
                    },
                    order: []
                });

                var serSearchTimer;
                $('#serSearchFilter').on('input', function () {
                    clearTimeout(serSearchTimer);
                    serSearchTimer = setTimeout(function () {
                        sheetEntryTable.ajax.reload();
                    }, 300);
                });

                $('#entryDateFilter').on('change', function () {
                    sheetEntryTable.ajax.reload();
                });

                $('.sheet-entry-select').select2({ width: '100%' });
                $('.sheet-entry-select').on('change', function () {
                    sheetEntryTable.ajax.reload();
                });

                $('.assignment-filter').on('change', function () {
                    $('.assignment-filter').not(this).prop('checked', false);
                    if (!this.checked) {
                        $('.assignment-filter[value="all"]').prop('checked', true);
                    }
                    sheetEntryTable.ajax.reload();
                });

                $('#resetSheetEntryFilters').on('click', function () {
                    $('#entryDateFilter, #serSearchFilter, #entryStatusFilter, #driverFilter, #vehicleFilter').val('');
                    $('.sheet-entry-select').trigger('change.select2');
                    $('.assignment-filter').prop('checked', false);
                    $('.assignment-filter[value="all"]').prop('checked', true);
                    sheetEntryTable.ajax.reload();
                });

                $(document).on('submit', '.delete-sheet-entry', function (event) {
                    event.preventDefault();

                    var form = this;

                    Swal.fire({
                        title: 'Delete entry?',
                        text: 'This trip sheet entry will be permanently deleted.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            var deleteForm = $(form);
                            var deleteButton = deleteForm.find('button[type="submit"]');
                            deleteButton.prop('disabled', true);

                            $.ajax({
                                url: deleteForm.attr('action'),
                                type: 'POST',
                                data: deleteForm.serialize(),
                                headers: { 'Accept': 'application/json' },
                                success: function (response) {
                                    sheetEntryTable.ajax.reload(null, false);
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: response.message,
                                        timer: 1800,
                                        showConfirmButton: false
                                    });
                                },
                                error: function (xhr) {
                                    deleteButton.prop('disabled', false);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Delete failed',
                                        text: xhr.responseJSON?.message || 'Unable to delete the trip sheet entry.'
                                    });
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endsection
</x-app-layout>