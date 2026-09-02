@section('title')
    Reason for Driver Change
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Reasons for Driver Change</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Trip Management</li>
                    <li class="breadcrumb-item active">Reasons for Driver Change</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="o-f-inp">
                        <label for="statusFilter">Filter by Status</label>
                        <select name="status" id="statusFilter" class="form-select shadow-none">
                            <option value="">--- Select ---</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <button type="button" id="resetFilters" class="btn btn-secondary mb-1">Reset</button>
                </div>
                <div class="col-lg-5 ms-auto btns-group-container">
                    @can('driver-change-reasons.create')
                        <button type="button" class="add-btn form-btn">Add Reason for Driver Change</button>
                    @endcan
                    @can('driver-change-reasons.export')
                        <button id="exportSelected" class="exp-btn ms-1">Export</button>
                    @endcan
                </div>
            </div>
            <div class="table-container mt-3">
                <div class="table-over-cs">
                    <table id="table" class="table align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th class="text-center">Sl No</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Name</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    @section('scripts')
        @include('driver-change-reason.partials.js')
    @endsection
</x-app-layout>
