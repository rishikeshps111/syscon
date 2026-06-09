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
                $('#activityLogsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: "{{ route('activity-logs.index') }}",
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
            });
        </script>
    @endsection
</x-app-layout>
