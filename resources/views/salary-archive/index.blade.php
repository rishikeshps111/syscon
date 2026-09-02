@section('title', 'Salary Archive')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Salary Archive</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item active">Salary Archive</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container">
            <div class="row mb-4">
                <div class="col-lg-3 o-f-inp mb-2">
                    <label for="yearFilter">Year</label>
                    <select id="yearFilter" class="form-select shadow-none">
                        <option value="">--- Select ---</option>
                        @foreach($years as $year)<option value="{{ $year }}">{{ $year }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 o-f-inp mb-2">
                    <label for="monthFilter">Month</label>
                    <select id="monthFilter" class="form-select shadow-none">
                        <option value="">--- Select ---</option>
                        @foreach($months as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 o-f-inp mb-2">
                    <label for="depotFilter">Depo</label>
                    <select id="depotFilter" class="form-select shadow-none">
                        <option value="">--- Select ---</option>
                        @foreach($depots as $depot)<option value="{{ $depot->id }}">{{ $depot->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-3 o-f-inp mb-2 center-gap ps-0">
                    <div class="w-100">
                        <label for="roleFilter">Role</label>
                    <select id="roleFilter" class="form-select shadow-none">
                        <option value="">--- Select ---</option>
                        @foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
                    </select>
                    </div>
                     <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                </div>
               
            </div>

            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%">
                    <thead><tr>
                        <th class="text-center">SL No</th>
                        <th class="text-center">Month</th>
                        <th class="text-center">Year</th>
                        <th class="text-center">Depo</th>
                        <th class="text-center">Role</th>
                        <th class="text-center">Payment Method</th>
                        <th class="text-center">Employees</th>
                        <th class="text-center">Approved By</th>
                        <th class="text-center">Approved Date and Time</th>
                        <th class="text-center">Action</th>
                    </tr></thead>
                </table>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var table = $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: @json(route('salary-archives.index')),
                        data: function (data) {
                            data.year = $('#yearFilter').val();
                            data.month = $('#monthFilter').val();
                            data.depot_id = $('#depotFilter').val();
                            data.role_id = $('#roleFilter').val();
                        }
                    },
                    columns: [
                        {data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'month_name', name: 'month', className: 'text-center'},
                        {data: 'year', name: 'year', className: 'text-center'},
                        {data: 'depot_name', orderable: false, className: 'text-center'},
                        {data: 'role_name', orderable: false, className: 'text-center'},
                        {data: 'payment_method', className: 'text-center'},
                        {data: 'items_count', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'approved_by_name', orderable: false, className: 'text-center'},
                        {data: 'approved_date_time', name: 'approved_at', className: 'text-center'},
                        {data: 'action', orderable: false, searchable: false, className: 'text-center'}
                    ]
                });

                $('#yearFilter, #monthFilter, #depotFilter, #roleFilter').on('change', function () {
                    table.ajax.reload();
                });
                $('#resetFilters').on('click', function () {
                    $('#yearFilter, #monthFilter, #depotFilter, #roleFilter').val('');
                    table.ajax.reload();
                });
            });
        </script>
    @endsection
</x-app-layout>
