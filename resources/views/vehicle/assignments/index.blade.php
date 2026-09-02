@section('title')
    Assigned Trips
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Assigned Trips</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">Vehicle Management</a></li>
                    <li class="breadcrumb-item active">Assigned Trips</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec mb-3">Vehicle Details</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2"><span>Vehicle Code</span><strong>{{ $vehicle->vehicle_code ?: '-' }}</strong></div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2"><span>Vehicle No</span><strong>{{ $vehicle->vehicle_no ?: '-' }}</strong></div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2"><span>OEM</span><strong>{{ $vehicle->oem?->oem_name ?? '-' }}</strong></div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="vehicle-detail-card-2"><span>Depot</span><strong>{{ $vehicle->depot?->name ?? '-' }}</strong></div>
                </div>
            </div>
        </div>

        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 btns-group-container" style="margin-bottom:-20px;">
                    <a href="{{ route('vehicles.index') }}" class="bk-btn">Back</a>
                </div>
            </div>
            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL No</th>
                            <th class="text-center nowrap">Roster Code</th>
                            <th class="text-center nowrap">Trip Code</th>
                            <th class="text-center nowrap">Driver</th>
                            <th class="text-center nowrap">Date</th>
                            <th class="text-center nowrap">Actual Start</th>
                            <th class="text-center nowrap">Actual Reach</th>
                            <th class="text-center nowrap">Starting Km</th>
                            <th class="text-center nowrap">Ending Km</th>
                            <th class="text-center nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <style>
            .vehicle-detail-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; min-height: 78px; padding: 14px 16px; }
            .vehicle-detail-card span { color: #6b7280; display: block; font-size: 13px; margin-bottom: 8px; }
            .vehicle-detail-card strong { color: #111827; display: block; font-size: 15px; font-weight: 600; word-break: break-word; }
        </style>
        <script>
            $(function () {
                $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: "{{ route('vehicles.assignments.index', $vehicle->id) }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'roster_code', name: 'roster_code', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_code', name: 'trip_code', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'driver', name: 'driver', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'trip_date', name: 'trip_date', orderable: false, searchable: false, className: 'text-center nowrap' },
                        { data: 'actual_start_time', name: 'actual_start_time', className: 'text-center' },
                        { data: 'actual_reach_time', name: 'actual_reach_time', className: 'text-center' },
                        { data: 'starting_km', name: 'starting_km', className: 'text-center' },
                        { data: 'ending_km', name: 'ending_km', className: 'text-center' },
                        { data: 'trip_status', name: 'trip_status', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [],
                    language: { emptyTable: 'No trips are assigned to this vehicle through the roster.' }
                });
            });
        </script>
    @endsection
</x-app-layout>
