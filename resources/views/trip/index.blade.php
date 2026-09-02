@section('title')
    Trip Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Trip Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Trip Management</li>
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
                                    <label for="depotFilter">Search by Depot</label>
                                    <select id="depotFilter" class="form-select shadow-none select2-filter">
                                        <option value="">---Select---</option>
                                        @foreach ($depots as $depot)
                                            <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="statusFilter">Filter by Status</label>
                                    <select id="statusFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                                {{ $label }}</option>
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
                        <div class="btns-group-container">
                            <a class="filter-btnss" data-bs-toggle="collapse" href="#filterCollapse" role="button"
                                aria-expanded="false" aria-controls="filterCollapse">Filters</a>
                            @can('trips.create')
                                <a href="{{ route('trips.create') }}" class="add-btn m-0">Create Trip</a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mt-3 table-container">
                            <div class="row justify-content-end">
                                <div class="col-lg-7">
                                    <div class="table-search btns-group-container" style="margin-bottom:-20px;">
                                        <label for="searchFilter" class="nowrap">Search</label>
                                        <input type="text" id="searchFilter" class="form-control shadow-none"
                                            placeholder="Trip No / Trip">
                                        @can('trips.view')
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
                                            <th class="text-center nowrap">Trip No</th>
                                            <th class="text-center nowrap">Trip Title</th>
                                            {{-- <th class="text-center nowrap">Route</th> --}}
                                            {{-- <th class="text-center nowrap">State</th> --}}
                                            <th class="text-center nowrap">Depot</th>
                                            {{-- <th class="text-center nowrap">Start Point</th> --}}
                                            {{-- <th class="text-center nowrap">End Point</th> --}}
                                            {{-- <th class="text-center nowrap">Vehicle Classification</th> --}}
                                            {{-- <th class="text-center nowrap">Trip Nature</th> --}}
                                            {{-- <th class="text-center nowrap">Rounds / Trip</th> --}}
                                            {{-- <th class="text-center nowrap">Schedule Km</th> --}}
                                            {{-- <th class="text-center nowrap">Total Trips</th> --}}
                                            <th class="text-center nowrap">From Date</th>
                                            <th class="text-center nowrap">To Date</th>
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

    <div class="modal fade" id="changeTripStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="changeTripStatusForm" class="modal-content">
                @csrf
                <input type="hidden" name="id" id="statusTripId">
                <div class="modal-header">
                    <h5 class="modal-title">Update Trip Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="o-f-inp mb-3">
                        <label for="modalTripStatus">Status <span class="text-danger">*</span></label>
                        <select id="modalTripStatus" name="status" class="form-select shadow-none">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="o-f-inp" id="modalCancellationReasonWrap">
                        <label for="modalCancellationReason">Reason for cancellation</label>
                        <textarea id="modalCancellationReason" name="cancellation_reason"
                            class="form-control shadow-none" rows="3"></textarea>
                        <span class="text-danger error-text cancellation_reason_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="tripStatusSubmitBtn">Update</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('trip.partials.js')
    @endsection
</x-app-layout>