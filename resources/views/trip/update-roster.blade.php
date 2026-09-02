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
                    <div class="col-lg-4 o-f-inp mb-3 mb-lg-0">
                        <label for="dateFromFilter">From Date</label>
                        <input type="date" id="dateFromFilter" class="form-control shadow-none" min="{{ $record->from_date?->format('Y-m-d') }}" max="{{ $record->to_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3 mb-lg-0">
                        <label for="dateToFilter">To Date</label>
                        <input type="date" id="dateToFilter" class="form-control shadow-none" min="{{ $record->from_date?->format('Y-m-d') }}" max="{{ $record->to_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3 mb-lg-0">
                        <label for="serFilter">Search by SER</label>
                        <input type="text" id="serFilter" class="form-control shadow-none" placeholder="Enter SER">
                    </div>
                    <div class="col-lg-12 mt-2 d-flex align-items-center gap-2">
                        <div class="filter-btns-top justify-content-start">
                            <button type="button" class="fil-btn" id="resetRosterFilters">Reset</button>
                        </div>
                         <div class="btns-group-container   mb-3" style="margin-left:0 !important; margin-bottom:0px !important;">
                    <a href="{{ route('trips.index') }}" class="bk-btn">Back</a>
                    <button type="submit" class="add-btn m-0 js-loading-submit">Update Roasters</button>
                </div>
                    </div>
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
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;

    width: 100% !important;

    /*padding: 16px !important;*/

    /*background: #f8fafc !important;*/

    /*border: 1px solid #e2e8f0 !important;*/
    /*border-radius: 12px !important;*/

    overflow-x: auto !important;
    overflow-y: hidden !important;

    scrollbar-width: thin !important;
}



.route-stop-item {
    position: relative !important;

    flex: 0 0 auto !important;

    min-width: 170px !important;

    padding: 13px 16px !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;

    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.04) !important;

    transition: all 0.2s ease !important;
}


/* Hover */

.route-stop-item:hover {
    transform: translateY(-2px) !important;

    border-color: #cbd5e1 !important;

    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08) !important;
}

.route-stop-item .fw-semibold {
    display: block !important;

    margin-bottom: 4px !important;

    color: #1e293b !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    line-height: 1.4 !important;

    white-space: nowrap !important;
}


.route-stop-item small {
    display: block !important;

    color: #64748b !important;

    font-size: 10px !important;
    font-weight: 500 !important;

    line-height: 1.3 !important;
}



.route-stop-arrow {
    flex: 0 0 auto !important;

    width: 32px !important;
    height: 32px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    color: #64748b !important;

    background: #ffffff !important;

    border: 1px solid #e2e8f0 !important;

    border-radius: 50% !important;

    font-size: 12px !important;

    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04) !important;
}



/* First Stop */

.route-stops-horizontal .route-stop-item:first-child {
    background: #eff6ff !important;

    border-color: #eff6ff !important;
}

.route-stops-horizontal .route-stop-item:first-child .fw-semibold {
    color: #1d4ed8 !important;
}

.route-stops-horizontal .route-stop-item:first-child small {
    color: #2563eb !important;
}


/* Last Stop */

.route-stops-horizontal .route-stop-item:last-child {
   background: #fae9ea !important;
    border-color: #fae9ea !important;
}

.route-stops-horizontal .route-stop-item:last-child .fw-semibold {
    color: #dc3545  !important;
}

.route-stops-horizontal .route-stop-item:last-child small {
    color: #dc3545  !important;
}



.route-stop-item:not(:first-child):not(:last-child) {
    background: #f8fafc !important;

    border-color: #e2e8f0 !important;
}




.route-stops-horizontal + * {
    margin-top: 0 !important;
}



.route-stops-horizontal::-webkit-scrollbar {
    height: 5px !important;
}

.route-stops-horizontal::-webkit-scrollbar-track {
    background: #f1f5f9 !important;

    border-radius: 10px !important;
}

.route-stops-horizontal::-webkit-scrollbar-thumb {
    background: #cbd5e1 !important;

    border-radius: 10px !important;
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
