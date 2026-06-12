@section('title')
    Activity Logs
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Activity Logs</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">
                    <div class="row align-items-end g-3 mb-3">
                        <div class="col-lg-3 o-f-inp">
                            <label for="fromDateFilter">From Date</label>
                            <input type="date" id="fromDateFilter" class="form-control shadow-none">
                        </div>
                        <div class="col-lg-3 o-f-inp">
                            <label for="toDateFilter">To Date</label>
                            <input type="date" id="toDateFilter" class="form-control shadow-none">
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex gap-2 justify-content-lg-end">
                                <button type="button" id="searchFilters" class="btn btn-primary d-none">Search</button>
                                <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                                <button type="button" id="exportActivityLogs" class="btn btn-success">Export Data</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <table id="activityLogsTable" class="table align-middle mb-0 table tble-cstm mt-3"
                            style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="text-center">SL NO</th>
                                    <th class="text-center">Module</th>
                                    <th class="text-center">Event</th>
                                    <th class="text-center">Name of User</th>
                                    <th class="text-center">Role</th>
                                    <th class="text-center">Date and Time</th>
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
                var table = $('#activityLogsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: "{{ route('activity-logs.index') }}",
                        data: filters
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'module', name: 'module', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'event_name', name: 'event', className: 'text-center' },
                        { data: 'user_name', name: 'causer.name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'role_name', name: 'role_name', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'created_datetime', name: 'created_at', className: 'text-center' }
                    ],
                    order: []
                });

                $('#searchFilters').on('click', reloadTable);
                $('#fromDateFilter, #toDateFilter').on('change', reloadTable);

                $('#resetFilters').on('click', function () {
                    $('#fromDateFilter, #toDateFilter').val('');
                    reloadTable();
                });

                $('#exportActivityLogs').on('click', function () {
                    var url = new URL("{{ route('activity-logs.export') }}", window.location.origin);

                    if ($('#fromDateFilter').val()) {
                        url.searchParams.set('from_date', $('#fromDateFilter').val());
                    }

                    if ($('#toDateFilter').val()) {
                        url.searchParams.set('to_date', $('#toDateFilter').val());
                    }

                    window.location.href = url.toString();
                });

                function filters(data) {
                    data.from_date = $('#fromDateFilter').val();
                    data.to_date = $('#toDateFilter').val();
                }

                function reloadTable() {
                    table.ajax.reload();
                }
            });
        </script>
    @endsection
</x-app-layout>
