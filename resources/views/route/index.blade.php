@section('title')
    Routes
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Route Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Master</li>
                    <li class="breadcrumb-item active">Route Management</li>
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
                                        @foreach($states as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="districtFilter">District</label>
                                    <select id="districtFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach($districts as $district)
                                            <option value="{{ $district->id }}" data-state-id="{{ $district->state_id }}">{{ $district->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="routeTypeFilter">Route Type</label>
                                    <select id="routeTypeFilter" class="form-select shadow-none select2-filter">
                                        <option value="">--- Select ---</option>
                                        @foreach($routeTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="routeCategoryFilter">Route Category</label>
                                    <select id="routeCategoryFilter" class="form-select shadow-none select2-filter">
                                        <option value="">--- Select ---</option>
                                        @foreach($routeCategories as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Status</label>
                                    <select id="statusFilter" class="form-select shadow-none select2-filter">
                                        <option value="">--- Select ---</option>
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="filter-btns-top pt-4 justify-content-start">
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
                            <div class="btns-group-container">
                            <a class="filter-btnss" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                                aria-expanded="true" aria-controls="filterCollapse">Filters</a>
                            @can('routes.create')
                                <a href="{{ route('routes.create') }}" class="add-btn m-0">Add Route</a>
                            @endcan
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container">
                                <div class="row justify-content-end">
                                    <div class="col-lg-8">
                                        <div class="table-search btns-group-container">
                                            <label for="searchFilter" class="nowrap">Search</label>
                                            <input type="text" id="searchFilter" class="form-control shadow-none"
                                                placeholder="Route code / Route name">
                                            @can('routes.view')
                                                <button id="exportSelected" class="exp-btn">Export Data</button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <div class="table-over">
                                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center nowrap"><input type="checkbox"
                                                    class="fieldCheck" id="checkAll">
                                            </th>
                                            <th class="text-center nowrap">SL NO</th>
                                            <th class="text-center nowrap">Route Code</th>
                                            <th class="text-center nowrap">Route Name</th>
                                            <th class="text-center">Starting &rarr; Ending Depot</th>
                                            <th class="text-center">Approximate Distance</th>
                                            {{-- <th class="text-center">Assigned Vehicle</th>
                                            <th class="text-center">Assigned Driver</th> --}}
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

    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="changeStatusForm" class="modal-content">
                @csrf
                <input type="hidden" id="routeStatusId" name="id">
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
                <div class="modal-btns-last p-3">
                    <button type="button" class="modal-btn-1" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="modal-btn-2">Update</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('route.partials.js')
    @endsection
</x-app-layout>
