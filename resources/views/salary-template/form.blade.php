@section('title', isset($record) ? 'Edit Salary Template' : 'Add Salary Template')
<style>
    .alert{
        font-size:13px;
    }
    #componentRows > * {
    height: 100%;
}

#componentRows .border.rounded.p-3.h-100 {
    position: relative !important;
    overflow: hidden !important;

    padding: 17px !important;

    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;

    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;

    transition: all 0.25s ease !important;
}


/* Left accent */
#componentRows .border.rounded.p-3.h-100::before {
    content: "" !important;

    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    bottom: 0 !important;

    width: 4px !important;

    background: #2563eb !important;

    border-radius: 12px 0 0 12px !important;
}


/* Decorative circle */
#componentRows .border.rounded.p-3.h-100::after {
    content: "" !important;

    position: absolute !important;
    width: 75px !important;
    height: 75px !important;

    top: -35px !important;
    right: -25px !important;

    background: #eff6ff !important;
    border-radius: 50% !important;

    pointer-events: none !important;
}


/* Hover */
#componentRows .border.rounded.p-3.h-100:hover {
    transform: translateY(-3px) !important;

    border-color: #bfdbfe !important;

    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.09) !important;
}


/* =========================================
   Header
   ========================================= */

#componentRows .border.rounded.p-3.h-100 .d-flex {
    position: relative !important;
    z-index: 2 !important;
}


#componentRows .border.rounded.p-3.h-100 label {
    color: #1e293b !important;

    font-size: 14px !important;
    font-weight: 700 !important;
}


/* Earning text */
#componentRows .border.rounded.p-3.h-100 label small {
    margin-left: 3px !important;

    color: #64748b !important;

    font-size: 10px !important;
    font-weight: 500 !important;

    text-transform: uppercase !important;
    letter-spacing: 0.3px !important;
}


/* =========================================
   Remove Button
   ========================================= */

#componentRows .remove-component {
    position: relative !important;
    z-index: 3 !important;

    width: 30px !important;
    height: 30px !important;

    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;

    padding: 0 !important;

    color: #dc2626 !important;
    background: #fef2f2 !important;

    border: 1px solid #fecaca !important;
    border-radius: 7px !important;

    font-size: 12px !important;

    transition: all 0.2s ease !important;
}


#componentRows .remove-component:hover {
    color: #ffffff !important;
    background: #dc2626 !important;
    border-color: #dc2626 !important;

    transform: scale(1.06) !important;

    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.18) !important;
}


/* =========================================
   Amount Input
   ========================================= */

#componentRows .form-control {
    position: relative !important;
    z-index: 2 !important;

    height: 42px !important;

    margin-top: 5px !important;

    color: #1e293b !important;
    background: #f8fafc !important;

    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;

    font-size: 14px !important;
    font-weight: 600 !important;

    transition: all 0.2s ease !important;
}


#componentRows .form-control:hover {
    background: #ffffff !important;
    border-color: #cbd5e1 !important;
}


#componentRows .form-control:focus {
    color: #1e293b !important;
    background: #ffffff !important;

    border-color: #60a5fa !important;

    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10) !important;
}


/* Number input arrows */
#componentRows input[type="number"] {
    appearance: auto !important;
}

</style>
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
                <div class="text-center modal-btns-last mt-3">
                    <a href="{{ route('salary-templates.index') }}" class=" modal-btn-1">Cancel</a>
                    <button class="modal-btn-2" type="submit" data-loading-text="Loading...">
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
