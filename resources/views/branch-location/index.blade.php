@section('title')
    Branch Locations
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Branch Locations</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Branch Management</li>
                    <li class="breadcrumb-item active">Branch Location</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-4 ms-auto btns-group-container">
                            @can('branch-locations.create')
                                <button type="button" id="addNewBranchLocation" class="add-btn form-btn">Add Branch
                                    Location</button>
                            @endcan
                            @can('branch-locations.view')
                                <button id="exportSelected" class="exp-btn ms-1">Export</button>
                            @endcan
                        </div>
                    </div>
                    <div class="row mt-3">
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
                                <select name="district_id" id="districtFilter"
                                    class="form-select shadow-none multi-select" disabled>
                                    <option value="">--- Select State First ---</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="locationFilter">Filter by Location</label>
                                <select name="location_id" id="locationFilter"
                                    class="form-select shadow-none multi-select" disabled>
                                    <option value="">--- Select District First ---</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="o-f-inp">
                                <label for="statusFilter">Filter by Status</label>
                                <select name="status" id="statusFilter" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 d-flex align-items-end mt-2">
                            <button type="button" id="resetFilters" class="btn btn-secondary mb-1">
                                Reset
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <div class=" mt-3 table-container">
                                <div class="table-over">
                                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3"
                                    style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <input type="checkbox" id="checkAll">
                                            </th>
                                            <th class="text-center">Sl No</th>
                                            <th class="text-center">Code</th>
                                            <th class="text-center">Name</th>
                                            <th class="text-center">State</th>
                                            <th class="text-center">District</th>
                                            <th class="text-center">Location</th>
                                            <th class="text-center">Remarks</th>
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
            </div>
    </section>
    <div class="modal fade" id="changeStatus" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cnt-modal-cs">
                <div class="modal-header">

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-not">
                    <form id="changeStatusForm">
                        @csrf
                        <input type="hidden" name="id" id="change_status_id">
                        <div class="row">
                            <div class="col-lg-12 o-f-inp">
                                <label for="change_status_value">Change Status</label>
                                <select name="status" id="change_status_value" class="form-select shadow-none">
                                    <option value="">---Select---</option>
                                    <option value="active">Active </option>
                                    <option value="inactive">Inactive </option>
                                    <option value="suspended"> Suspended</option>
                                </select>
                                <span class="text-danger error-text status_error"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-btns-last">
                    <button type="button" data-bs-dismiss="modal" class="modal-btn-1">Close</button>
                    <button type="submit" form="changeStatusForm" id="changeStatusSubmit" class="modal-btn-2">Submit</button>
                </div>

            </div>
        </div>
    </div>
    @section('scripts')
        @include('branch-location.partials.js')
    @endsection
</x-app-layout>
