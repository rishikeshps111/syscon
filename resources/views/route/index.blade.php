@section('title')
    Routes
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Route</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Master</li>
                    <li class="breadcrumb-item active">Manage Route</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="stateFilter">Filter by State</label>
                                <select name="state_id" id="stateFilter" class="form-select shadow-none multi-select">
                                    <option value="">--- Select ---</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="startPointFilter">Filter by Start Point</label>
                                <select name="start_point_id" id="startPointFilter" class="form-select shadow-none multi-select">
                                    <option value="">--- Select ---</option>
                                    @foreach($depots as $depot)
                                        <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="endPointFilter">Filter by End Point</label>
                                <select name="end_point_id" id="endPointFilter" class="form-select shadow-none multi-select">
                                    <option value="">--- Select ---</option>
                                    @foreach($depots as $depot)
                                        <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="statusFilter">Filter by Status</label>
                                <select name="status" id="statusFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 ms-auto justify-content-end d-flex">
                            @can('routes.create')
                                <button type="button" id="addNewRoute" class="add-btn form-btn">Add Route</button>
                            @endcan
                            @can('routes.view')
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class=" mt-3 table-container table-over">
                                <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Route Code</th>
                                            <th class="text-center">Route Name</th>
                                            <th class="text-center">Start Point</th>
                                            <th class="text-center">End Point</th>
                                            <th class="text-center">Distance</th>
                                            <th class="text-center">Estimate Duration</th>
                                            <th class="text-center">Route Type</th>
                                            <th class="text-center">State</th>
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
    </section>
    @section('scripts')
        @include('route.partials.js')
    @endsection
</x-app-layout>
