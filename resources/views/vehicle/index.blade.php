@section('title')
    Vehicle Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Vehicle Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Vehicle Management</li>
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
                                    <label for="oemFilter">Filter by OEM</label>
                                    <select id="oemFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($oems as $oem)
                                            <option value="{{ $oem->id }}">{{ $oem->oem_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="vehicleTypeFilter">Vehicle Type</label>
                                    <select id="vehicleTypeFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($vehicleTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="fuelTypeFilter">Fuel Type</label>
                                    <select id="fuelTypeFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($fuelTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Status</label>
                                    <select id="statusFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="gpsFilter">GPS Enabled</label>
                                    <select id="gpsFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        <option value="1">Enabled</option>
                                        <option value="0">Disabled</option>
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
                            @can('vehicles.create')
                                <a href="{{ route('bulk-import.form', 'vehicles') }}" class="add-btn">Import Vehicles</a>
                                <a href="{{ route('vehicles.create') }}" class="add-btn">Add Vehicle</a>
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
                                            placeholder="Vehicle no / Code / Engine / Chassis">
                                        @can('vehicles.view')
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
                                            <th class="text-center nowrap">Vehicle No</th>
                                            <th class="text-center nowrap">Type</th>
                                            <th class="text-center nowrap">Fuel Type</th>
                                            <th class="text-center nowrap">OEM</th>
                                            <th class="text-center nowrap">Capacity</th>
                                            <th class="text-center nowrap">Insurance Expiry</th>
                                            <th class="text-center nowrap">Fitness Expiry</th>
                                            <th class="text-center nowrap">GPS Status</th>
                                            <th class="text-center nowrap">Status</th>
                                            <th class="text-center nowrap">Actions</th>
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
        @include('vehicle.partials.js')
    @endsection
</x-app-layout>
