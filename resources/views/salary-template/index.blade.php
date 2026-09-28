@section('title', 'Salary Templates')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Manage Salary Templates</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item active">Salary Templates</li></ol></nav>
        </div>
        <div class="main-table-container">
            <div class="row mb-3">
                <div class="col-lg-3">
                    <label for="roleFilter">Filter by Role</label>
                    <select id="roleFilter" class="form-select shadow-none select2">
                        <option value="">--- Select ---</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-role-name="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <label for="designationFilter">Filter by Designation</label>
                    <select id="designationFilter" class="form-select shadow-none select2">
                        <option value="">--- Select ---</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}" data-role-type="{{ $designation->role_type }}">{{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <button type="button" id="resetFilters" class="btn btn-secondary mb-1">Reset</button>
                </div>
                <div class="col-lg-12 btns-group-container">
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
                    ajax: {
                        url: "{{ route('salary-templates.index') }}",
                        data: function (data) {
                            data.role_id = $('#roleFilter').val();
                            data.designation_id = $('#designationFilter').val();
                        }
                    },
                    columns: [
                        {data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'code', className: 'text-center'},
                        {data: 'role_name', orderable: false, className: 'text-center'},
                        {data: 'designation_name', orderable: false, className: 'text-center'},
                        {data: 'components_count', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'action', orderable: false, searchable: false, className: 'text-center'}
                    ]
                });

                var table = $('#table').DataTable();
                $('.select2').select2({width: '100%', allowClear: true});
                var allDesignationOptions = $('#designationFilter option').clone();

                function filterDesignations() {
                    var roleName = $('#roleFilter option:selected').data('role-name');
                    var $designation = $('#designationFilter');
                    var selected = $designation.val();
                    var selectedType = $designation.find('option:selected').data('role-type');
                    var options = allDesignationOptions.filter(function () {
                        return !$(this).val() || (roleName && $(this).data('role-type') === roleName);
                    }).clone();

                    $designation.empty().append(options);
                    if (!roleName || (selectedType && selectedType !== roleName)) selected = '';
                    $designation.val(selected).trigger('change.select2');
                }

                filterDesignations();
                $('#roleFilter').on('change', function () { filterDesignations(); table.ajax.reload(); });
                $('#designationFilter').on('change', function () { table.ajax.reload(); });
                $('#resetFilters').on('click', function () {
                    $('#roleFilter').val('').trigger('change.select2');
                    filterDesignations();
                    table.ajax.reload();
                });
            });
            function deleteRow(id) {
                deleteRecord('/salary-templates/' + id, 'table', 'Do you really want to delete this salary template?');
            }
        </script>
    @endsection
</x-app-layout>
