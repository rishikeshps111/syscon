@section('title', 'Salary Templates')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Salary Templates</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active">Salary Templates</li></ol></nav>
        </div>
        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-12 d-flex justify-content-end">
                    @can('salary-templates.create')
                        <a href="{{ route('salary-templates.create') }}" class="add-btn form-btn text-decoration-none">Add Salary Template</a>
                    @endcan
                </div>
            </div>
            <div class="table-over">
                <table id="table" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%">
                    <thead><tr>
                        <th class="text-center">SL No</th>
                        <th class="text-center">Code</th>
                        <th class="text-center">Role</th>
                        <th class="text-center">Designation</th>
                        <th class="text-center">Components</th>
                        <th class="text-center">Action</th>
                    </tr></thead>
                </table>
            </div>
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('#table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('salary-templates.index') }}",
                    columns: [
                        {data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'code', className: 'text-center'},
                        {data: 'role_name', orderable: false, className: 'text-center'},
                        {data: 'designation_name', orderable: false, className: 'text-center'},
                        {data: 'components_count', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'action', orderable: false, searchable: false, className: 'text-center'}
                    ]
                });
            });
            function deleteRow(id) {
                deleteRecord('/salary-templates/' + id, 'table', 'Do you really want to delete this salary template?');
            }
        </script>
    @endsection
</x-app-layout>
