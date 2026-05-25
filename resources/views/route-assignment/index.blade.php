@section('title')
    Route Assignments
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Route Assignments</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('routes.index') }}">Routes</a></li>
                    <li class="breadcrumb-item active">Route Assignments</li>
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
                                        <h5 class="mb-1">{{ $route->route_name }}</h5>
                                        <span class="text-muted">{{ $route->route_code }}</span>
                                    </div>
                                    <a href="{{ route('routes.index') }}" class="btn btn-secondary">Back to Routes</a>
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
                                            <div class="text-muted small">Status</div>
                                            <span class="badge {{ $route->is_active ? 'status-green' : 'status-red' }}">
                                                {{ $route->status }}
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
                                <button type="button" id="addNewRouteAssignment" class="add-btn form-btn">Add Assignment</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container">
                                <table id="table" class="table align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Vehicle</th>
                                            <th class="text-center">Driver</th>
                                            <th class="text-center">Shift</th>
                                            <th class="text-center">Start Time</th>
                                            <th class="text-center">End Time</th>
                                            <th class="text-center">Effective From</th>
                                            <th class="text-center">Effective To</th>
                                            <th class="text-center">Status</th>
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
        @include('route-assignment.partials.js')
    @endsection
</x-app-layout>
