@section('title')
    Roaster Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Roaster Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Roaster Management</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="collapse" id="filterCollapse">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="stateFilter">State</label>
                                <select id="stateFilter" class="form-select shadow-none select2-filter">
                                    <option value="">---Select---</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="oemFilter">Vendor</label>
                                <select id="oemFilter" class="form-select shadow-none select2-filter">
                                    <option value="">---Select---</option>
                                    @foreach($oems as $oem)
                                        <option value="{{ $oem->id }}">{{ $oem->oem_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="depotFilter">Depot</label>
                                <select id="depotFilter" class="form-select shadow-none select2-filter">
                                    <option value="">---Select---</option>
                                    @foreach($depots as $depot)
                                        <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="driverFilter">Driver</label>
                                <select id="driverFilter" class="form-select shadow-none select2-filter">
                                    <option value="">---Select---</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->user?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="dateFromFilter">From Date</label>
                                <input type="date" id="dateFromFilter" class="form-control shadow-none">
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="dateToFilter">To Date</label>
                                <input type="date" id="dateToFilter" class="form-control shadow-none">
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="shiftTypeFilter">Shift Type</label>
                                <select id="shiftTypeFilter" class="form-select shadow-none">
                                    <option value="">---Select---</option>
                                    @foreach($shiftTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3 o-f-inp">
                                <label for="statusFilter">Status</label>
                                <select id="statusFilter" class="form-select shadow-none">
                                    <option value="">---Select---</option>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
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
                            <a class="add-btn bg-filter" data-bs-toggle="collapse" href="#filterCollapse">Filters</a>
                            @can('rosters.create')
                                <a href="{{ route('rosters.create') }}" class="add-btn">Create Roaster</a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="mt-3 table-container">
                    <div class="row justify-content-end">
                        <div class="col-lg-7">
                            <div class="table-search">
                                <label for="searchFilter" class="nowrap">Search</label>
                                <input type="text" id="searchFilter" class="form-control shadow-none" placeholder="Roster / driver / vehicle / trip">
                                @can('rosters.view')
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
                                    <th class="text-center nowrap">SL No</th>
                                    <th class="text-center nowrap">Roster Code</th>
                                    <th class="text-center nowrap">Date</th>
                                    <th class="text-center nowrap">Shift Type</th>
                                    <th class="text-center nowrap">Driver Name</th>
                                    <th class="text-center nowrap">Vehicle</th>
                                    <th class="text-center nowrap">Trip Code</th>
                                    <th class="text-center nowrap">Reporting To Time</th>
                                    <th class="text-center nowrap">Status</th>
                                    <th class="text-center nowrap">Attendance Status</th>
                                    <th class="text-center nowrap">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('roster.partials.modals')

    @section('scripts')
        @include('roster.partials.js')
    @endsection
</x-app-layout>
