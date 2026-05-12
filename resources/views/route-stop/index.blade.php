@section('title')
    Route Stops
@endsection
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
                            <div class="table-container p-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $route->name }}</h5>
                                        <span class="text-muted">{{ $route->code }}</span>
                                    </div>
                                    <a href="{{ route('routes.index') }}" class="btn btn-secondary">
                                        Back to Routes
                                    </a>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Start Point</div>
                                            <div class="fw-semibold">{{ $route->startPoint?->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">End Point</div>
                                            <div class="fw-semibold">{{ $route->endPoint?->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">State</div>
                                            <div class="fw-semibold">{{ $route->state?->name ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Route Type</div>
                                            <div class="fw-semibold">{{ $route->route_type }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Distance</div>
                                            <div class="fw-semibold">{{ $route->distance ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <div class="text-muted small">Estimate Duration</div>
                                            <div class="fw-semibold">
                                                {{ $route->estimated_duration ? substr($route->estimated_duration, 0, 5) : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="border rounded p-2 h-100">
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
                        <div class="col-lg-4 ms-auto justify-content-end d-flex">
                            @can('routes.edit')
                                <button type="button" id="addNewRouteStop" class="add-btn form-btn">Add Stop</button>
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class=" mt-3 table-container">
                                <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Place Name</th>
                                            <th class="text-center">Expected Reach Time</th>
                                            <th class="text-center">Position</th>
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
    </section>
    @section('scripts')
        @include('route-stop.partials.js')
    @endsection
</x-app-layout>