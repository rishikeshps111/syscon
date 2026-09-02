@section('title')
    Assign Permissions
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Assign Permissions</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('role-permissions.index') }}">Role Permissions</a></li>
                    <li class="breadcrumb-item active">{{ $role->name }}</li>
                </ol>
            </nav>
        </div>

        <form id="rolePermissionForm" action="{{ route('role-permissions.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-12">
                    <div class="main-table-container">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h5 class="mb-0">{{ $role->name }}</h5>
                        </div>

                        @error('permissions')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="accordion role-accordion" id="permissionAccordion">
                            @foreach($permissionTree as $section)
                                @php $sectionId = 'section_' . $loop->index; @endphp
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header" id="{{ $sectionId }}_heading">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#{{ $sectionId }}">
                                            {{ $section['label'] }}
                                        </button>
                                    </h2>
                                    <div id="{{ $sectionId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                        data-bs-parent="#permissionAccordion">
                                        <div class="accordion-body">
                                            @foreach($section['children'] as $child)
                                                @php $groupId = 'group_' . $loop->parent->index . '_' . $loop->index; @endphp
                                                <div class="border rounded p-3 mb-3 permission-group" data-group="{{ $groupId }}">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                        <strong>{{ $child['label'] }}</strong>
                                                        <label class="mb-0">
                                                            <input type="checkbox" class="permission-group-check" data-group="{{ $groupId }}">
                                                            Select All
                                                        </label>
                                                    </div>
                                                    <div class="row">
                                                        @foreach($child['permissions'] as $permission)
                                                            @php
                                                                $inputId = $groupId . '_' . $permission->id;
                                                                $action = str($permission->name)->afterLast('.')->headline();
                                                            @endphp
                                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                                                <label for="{{ $inputId }}" class="mb-0">
                                                                    <input type="checkbox" id="{{ $inputId }}" name="permissions[]"
                                                                        value="{{ $permission->id }}" class="permission-check"
                                                                        data-group="{{ $groupId }}"
                                                                        @checked(in_array($permission->name, $assignedPermissions, true))>
                                                                    {{ $action }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="modal-btns-last mt-4">
                            <a href="{{ route('role-permissions.index') }}" class="modal-btn-1">Back</a>
                            @can('role-permissions.edit')
                                <button type="submit" class="modal-btn-2" id="savePermissionBtn">
                                    Save Permissions
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    @section('scripts')
        <script>
            $(function () {
                function syncGroup(group) {
                    var checks = $('.permission-check[data-group="' + group + '"]');
                    var checked = checks.filter(':checked').length;
                    $('.permission-group-check[data-group="' + group + '"]').prop('checked', checks.length > 0 && checks.length === checked);
                }

                $('.permission-group-check').each(function () {
                    syncGroup($(this).data('group'));
                });

                $('.permission-group-check').on('change', function () {
                    $('.permission-check[data-group="' + $(this).data('group') + '"]').prop('checked', $(this).is(':checked'));
                });

                $('.permission-check').on('change', function () {
                    syncGroup($(this).data('group'));
                });

                $('#rolePermissionForm').on('submit', function () {
                    $('#savePermissionBtn')
                        .prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
                });
            });
        </script>
    @endsection
</x-app-layout>
