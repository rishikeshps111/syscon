@section('title')
    Attendance Management
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Attendance Management</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Attendance Management</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <div class="row mb-3">
                        <div class="col-lg-3 mb-3">
                            <div class="o-f-inp">
                                <label for="yearFilter">Year</label>
                                <select id="yearFilter" class="form-select shadow-none attendance-filter">
                                    <option value="">---Select---</option>
                                    @foreach($years as $filterYear)
                                        <option value="{{ $filterYear }}">{{ $filterYear }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <div class="o-f-inp">
                                <label for="monthFilter">Month</label>
                                <select id="monthFilter" class="form-select shadow-none attendance-filter">
                                    <option value="">---Select---</option>
                                    @foreach($months as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                            <button type="button" id="resetAttendanceFilters" class="reset-btn">Reset</button>
                            @can('attendance-management.create')
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('attendance-management.import.form') }}" class="btn btn-primary">Import CSV</a>
                                    <a href="{{ route('attendance-management.create') }}" class="add-btn">Add</a>
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="table-over">
                        <table id="attendanceTable" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="text-center nowrap">SL NO</th>
                                    <th class="text-center nowrap">Month</th>
                                    <th class="text-center nowrap">Year</th>
                                    <th class="text-center nowrap">User Type</th>
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

    @section('scripts')
        @include('attendance-management.partials.js')
    @endsection
</x-app-layout>
