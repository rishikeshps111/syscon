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

        <form id="rosterUpdateForm" method="POST" action="{{ route('trips.roster.store', $record) }}" class="js-loading-form">
            @csrf
            <div class="main-table-container">
                @if (session('roster_update_summary'))
                    @php($summary = session('roster_update_summary'))
                    <div class="alert alert-info">
                        <strong>{{ $summary['created'] }} created, {{ $summary['updated'] }} updated, {{ $summary['unchanged'] ?? 0 }} unchanged, {{ $summary['skipped'] }} skipped.</strong>
                        @if (! empty($summary['reasons']))
                            <details class="mt-2"><summary>View {{ count($summary['reasons']) }} skipped row reason(s)</summary>
                                <ul class="mb-0 mt-2">@foreach ($summary['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                            </details>
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
                    <button type="button" class="add-btn m-0" data-bs-toggle="modal" data-bs-target="#rosterImportModal">Update Roaster Via Import</button>
                </div>
                    </div>
                </div>
               
                <p id="rosterSelectionCount" role="status" aria-live="polite">0 duties selected. Selections are kept when filters change.</p>
                <div id="rosterScroll" class="table-over" tabindex="0" aria-label="Roster duties. Scroll to load more."
                    data-rows-url="{{ route('trips.roster.rows', $record) }}"
                    data-drivers-url="{{ route('trips.roster.drivers', $record) }}"
                    data-vehicles-url="{{ route('trips.roster.vehicles', $record) }}">
                    <table id="rosterTable" class="align-middle mb-0 table tble-cstm" style="width:100%" aria-label="Roster duties">
                        <thead>
                            <tr>
                                <th class="text-center"><input type="checkbox" id="checkAll" aria-label="Select all loaded duties"></th>
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
                    <div id="rosterLoadSentinel" class="p-3 text-center">
                        <span id="rosterLoadingSpinner" class="spinner-border text-primary" aria-hidden="true" hidden></span>
                        <span id="rosterLoadStatus" role="status" aria-live="polite">Loading duties…</span>
                        <button type="button" id="rosterLoadMore" class="btn btn-outline-secondary btn-sm ms-2">Load more duties</button>
                    </div>
                </div>
            </div>
        </form>
        @include('trip.partials.roster-import-modal')
    </section>

    <style>
        #rosterScroll {
            max-height: 65vh;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #237ca3 transparent;
        }
        #rosterScroll::-webkit-scrollbar { width: 5px; }
        #rosterScroll::-webkit-scrollbar-track { background: transparent; }
        #rosterScroll::-webkit-scrollbar-thumb { background: #237ca3; border-radius: 4px; }
        #rosterLoadingSpinner[hidden] { display: none; }
        #rosterTable { table-layout: fixed; }
        #rosterTable th:nth-child(1) { width: 4%; }
        #rosterTable th:nth-child(2) { width: 5%; }
        #rosterTable th:nth-child(3) { width: 11%; }
        #rosterTable th:nth-child(4) { width: 19%; }
        #rosterTable th:nth-child(5) { width: 8%; }
        #rosterTable th:nth-child(6) { width: 31%; }
        #rosterTable th:nth-child(7) { width: 22%; }
        #rosterTable th, #rosterTable td { white-space: normal !important; overflow-wrap: anywhere; }
        #rosterTable thead th { position: sticky; top: 0; z-index: 2; background: linear-gradient(135deg, #082647 0%, #1c3e64 100%); color: #fff; }
        #rosterTable .roster-select, #rosterTable .select2-container { min-width: 0; max-width: 100%; width: 100% !important; }
        @media (max-width: 768px) {
            #rosterTable th, #rosterTable td { padding: 10px 4px; }
            #rosterTable th:nth-child(1), #rosterTable th:nth-child(2) { width: 7%; }
            #rosterTable th:nth-child(3) { width: 12%; }
            #rosterTable th:nth-child(4) { width: 18%; }
            #rosterTable th:nth-child(5) { width: 9%; }
            #rosterTable th:nth-child(6) { width: 25%; }
        }
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
        <script src="{{ asset('assets/js/monthly-roster-import.js') }}?v={{ filemtime(public_path('assets/js/monthly-roster-import.js')) }}"></script>
        <script src="{{ asset('assets/js/trip-roster-scroll.js') }}?v={{ filemtime(public_path('assets/js/trip-roster-scroll.js')) }}"></script>
    @endsection
</x-app-layout>
