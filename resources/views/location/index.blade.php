@section('title')
    Locations
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Location</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Master</li>
                    <li class="breadcrumb-item active">Manage Location</li>
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
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="districtFilter">Filter by District</label>
                                <select name="district_id" id="districtFilter" class="form-select shadow-none multi-select" disabled>
                                    <option value="">--- Select State First ---</option>
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
                        <div class="col-lg-2 d-flex align-items-end">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 ms-auto justify-content-end d-flex">
                            @can('locations.create')
                                <button type="button" id="addNewLocation" class="add-btn form-btn">Add Location</button>
                            @endcan
                            @can('locations.view')
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
                                            <th class="text-center">Code</th>
                                            <th class="text-center">Location</th>
                                            <th class="text-center">Pincode</th>
                                            <th class="text-center">District</th>
                                            <th class="text-center">State</th>
                                            <th class="text-center">Default Location</th>
                                            <th class="text-center">Created Date</th>
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
        @include('location.partials.js')
    @endsection
</x-app-layout>
