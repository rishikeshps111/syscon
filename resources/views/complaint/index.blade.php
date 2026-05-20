@section('title')
    Complaints
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Complaint Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Complaint Management</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <ul class="nav nav-tabs nav-tabs-bordered justify-content-start">
                <li class="nav-item ps-0 ms-0">
                    <a href="{{ route('complaints.index', ['reported_by_role' => 'supervisor']) }}"
                        class="nav-link ms-0 mb-0 {{ $activeRole === 'supervisor' ? 'active' : '' }}">Supervisor</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('complaints.index', ['reported_by_role' => 'controller']) }}"
                        class="nav-link mb-0 {{ $activeRole === 'controller' ? 'active' : '' }}">Controller</a>
                </li>
            </ul>

            <div class="filters-complaint mt-3">
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="againstRoleFilter">Against Role</label>
                            <select id="againstRoleFilter" class="form-select shadow-none">
                                <option value="">---Select---</option>
                                @foreach ($againstRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="categoryFilter">Category</label>
                            <select id="categoryFilter" class="form-select shadow-none">
                                <option value="">---Select---</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 mb-3">
                        <div class="o-f-inp">
                            <label for="severityFilter">Severity</label>
                            <select id="severityFilter" class="form-select shadow-none">
                                <option value="">---Select---</option>
                                @foreach ($severities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
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
                    <div class="col-lg-6 mb-3 d-flex align-items-end justify-content-end">
                        <button type="button" id="resetFilters" class="btn btn-secondary me-2">Reset</button>
                        <button type="button" id="searchFilters" class="search-btn">Search</button>
                    </div>
                </div>
            </div>

            <div class="mt-3 table-container">
                <div class="row justify-content-end">
                    <div class="col-lg-8">
                        <div class="table-search">
                            <label for="searchFilter" class="nowrap">Search</label>
                            <input type="text" id="searchFilter" class="form-control shadow-none"
                                placeholder="ID / Name">
                            @can('complaints.view')
                                <button id="exportSelected" class="exp-btn me-2">Export Data</button>
                            @endcan
                            @can('complaints.create')
                                <a href="{{ route('complaints.create') }}" class="add-btn mx-0">Add</a>
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
                                <th class="text-center nowrap">ID</th>
                                <th class="text-center nowrap">Date</th>
                                <th class="text-center nowrap">Reported By</th>
                                <th class="text-center nowrap">Against</th>
                                <th class="text-center nowrap">Category</th>
                                <th class="text-center">Severity</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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

    <div class="modal fade" id="assignActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="assignActionForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Assign for Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="o-f-inp mb-3">
                        <label for="assignedTo">Assigned To <span class="text-danger">*</span></label>
                        <select id="assignedTo" name="assigned_to" class="form-select shadow-none">
                            <option value="">---Select---</option>
                            @foreach ($assignedToOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text assigned_to_error"></span>
                    </div>
                    <div class="o-f-inp mb-3">
                        <label for="actionTaken">Action Taken</label>
                        <select id="actionTaken" name="action_taken" class="form-select shadow-none">
                            <option value="">---Select---</option>
                            @foreach ($actionTakenOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-danger error-text action_taken_error"></span>
                    </div>
                    <div class="o-f-inp">
                        <label for="actionDate">Action Date</label>
                        <input type="date" id="actionDate" name="action_date" class="form-control shadow-none">
                        <span class="text-danger error-text action_date_error"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>

    @section('scripts')
        @include('complaint.partials.js')
    @endsection
</x-app-layout>
