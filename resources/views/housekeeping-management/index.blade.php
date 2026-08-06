@section('title')
    Housekeeping Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Housekeeping Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Housekeeping Management</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="collapse" id="filterCollapse">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="stateFilter">Filter by State</label>
                                    <select id="stateFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="employmentTypeFilter">Employment Type</label>
                                    <select id="employmentTypeFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($employmentTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="verificationStatusFilter">Verification Status</label>
                                    <select id="verificationStatusFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($verificationStatuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Status</label>
                                    <select id="statusFilter" class="form-select shadow-none">
                                        <option value="">--- Select ---</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="expiryFilter">Expiry Filters</label>
                                    <select id="expiryFilter" class="form-select shadow-none">
                                        <option value="">--- Select ---</option>
                                        <option value="medical_expiring" @selected(request('expiry_filter') === 'medical_expiring')>Medical Expiring</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="filter-btns-top">
                                    <a href="#!" class="reset-btn" id="resetFilters">Reset</a>
                                    <button type="button" class="search-btn" id="searchFilters">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-3">
            <div class="main-table-container">
                <div class="row">
                    <div class="col-lg-12 ms-auto">
                        <div class="btn-flex">
                            <a class="add-btn bg-filter" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                                aria-expanded="true" aria-controls="filterCollapse">Filters</a>
                            @can('housekeeping-management.create')
                                <a href="{{ route('housekeeping-management.create') }}" class="add-btn">Add New Housekeeping</a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mt-3 table-container">
                            <div class="row justify-content-end">
                                <div class="col-lg-8">
                                    <div class="table-search">
                                        <label for="searchFilter" class="nowrap">Search (Code / Name / Phone)</label>
                                        <input type="text" id="searchFilter" class="form-control shadow-none">
                                        @can('housekeeping-management.view')
                                            <button id="exportSelected" class="exp-btn">Export Data</button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="table-over">
                                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center nowrap"><input type="checkbox" id="checkAll"></th>
                                            <th class="text-center nowrap">SL NO</th>
                                            <th class="text-center nowrap">Housekeeping Code</th>
                                            <th class="text-center nowrap">Name</th>
                                            <th class="text-center nowrap">Phone</th>
                                            <th class="text-center nowrap">Employment Type</th>
                                            <th class="text-center nowrap">Depot</th>
                                            <th class="text-center nowrap">Verification Status</th>
                                            <th class="text-center nowrap">Status</th>
                                            <th class="text-center nowrap">Action</th>
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
        @include('housekeeping-management.partials.js')
    @endsection
</x-app-layout>
