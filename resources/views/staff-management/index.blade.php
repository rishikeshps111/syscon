@section('title')
    Staff Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Staff Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Staff Management</li>
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
                                    <label for="roleFilter">Filter by Role</label>
                                    <select id="roleFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach($employeeRoles as $role)<option value="{{ $role }}">{{ $role }}</option>@endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3" id="designationFilterWrap">
                                <div class="o-f-inp">
                                    <label for="designationFilter">Filter by Designation</label>
                                    <select id="designationFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($designations as $designation)
                                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="depotFilter">Filter by Depot</label>
                                    <select id="depotFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($depots as $depot)<option value="{{ $depot->id }}">{{ $depot->name }}</option>@endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="employmentTypeFilter">Filter by Employment Type</label>
                                    <select id="employmentTypeFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($employmentTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateOfJoiningFilter">Date of Joining</label>
                                    <input type="date" id="dateOfJoiningFilter" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="col-lg-4 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Status</label>
                                    <select id="statusFilter" class="form-select shadow-none">
                                        <option value="">--- Select ---</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
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
                                aria-expanded="false" aria-controls="filterCollapse">Filters</a>
                            @can('staff-management.create')
                                <a href="{{ route('bulk-import.form', 'staff') }}" class="add-btn">Import Staff</a>
                                <a href="{{ route('staff-management.create') }}" class="add-btn">Add New Staff</a>
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
                                        <label for="searchFilter" class="nowrap">Search (Name / Code / Ref Code)</label>
                                        <input type="text" id="searchFilter" class="form-control shadow-none">
                                        @can('staff-management.view')
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
                                            <th class="text-center nowrap">Staff Code</th>
                                            <th class="text-center nowrap">Ref Code</th>
                                            <th class="text-center nowrap">Staff Name</th>
                                            <th class="text-center nowrap">Role</th>
                                            <th class="text-center">Designation</th>
                                            <th class="text-center">DOJ</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Actions</th>
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
        @include('staff-management.partials.js')
    @endsection
</x-app-layout>
