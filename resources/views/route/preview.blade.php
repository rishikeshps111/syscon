@section('title')
    Route Preview
@endsection
<style>
    .timeline {
    position: relative !important;

    padding: 8px 8px 8px 42px !important;

    margin: 0 !important;

    background: #ffffff !important;
}


/* =========================================
   Vertical Route Line
   ========================================= */

.timeline::before {
    content: "" !important;

    position: absolute !important;

    top: 18px !important;
    bottom: 18px !important;
    left: 17px !important;

    width: 2px !important;

    background: linear-gradient(
        to bottom,
        #2563eb 0%,
        #93c5fd 50%,
        #16a34a 100%
    ) !important;

    border-radius: 10px !important;
}


/* =========================================
   Individual Stop
   ========================================= */

.timeline .stop {
    position: relative !important;

    min-height: 62px !important;

    padding: 10px 14px !important;
    margin-bottom: 12px !important;

    background: #f8fafc !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;

    transition: all 0.2s ease !important;
}


/* Remove margin from last stop */
.timeline .stop:last-child {
    margin-bottom: 0 !important;
}


/* =========================================
   Stop Marker
   ========================================= */

.timeline .stop::before {
    content: "" !important;

    position: absolute !important;

    left: -34px !important;
    top: 50% !important;

    width: 14px !important;
    height: 14px !important;

    transform: translateY(-50%) !important;

    background: #ffffff !important;

    border: 3px solid #94a3b8 !important;

    border-radius: 50% !important;

    z-index: 2 !important;

    box-sizing: border-box !important;
}


/* Inner marker */
.timeline .stop::after {
    content: "" !important;

    position: absolute !important;

    left: -29px !important;
    top: 50% !important;

    width: 4px !important;
    height: 4px !important;

    transform: translate(-50%, -50%) !important;

    background: #94a3b8 !important;

    border-radius: 50% !important;

    z-index: 3 !important;
}




/* =========================================
   Stop Name
   ========================================= */

.timeline .stop-name {
    color: #1e293b !important;

    font-size: 14px !important;
    font-weight: 600 !important;

    line-height: 1.5 !important;
}


/* =========================================
   Stop Time / Description
   ========================================= */

.timeline .stop-time {
    margin-top: 3px !important;

    color: #64748b !important;

    font-size: 12px !important;
    font-weight: 400 !important;
}


/* =========================================
   Active Start / End Stops
   ========================================= */

.timeline .stop.active {
    background: #eff6ff !important;

    border-color: #bfdbfe !important;
}


/* Active marker */
.timeline .stop.active::before {
    width: 18px !important;
    height: 18px !important;

    left: -36px !important;

    border: 4px solid #2563eb !important;

    background: #ffffff !important;

    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10) !important;
}


/* Active inner dot */
.timeline .stop.active::after {
    left: -27px !important;

    width: 6px !important;
    height: 6px !important;

    background: #2563eb !important;
}


/* =========================================
   Last Active Stop / Ending Depot
   ========================================= */

.timeline .stop.active:last-child {
    background: #f0fdf4 !important;

    border-color: #bbf7d0 !important;
}


.timeline .stop.active:last-child::before {
    border-color: #16a34a !important;

    box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.10) !important;
}


.timeline .stop.active:last-child::after {
    background: #16a34a !important;
}


/* =========================================
   Start / End Description
   ========================================= */

.timeline .stop.active .stop-time {
    color: #2563eb !important;

    font-weight: 600 !important;
}


.timeline .stop.active:last-child .stop-time {
    color: #16a34a !important;
}


/* =========================================
   Short Name Styling
   ========================================= */

.timeline .stop-name {
    word-break: break-word !important;
}


/* =========================================
   Empty Stop
   ========================================= */

.timeline .stop .stop-name:only-child {
    color: #64748b !important;

    font-weight: 600 !important;
}


/* =========================================
   Optional Start / End Badge
   ========================================= */

.timeline .badge-cs {
    display: inline-flex !important;
    align-items: center !important;

    margin-left: 6px !important;
    padding: 3px 7px !important;

    color: #2563eb !important;
    background: #dbeafe !important;

    border: 1px solid #bfdbfe !important;
    border-radius: 20px !important;

    font-size: 9px !important;
    font-weight: 700 !important;

    text-transform: uppercase !important;
}

</style>
<x-app-layout>
    <div class="page-title">
        <h3>Route Preview</h3>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Masters</li>
                <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
                <li class="breadcrumb-item active">Route Preview</li>
            </ol>
        </nav>
    </div>

    <section class="section dashboard">
        <div class="route-card">
            <div class="route-header btns-group-container" >
                <div>
                    <div class="route-title">{{ $route->name }}</div>
                    <div class="text-muted small">{{ $route->code }} | {{ $route->state?->name ?? '-' }} |
                        {{ $route->route_type }}
                    </div>
                </div>
                {{-- <a href="{{ route('routes.preview.export', $route->id) }}" class="btn btn-primary ms-auto">Export
                    CSV</a> --}}
                <a href="{{ route('routes.index') }}" class="bk-btn ms-auto">Back to Routes</a>
            </div>

            <div class="timeline">
                <div class="stop active">
                    <div class="stop-name">
                        {{ $route->startPoint?->name ?? '-' }} ({{ $route->startPoint?->short_name ?? '-' }})
                        {{-- <span class="badge-cs">Start</span> --}}
                    </div>
                    <div class="stop-time">Starting Depot</div>
                </div>

                @forelse($route->stops as $stop)
                    <div class="stop">
                        <div class="stop-name">
                            {{ $stop->location->name }} ({{ $stop->location->short_name ?? '-' }})
                        </div>
                        {{-- <div class="stop-time">
                            {{ $stop->expected_reach_time ? substr($stop->expected_reach_time, 0, 5) : '-' }}
                        </div> --}}
                    </div>
                @empty
                    <div class="stop">
                        <div class="stop-name">No stops added</div>
                        <div class="stop-time">Add stops to complete this route preview.</div>
                    </div>
                @endforelse

                <div class="stop active">
                    <div class="stop-name">
                        {{ $route->endPoint?->name ?? '-' }} ({{ $route->endPoint?->short_name ?? '-' }})
                        {{-- <span class="badge-cs">End</span> --}}
                    </div>
                    <div class="stop-time">Ending Depot</div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>