@section('title')
    Route Stops
@endsection
<style>
    .stop-widget {
    position: relative !important;

    height: 100% !important;
    min-height: 135px !important;

    padding: 16px !important;

    background: #e8ecf2 !important;

    border: 1px solid #e5e7eb !important;
    border-radius: 14px !important;

    overflow: hidden !important;


    transition: all 0.25s ease !important;
}


/* Decorative background shape */
.stop-widget::after {
    content: "" !important;

    position: absolute !important;

    width: 80px !important;
    height: 80px !important;

    right: -35px !important;
    bottom: -40px !important;

    background: #f8fafc !important;

    border-radius: 50% !important;

    pointer-events: none !important;

    transition: all 0.25s ease !important;
}




/* =========================================
   Icon
   ========================================= */

.stop-widget .stop-widget-icon {
    position: relative !important;
    z-index: 2 !important;

    width: 42px !important;
    height: 42px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    margin-bottom: 12px !important;

    border-radius: 10px !important;

    font-size: 17px !important;

    transition: all 0.25s ease !important;
}


.stop-widget:hover .stop-widget-icon {
    transform: scale(1.08) !important;
}


/* =========================================
   Different Icon Backgrounds
   ========================================= */

/* Starting Depot */
.stop-widget .start-icon {
    color: #2563eb !important;

    background: #eff6ff !important;

    border: 1px solid #dbeafe !important;
}


/* Ending Depot */
.stop-widget .end-icon {
    color: #16a34a !important;

    background: #f0fdf4 !important;

    border: 1px solid #dcfce7 !important;
}


/* State */
.stop-widget .state-icon {
    color: #7c3aed !important;

    background: #f5f3ff !important;

    border: 1px solid #ede9fe !important;
}


/* Route Type */
.stop-widget .route-icon {
    color: #0891b2 !important;

    background: #ecfeff !important;

    border: 1px solid #cffafe !important;
}


/* Distance */
.stop-widget .distance-icon {
    color: #ea580c !important;

    background: #fff7ed !important;

    border: 1px solid #ffedd5 !important;
}


/* Status */
.stop-widget .status-icon {
    color: #ca8a04 !important;

    background: #fefce8 !important;

    border: 1px solid #fef08a !important;
}


/* =========================================
   Label
   ========================================= */

.stop-widget .text-muted.small {
    position: relative !important;
    z-index: 2 !important;

    display: block !important;

    margin-bottom: 5px !important;

    color: #64748b !important;

    font-size: 12px !important;
    font-weight: 400 !important;

    text-transform: uppercase !important;
    letter-spacing: 0.45px !important;
}


/* =========================================
   Value
   ========================================= */

.stop-widget .fw-semibold {
    position: relative !important;
    z-index: 2 !important;

    display: block !important;

    color: #1e293b !important;

    font-size: 14px !important;
    font-weight: 600 !important;

    line-height: 1.45 !important;

    word-break: break-word !important;
}


/* =========================================
   Status Badge
   ========================================= */

.stop-widget .status-green,
.stop-widget .status-red {
    position: relative !important;
    z-index: 2 !important;

    display: inline-flex !important;
    align-items: center !important;

    margin-top: 2px !important;

    padding: 5px 9px !important;

    border-radius: 20px !important;

    font-size: 10px !important;
    font-weight: 700 !important;
}


/* Active */
.stop-widget .status-green {
    color: #15803d !important;

    background: #f0fdf4 !important;

    border: 1px solid #bbf7d0 !important;
}


/* Inactive */
.stop-widget .status-red {
    color: #dc2626 !important;

    background: #fef2f2 !important;

    border: 1px solid #fecaca !important;
}


/* =========================================
   Status Indicator
   ========================================= */

.stop-widget .status-green::before,
.stop-widget .status-red::before {
    content: "" !important;

    width: 6px !important;
    height: 6px !important;

    margin-right: 5px !important;

    border-radius: 50% !important;
}


.stop-widget .status-green::before {
    background: #16a34a !important;

    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.10) !important;
}


.stop-widget .status-red::before {
    background: #dc2626 !important;

    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.10) !important;
}

</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Route Stops</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Master</li>
                    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
                    <li class="breadcrumb-item active">Route Stops</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="table-container p-3 p-3-sm-0">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 column-rvse">
                                    <div>
                                        <h5 class="mb-1">{{ $route->name }}</h5>
                                        <span class="text-muted">{{ $route->code }}</span>
                                    </div>
                                    <div class="btns-group-container">
                                        <a href="{{ route('routes.index') }}" class="bk-btn">
                                        Back to Routes
                                    </a>
                                      @can('routes.edit')
                                <button type="button" id="addNewRouteStop" class="add-btn form-btn">Add Stop</button>
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon start-icon">
            <i class="fa-solid fa-location-dot"></i>
        </div>
        <div class="text-muted small">Starting Depot</div>
        <div class="fw-semibold">{{ $route->startPoint?->name ?? '-' }}</div>
    </div>
</div>

<div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon end-icon">
            <i class="fa-solid fa-flag-checkered"></i>
        </div>
        <div class="text-muted small">Ending Depot</div>
        <div class="fw-semibold">{{ $route->endPoint?->name ?? '-' }}</div>
    </div>
</div>

<div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon state-icon">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>
        <div class="text-muted small">State</div>
        <div class="fw-semibold">{{ $route->state?->name ?? '-' }}</div>
    </div>
</div>

<div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon route-icon">
            <i class="fa-solid fa-route"></i>
        </div>
        <div class="text-muted small">Route Type</div>
        <div class="fw-semibold">{{ $route->route_type }}</div>
    </div>
</div>

<div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon distance-icon">
            <i class="fa-solid fa-road"></i>
        </div>
        <div class="text-muted small">Approximate Distance</div>
        <div class="fw-semibold">{{ $route->distance ?? '-' }}</div>
    </div>
</div>

<div class="col-lg-2 col-md-6">
    <div class="stop-widget">
        <div class="stop-widget-icon status-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="text-muted small">Status</div>

        <span class="badge {{ $route->is_active ? 'status-green' : 'status-red' }}">
            {{ $route->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class=" mt-3 table-container ">
                                <div class="table-over">
                                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            {{-- <th class="text-center">Sl No</th> --}}
                                            <th class="text-center"></th>
                                            <th class="text-center">Place Name</th>
                                            <th class="text-center">Action</th>
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
    </section>
    @section('scripts')
        @include('route-stop.partials.js')
    @endsection
</x-app-layout>
