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
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip Side</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Date Range</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d M Y') }} - {{ $record->to_date?->format('d M Y') }}"
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
                            <th class="text-center nowrap">Side</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Vehicle</th>
                            <th class="text-center nowrap" style="min-width: 130px;">Date</th>
                            <th class="text-center nowrap">Actual Start</th>
                            <th class="text-center nowrap">Actual Reach</th>
                            <th class="text-center nowrap">Starting Km</th>
                            <th class="text-center nowrap">Charge</th>
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
                        { data: 'side', name: 'side', className: 'text-center' },
                        { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_date', name: 'trip_sheets.date', className: 'text-center nowrap', width: '130px' },
                        { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        { data: 'starting_km', name: 'starting_km', className: 'text-center' },
                        { data: 'starting_electric_charge', name: 'starting_electric_charge', className: 'text-center' },
                        { data: 'is_vehicle_verified', name: 'is_vehicle_verified', className: 'text-center' },
                        { data: 'is_driver_verified', name: 'is_driver_verified', className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    language: {
                        emptyTable: 'No trip sheet entries found.'
                    },
                    order: [[1, 'asc']]
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