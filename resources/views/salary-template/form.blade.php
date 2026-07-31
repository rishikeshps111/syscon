@section('title', isset($record) ? 'Edit Salary Template' : 'Add Salary Template')
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Salary Template' : 'Add Salary Template' }}</h3>
            <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li><li class="breadcrumb-item"><a href="{{ route('salary-templates.index') }}">Salary Templates</a></li><li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li></ol></nav>
        </div>
        <div class="main-table-container">
            <form method="POST" action="{{ isset($record) ? route('salary-templates.update', $record) : route('salary-templates.store') }}" id="salaryTemplateForm">
                @csrf
                @isset($record) @method('PUT') @endisset
                <div class="row">
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label>Template Code</label>
                        <input class="form-control shadow-none" value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="role_id">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="role_id" class="form-select shadow-none select2">
                            <option value="">--- Select ---</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" @selected(old('role_id', $record->role_id ?? '') == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3 d-none" id="designationWrapper">
                        <label for="designation_id">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" id="designation_id" class="form-select shadow-none select2">
                            <option value="">--- Select ---</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" @selected(old('designation_id', $record->designation_id ?? '') == $designation->id)>{{ $designation->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <h5 class="title-w-sec">Salary Components</h5>
                    @error('components')<div class="text-danger mb-2">{{ $message }}</div>@enderror
                    <div class="row" id="componentRows">
                        <div class="col-12"><div class="alert alert-info">Select a role{{ isset($record) && $record->role?->name === 'Staff' ? ' and designation' : '' }} to load salary components.</div></div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('salary-templates.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button class="btn btn-primary" type="submit" data-loading-text="Loading...">
                        {{ isset($record) ? 'Update' : 'Submit' }}
                    </button>
                </div>
            </form>
        </div>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({width: '100%', allowClear: true});
                var templateId = @json($record->id ?? null);

                function roleName() {
                    return $('#role_id option:selected').data('role-name') || '';
                }
                function toggleDesignation() {
                    var staff = roleName() === 'Staff';
                    $('#designationWrapper').toggleClass('d-none', !staff);
                    $('#designation_id').prop('disabled', !staff);
                    if (!staff) $('#designation_id').val('').trigger('change.select2');
                    loadComponents();
                }
                function loadComponents() {
                    var roleId = $('#role_id').val();
                    var designationId = $('#designation_id').val();
                    if (!roleId || (roleName() === 'Staff' && !designationId)) {
                        $('#componentRows').html('<div class="col-12"><div class="alert alert-info">Select the role and required designation to load salary components.</div></div>');
                        return;
                    }
                    $('#componentRows').html('<div class="col-12"><div class="alert alert-info">Loading salary components...</div></div>');
                    $.get(@json(route('salary-templates.components')), {
                        role_id: roleId,
                        designation_id: designationId,
                        template_id: templateId
                    }).done(function (components) {
                        if (!components.length) {
                            $('#componentRows').html('<div class="col-12"><div class="alert alert-warning">No salary components are assigned to this role and designation.</div></div>');
                            return;
                        }
                        var html = components.filter(function (component) {
                            return !templateId || component.selected;
                        }).map(componentRow).join('');
                        $('#componentRows').html(html || '<div class="col-12"><div class="alert alert-warning">All loaded components have been removed.</div></div>');
                    }).fail(function () {
                        $('#componentRows').html('<div class="col-12"><div class="alert alert-danger">Unable to load salary components.</div></div>');
                    });
                }
                function componentRow(component) {
                    return `<div class="col-lg-4 mb-3 salary-template-component">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="mb-0">${component.name} <small class="text-muted">(${component.type})</small></label>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-component" title="Remove"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                            <input type="number" min="0" step="0.01" class="form-control shadow-none" name="components[${component.id}]" value="${component.amount}" required>
                        </div>
                    </div>`;
                }
                $(document).on('click', '.remove-component', function () { $(this).closest('.salary-template-component').remove(); });
                $('#role_id').on('change', function () { templateId = null; toggleDesignation(); });
                $('#designation_id').on('change', function () {
                    templateId = null;
                    if (roleName() === 'Staff') loadComponents();
                });
                $('#salaryTemplateForm').on('submit', function () {
                    var button = $(this).find('button[type="submit"]');
                    button.prop('disabled', true).text(button.data('loading-text'));
                });
                toggleDesignation();
            });
        </script>
    @endsection
</x-app-layout>
