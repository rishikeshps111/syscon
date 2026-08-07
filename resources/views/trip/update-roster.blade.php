@section('title', 'Update Roaster')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Update Roaster</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item active">Update Roaster</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-3 o-f-inp mb-3"><label>Trip Code</label><input class="form-control shadow-none"
                        value="{{ $record->code ?: '-' }}" disabled></div>
                <div class="col-lg-3 o-f-inp mb-3"><label>From</label><input class="form-control shadow-none"
                        value="{{ $record->route?->startPoint?->name ?: '-' }}" disabled></div>
                <div class="col-lg-3 o-f-inp mb-3"><label>To</label><input class="form-control shadow-none"
                        value="{{ $record->route?->endPoint?->name ?: '-' }}" disabled></div>
                <div class="col-lg-3 o-f-inp mb-3"><label>Depot</label><input class="form-control shadow-none"
                        value="{{ $record->depot?->name ?: '-' }}" disabled></div>
                <div class="col-lg-12 o-f-inp">
                    <label>Route Stops</label>
                    <div class="route-stops-horizontal mt-2">
                        @php
                            $routePoints = collect();
                            if ($record->route?->startPoint) {
                                $routePoints->push(['name' => $record->route->startPoint->name, 'short_name' => $record->route->startPoint->short_name, 'label' => 'Starting Depot']);
                            }
                            foreach ($record->route?->stops ?? [] as $stop) {
                                $routePoints->push(['name' => $stop->location?->name ?? $stop->name, 'short_name' => $stop->location?->short_name, 'label' => 'Stop']);
                            }
                            if ($record->route?->endPoint) {
                                $routePoints->push(['name' => $record->route->endPoint->name, 'short_name' => $record->route->endPoint->short_name, 'label' => 'Ending Depot']);
                            }
                        @endphp
                        @forelse ($routePoints as $point)
                            @if (!$loop->first)<span class="route-stop-arrow"><i
                            class="fa-solid fa-arrow-right"></i></span>@endif
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

        <form method="POST" action="{{ route('trips.roster.store', $record) }}" class="js-loading-form">
            @csrf
            <div class="main-table-container">
                @if (session('roster_update_summary'))
                    @php($summary = session('roster_update_summary'))
                    <div class="alert alert-info">
                        <strong>{{ $summary['created'] }} created, {{ $summary['updated'] }} updated, {{ $summary['skipped'] }} skipped.</strong>
                        @if (! empty($summary['reasons']))
                            <ul class="mb-0 mt-2">@foreach ($summary['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <div class="row align-items-end mb-3">
                    <div class="col-lg-3 o-f-inp mb-3 mb-lg-0">
                        <label for="dateFromFilter">From Date</label>
                        <input type="date" id="dateFromFilter" class="form-control shadow-none" min="{{ $record->from_date?->format('Y-m-d') }}" max="{{ $record->to_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3 mb-lg-0">
                        <label for="dateToFilter">To Date</label>
                        <input type="date" id="dateToFilter" class="form-control shadow-none" min="{{ $record->from_date?->format('Y-m-d') }}" max="{{ $record->to_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3 mb-lg-0">
                        <label for="serFilter">Search by SER</label>
                        <input type="text" id="serFilter" class="form-control shadow-none" placeholder="Enter SER">
                    </div>
                    <div class="col-lg-3">
                        <div class="filter-btns-top justify-content-start">
                            <button type="button" class="reset-btn border-0" id="resetRosterFilters">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <a href="{{ route('trips.index') }}" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary js-loading-submit">Update Roasters</button>
                </div>
                <div class="table-over">
                    <table id="rosterTable" class="align-middle mb-0 table tble-cstm" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th class="text-center">SL No</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Trip Code</th>
                                <th class="text-center">SER</th>
                                <th>Driver</th>
                                <th>Vehicle</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </form>
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
    </style>

    @section('scripts')
        <script>
            $(function () {
                $('#rosterTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('trips.roster.update', $record) }}",
                        data: function (data) {
                            data.date_from = $('#dateFromFilter').val();
                            data.date_to = $('#dateToFilter').val();
                            data.ser = $('#serFilter').val();
                        }
                    },
                    pageLength: 1000,
                    lengthMenu: [[10, 25, 50, 100, 1000], [10, 25, 50, 100, 1000]],
                    order: [],
                    columns: [
                        { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'date_text', name: 'trip_sheets.date', className: 'text-center nowrap' },
                        { data: 'trip_code', name: 'trip_sheet_entries.code', className: 'text-center nowrap' },
                        { data: 'ser', name: 'trip_sheet_entries.service_code', className: 'text-center nowrap' },
                        { data: 'driver_select', name: 'driver_select', orderable: false, searchable: false },
                        { data: 'vehicle_select', name: 'vehicle_select', orderable: false, searchable: false }
                    ],
                    drawCallback: function () {
                        $('.roster-select').select2({ allowClear: true, width: '100%', placeholder: function () { return $(this).data('placeholder'); } });
                        $('#checkAll').prop('checked', false);
                    }
                });
                $('#checkAll').on('change', function () { $('.row-check').prop('checked', this.checked); });
                $(document).on('change', '.row-check', function () { $('#checkAll').prop('checked', $('.row-check').length === $('.row-check:checked').length); });
                $(document).on('change', '.roster-select', function () { $(this).closest('tr').find('.row-check').prop('checked', true).trigger('change'); });
                $('#dateFromFilter, #dateToFilter, #serFilter').on('change', function () { $('#rosterTable').DataTable().ajax.reload(); });
                $('#resetRosterFilters').on('click', function () {
                    $('#dateFromFilter, #dateToFilter, #serFilter').val('');
                    $('#rosterTable').DataTable().ajax.reload();
                });
                $('.js-loading-form').on('submit', function () {
                    var payload = {};
                    $('.row-check:checked').each(function () {
                        var row = $(this).closest('tr');
                        payload[this.value] = {
                            driver_profile_id: row.find('.roster-driver-select').val() || null,
                            vehicle_id: row.find('.roster-vehicle-select').val() || null
                        };
                    });
                    $('<input>', {type: 'hidden', name: 'roster_payload', value: JSON.stringify(payload)}).appendTo(this);
                    $('.row-check, .roster-select').prop('disabled', true);
                });
            });
        </script>
    @endsection
</x-app-layout>
