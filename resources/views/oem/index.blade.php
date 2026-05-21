@section('title')
    OEM Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>OEM Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">OEM/Vendor Management</li>
                    <li class="breadcrumb-item active">OEM</li>
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
                                    <label for="oemTypeFilter">Filter by OEM Type</label>
                                    <select id="oemTypeFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($oemTypes as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateFromFilter">From Date</label>
                                    <input type="date" id="dateFromFilter" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateToFilter">To Date</label>
                                    <input type="date" id="dateToFilter" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Status</label>
                                    <select id="statusFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
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
                            @can('oems.create')
                                <a href="{{ route('oems.create') }}" class="add-btn">Add OEM</a>
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
                                        <label for="searchFilter" class="nowrap">Search</label>
                                        <input type="text" id="searchFilter" class="form-control shadow-none"
                                            placeholder="Code / Name / GST / PAN">
                                        @can('oems.view')
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
                                            <th class="text-center nowrap">OEM Code</th>
                                            <th class="text-center nowrap">OEM Name</th>
                                            <th class="text-center nowrap">Type</th>
                                            <th class="text-center nowrap">State</th>
                                            <th class="text-center nowrap">Verification Status</th>
                                            <th class="text-center nowrap">Last Updated</th>
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

    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="changeStatusForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="o-f-inp">
                        <label for="modalStatus">Status <span class="text-danger">*</span></label>
                        <select id="modalStatus" name="status" class="form-select shadow-none">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text status_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('oem.partials.js')
    @endsection
</x-app-layout>
