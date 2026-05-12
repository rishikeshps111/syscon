@section('title')
    Route Preview
@endsection
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
            <div class="route-header">
                <div>
                    <div class="route-title">{{ $route->name }}</div>
                    <div class="text-muted small">{{ $route->code }} | {{ $route->state?->name ?? '-' }} |
                        {{ $route->route_type }}
                    </div>
                </div>
                <div class="route-time">
                    Duration: {{ $route->estimated_duration ? substr($route->estimated_duration, 0, 5) : '-' }}
                </div>
                <a href="{{ route('routes.preview.export', $route->id) }}" class="btn btn-primary ms-auto">Export
                    CSV</a>
                <a href="{{ route('routes.index') }}" class="btn btn-secondary ">Back to Routes</a>
            </div>

            <div class="timeline">
                <div class="stop active">
                    <div class="stop-name">
                        {{ $route->startPoint?->name ?? '-' }}
                        <span class="badge-cs">Start</span>
                    </div>
                    <div class="stop-time">Start Point</div>
                </div>

                @forelse($route->stops as $stop)
                    <div class="stop">
                        <div class="stop-name">{{ $stop->name }}</div>
                        <div class="stop-time">
                            {{ $stop->expected_reach_time ? substr($stop->expected_reach_time, 0, 5) : '-' }}
                        </div>
                    </div>
                @empty
                    <div class="stop">
                        <div class="stop-name">No stops added</div>
                        <div class="stop-time">Add stops to complete this route preview.</div>
                    </div>
                @endforelse

                <div class="stop active">
                    <div class="stop-name">
                        {{ $route->endPoint?->name ?? '-' }}
                        <span class="badge-cs">End</span>
                    </div>
                    <div class="stop-time">End Point</div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>