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
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                {{-- <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip Side</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}" disabled>
                </div> --}}
                @if($record->trip_side === 'both')
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->depot?->name ?? '-' }}" disabled>
                    </div>
                @else
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>From Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->fromDepot?->name ?? '-' }}" disabled>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>To Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->toDepot?->name ?? '-' }}" disabled>
                    </div>
                @endif
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Date Range</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d M Y') }} - {{ $record->to_date?->format('d M Y') }}"
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
                    <a href="{{ route('trips.sheet.entries.create', $record->id) }}" class="btn btn-primary"
                        title="Add Entry">
                        <i class="fa-solid fa-plus me-1"></i> Add Entry
                    </a>
                    <a href="{{ route('trips.sheet.view', ['trip' => $record->id, 'export' => 'csv']) }}"
                        class="add-btn">Export</a>
                    <a href="{{ route('trips.index') }}" class="btn btn-secondary" title="Back">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="table-over">
                <table class="align-middle mb-0 table table-striped tble-cstm bg-transparent" id="sheetEntryTable">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Code</th>
                            <th class="text-center nowrap">Status</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Vehicle</th>
                            {{-- <th class="text-center nowrap">Trip Order Sequence No</th> --}}
                            <th class="text-center nowrap" style="min-width: 130px;">Date</th>
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
    </section>

    <style>
        .route-stops-horizontal { display: flex; align-items: center; gap: 10px; overflow-x: auto; padding: 10px 4px; }
        .route-stop-item { min-width: max-content; padding: 8px 12px; border: 1px solid #d9dee8; border-radius: 8px; background: #fff; text-align: center; }
        .route-stop-arrow { color: #6c757d; flex: 0 0 auto; }
    </style>

    @section('scripts')
        <script>
            $(function () {
                $('#sheetEntryTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    pageLength: 10,
                    ordering: true,
                    responsive: true,
                    ajax: "{{ route('trips.sheet', $record->id) }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'code', name: 'trip_sheets.code', className: 'text-center' },
                        { data: 'status', name: 'trip_sheets.status', className: 'text-center' },
                        { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                        //{ data: 'trip_order_sequence_no', name: 'trip_order_sequence_no', className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center nowrap', width: '130px' },
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
                    language: {
                        emptyTable: 'No trip sheet entries found.'
                    },
                    order: []
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
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endsection
</x-app-layout>
