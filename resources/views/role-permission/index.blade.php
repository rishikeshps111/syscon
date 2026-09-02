@section('title')
    Role Permissions
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Role Permissions</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item active">Role Permissions</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="main-table-container">

                    <div class="table-container">
                        <div class="table-over-cs">
                            <table id="rolePermissionTable" class="table align-middle mb-0 table tble-cstm mt-3"
                            style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="text-center">Sl No</th>
                                    <th class="text-center">Role</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $role->name }}</td>
                                        <td class="text-center">
                                           <div class="action-btns">
                                                @can('role-permissions.edit')
                                                <a href="{{ route('role-permissions.edit', $role->id) }}"
                                                    class="btn-cstm btn-nowrap"
                                                    title="Assign Permission">
                                                    Assign Permission
                                                </a>
                                            @else
                                                <span class="text-muted">No Access</span>
                                            @endcan
                                           </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No designation roles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('#rolePermissionTable').DataTable();
            });
        </script>
    @endsection
</x-app-layout>
