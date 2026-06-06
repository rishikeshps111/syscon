@section('title')
    User Logs
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>User Logs</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">User Logs</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <form method="GET" id="userLogFilterForm">
                        <div class="row align-items-end">
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="dateFilter">Date Filter</label>
                                    <input type="date" id="dateFilter" name="date" class="form-control shadow-none"
                                        value="{{ $filters['date'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="designationFilter">Filter by Designation</label>
                                    <select id="designationFilter" name="designation_id"
                                        class="form-select shadow-none filter-select">
                                        <option value="">--- Select ---</option>
                                        @foreach($designations as $designation)
                                            <option value="{{ $designation->id }}" @selected(($filters['designation_id'] ?? '') == $designation->id)>
                                                {{ $designation->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="o-f-inp">
                                    <label for="staffFilter">Filter by Staff</label>
                                    <select id="staffFilter" name="staff_id"
                                        class="form-select shadow-none filter-select">
                                        <option value="">--- Select ---</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}" @selected(($filters['staff_id'] ?? '') == $member->id)>
                                                {{ trim(($member->code ? $member->code . ' - ' : '') . $member->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <div class="filter-btns-top justify-content-start">
                                    <button type="button" id="resetFilters" class="reset-btn border-0">Reset</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-container">
                        <table id="userLogsTable" class="table align-middle mb-0 table tble-cstm mt-3"
                            style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="text-center">SL NO</th>
                                    <th class="text-center">Designation</th>
                                    <th class="text-center">Staff Name</th>
                                    <th class="text-center">Login Date and Time</th>
                                    <th class="text-center">Logout Date and Time</th>
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
        <script>
            $(function () {
                $('.filter-select').select2({ width: '100%', allowClear: true, placeholder: '--- Select ---' });

                var table = $('#userLogsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('user-logs.index') }}",
                        data: function (data) {
                            data.date = $('#dateFilter').val();
                            data.designation_id = $('#designationFilter').val();
                            data.staff_id = $('#staffFilter').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'designation_name', name: 'designation.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'staff_name', name: 'user.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'login_datetime', name: 'login_at', className: 'text-center' },
                        { data: 'logout_datetime', name: 'logout_at', orderable: false, searchable: false, className: 'text-center' }
                    ],
                    order: [[3, 'desc']]
                });

                $('#userLogFilterForm').on('submit', function (e) {
                    e.preventDefault();
                    table.ajax.reload();
                });

                $('#dateFilter, #staffFilter').on('change', function () {
                    table.ajax.reload();
                });

                $('#designationFilter').on('change', function () {
                    $('#staffFilter').val('').trigger('change.select2');
                    table.ajax.reload();
                });

                $('#resetFilters').on('click', function () {
                    $('#dateFilter').val('');
                    $('#designationFilter, #staffFilter').val('').trigger('change.select2');
                    table.ajax.reload();
                });
            });
        </script>
    @endsection
</x-app-layout>