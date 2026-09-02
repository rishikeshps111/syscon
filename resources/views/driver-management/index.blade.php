@section('title')
    Driver Management
@endsection
<style>
    .swal2-popup:has(.driver-qr-modal) {
    width: 440px !important;
    max-width: calc(100% - 30px);
    padding: 24px !important;

    border-radius: 14px !important;
    background: #fff !important;

    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18) !important;
}


/* Modal Title */
.swal2-popup:has(.driver-qr-modal) .swal2-title {
    margin: 0 0 18px;

    color: #1e293b;
    font-size: 19px;
    font-weight: 700;
}


/* =========================================
   QR Container
   ========================================= */

.driver-qr-modal {
    text-align: center;
    padding: 4px 0 0;
}

.driver-qr-box {
    display: flex;
    align-items: center;
    justify-content: center;

    width: fit-content;
    margin: 0 auto 14px;
    padding: 14px;

    background: #fff;

    border: 1px solid #e2e8f0;
    border-radius: 12px;

    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}


/* QR SVG */
.driver-qr-box svg {
    display: block;

    width: 250px;
    height: 250px;

    max-width: 100%;

    border-radius: 4px;
}


/* Driver Code */
.driver-qr-code {
    margin-top: 8px;

    color: #64748b;
    font-size: 12px;
    font-weight: 500;
}

.driver-qr-code strong {
    display: inline-block;

    margin-left: 3px;

    color: #2a74a6;
    font-size: 13px;
    font-weight: 700;
}


/* Driver Name */
.driver-qr-name {
    margin-top: 6px;

    color: #1e293b;

    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}


/* =========================================
   SweetAlert Buttons
   ========================================= */

.swal2-popup:has(.driver-qr-modal) .swal2-actions {
    gap: 8px;

    margin-top: 20px;
}


/* Copy Button */
.swal2-popup:has(.driver-qr-modal) .swal2-confirm {
    min-height: 38px;
    padding: 0 16px;

    color: #fff !important;
    background: #2a74a6 !important;

    border: 1px solid #2a74a6 !important;
    border-radius: 6px !important;

    font-size: 13px !important;
    font-weight: 600 !important;

    box-shadow: none !important;

    transition: all 0.2s ease;
}

.swal2-popup:has(.driver-qr-modal) .swal2-confirm:hover {
    background: #235f88 !important;
    border-color: #235f88 !important;

    transform: translateY(-1px);

    box-shadow: 0 5px 12px rgba(42, 116, 166, 0.2) !important;
}


/* Close Button */
.swal2-popup:has(.driver-qr-modal) .swal2-cancel {
    min-height: 38px;
    padding: 0 16px;

    color: #475569 !important;
    background: #fff !important;

    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;

    font-size: 13px !important;
    font-weight: 500 !important;

    box-shadow: none !important;

    transition: all 0.2s ease;
}

.swal2-popup:has(.driver-qr-modal) .swal2-cancel:hover {
    color: #1e293b !important;
    background: #f8fafc !important;
    border-color: #94a3b8 !important;

    transform: translateY(-1px);
}
div:where(.swal2-container) h2:where(.swal2-title){
    font-size:20px !important;
    color:#025187 !important;
}
div:where(.swal2-container) button:where(.swal2-styled){
    font-size:13px !important;
    padding:7px 15px !important;
}
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Driver Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Driver Management</li>
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
                                    <label for="licenseTypeFilter">License Type</label>
                                    <select id="licenseTypeFilter" class="form-select shadow-none">
                                        <option value="">---Select---</option>
                                        @foreach ($licenseTypes as $value => $label)
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
                                        <option value="license_expired" @selected(request('expiry_filter') === 'license_expired')>License Expired</option>
                                        <option value="license_expiring" @selected(request('expiry_filter') === 'license_expiring')>License Expiring</option>
                                        <option value="medical_expiring" @selected(request('expiry_filter') === 'medical_expiring')>Medical Expiring</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="filter-btns-top  pt-4 justify-content-start">
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
                            @can('driver-management.create')
                                <a href="{{ route('bulk-import.form', 'drivers') }}" class="imp-btn">Import Drivers</a>
                                <a href="{{ route('driver-management.create') }}" class="add-btn m-0">Add New Driver</a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mt-3 table-container">
                            <div class="row justify-content-end">
                                <div class="col-lg-8">
                                    <div class="table-search btns-group-container" style="margin-bottom:-20px;">
                                        <label for="searchFilter" class="nowrap">Search (Code / Ref Code / Name / Phone)</label>
                                        <input type="text" id="searchFilter" class="form-control shadow-none">
                                        @can('driver-management.view')
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
                                            <th class="text-center nowrap">Driver Code</th>
                                            <th class="text-center nowrap">Ref Code</th>
                                            <th class="text-center nowrap">Name</th>
                                            <th class="text-center nowrap">Phone</th>
                                            <th class="text-center nowrap">License Type</th>
                                            <th class="text-center nowrap">License Expiry</th>
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
        @include('driver-management.partials.js')
    @endsection
</x-app-layout>
