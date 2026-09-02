@section('title')
    Holidays
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Holiday</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item active">Manage Holiday</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row mb-3">
                        <div class="col-lg-6 ms-auto btns-group-container">
                            @can('holidays.create')
                                <a href="{{ route('holidays.create') }}" class="add-btn form-btn me-1">Add Holiday</a>
                            @endcan
                            @can('holidays.view')
                                <a href="{{ route('holidays.calendar-view') }}" class="holiday-btn m-0">Holiday calendar</a>
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 d-none">
                            <div class="o-f-inp">
                                <label for="searchFilter">Search</label>
                                <input type="text" id="searchFilter" class="form-control shadow-none"
                                    placeholder="Name / Code">
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="yearFilter">Year</label>
                                <select id="yearFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ now()->year == $year ? 'selected' : '' }}>{{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="typeFilter">Holiday Type</label>
                                <select id="typeFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach ($holidayTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="locationFilter">Location</label>
                                <select id="locationFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="all">All Locations</option>
                                    <option value="state">Specific State</option>
                                    <option value="branch">Specific Branch</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 center-gap ps-1">
                            <div class="o-f-inp">
                                <label for="statusFilter">Status</label>
                                <select id="statusFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                        
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mt-3 table-container">
                                <div class="table-over-cs">
                                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Code</th>
                                            <th class="text-center">Holiday Name</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center">Type</th>
                                            <th class="text-center">Location</th>
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
        </div>
    </section>
    @section('scripts')
        @include('holiday.partials.js')
    @endsection
</x-app-layout>
