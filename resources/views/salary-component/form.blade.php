@section('title')
    {{ isset($record) ? 'Edit Salary Component' : 'Add Salary Component' }}
@endsection
<x-app-layout>
    @php
        $selectedRoleIds = collect(old('role_ids', isset($record) ? $record->assignments->pluck('role_id')->unique()->values()->all() : []))
            ->map(fn ($roleId) => (int) $roleId)
            ->all();
        $selectedDesignationIds = collect(old('designation_ids', isset($record) ? $record->assignments->pluck('designation_id')->filter()->unique()->values()->all() : []))
            ->map(fn ($designationId) => (int) $designationId)
            ->all();
    @endphp
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Salary Component' : 'Add Salary Component' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Payroll</li>
                    <li class="breadcrumb-item"><a href="{{ route('salary-components.index') }}">Salary Components</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <form id="salaryComponentForm" method="POST"
                        action="{{ isset($record) ? route('salary-components.update', $record->id) : route('salary-components.store') }}">
                        @csrf
                        @if (isset($record))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="code">Component Code <span class="text-danger">*</span></label>
                                <input type="text" id="code" class="form-control shadow-none"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>

                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="role_ids">User Roles <span class="text-danger">*</span></label>
                                <select name="role_ids[]" id="role_ids" class="form-select shadow-none select2" multiple>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" data-role-name="{{ $role->name }}"
                                            {{ in_array($role->id, $selectedRoleIds, true) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_ids')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                @error('role_ids.*')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-12 o-f-inp mb-3 staff-designation-field d-none">
                                <label for="designation_ids">Staff Designations <span class="text-danger">*</span></label>
                                <select name="designation_ids[]" id="designation_ids" class="form-select shadow-none select2" multiple>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}"
                                            {{ in_array($designation->id, $selectedDesignationIds, true) ? 'selected' : '' }}>
                                            {{ $designation->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('designation_ids')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                @error('designation_ids.*')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="component_name">Component Name <span class="text-danger">*</span></label>
                                <input type="text" id="component_name" name="component_name" class="form-control shadow-none"
                                    value="{{ old('component_name', $record->component_name ?? '') }}">
                                @error('component_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="type">Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="earning" {{ old('type', $record->type ?? '') === 'earning' ? 'selected' : '' }}>Earning</option>
                                    <option value="deduction" {{ old('type', $record->type ?? '') === 'deduction' ? 'selected' : '' }}>Deduction</option>
                                </select>
                                @error('type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="is_applicable">Applicable <span class="text-danger">*</span></label>
                                <select name="is_applicable" id="is_applicable" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1" {{ (string) old('is_applicable', $record->is_applicable ?? '1') === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ (string) old('is_applicable', $record->is_applicable ?? '1') === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                @error('is_applicable')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="calculation_type">Calculation Type <span class="text-danger">*</span></label>
                                <select name="calculation_type" id="calculation_type" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    @foreach (['fixed' => 'Fixed', 'percentage' => 'Percentage', 'per_shift' => 'Per Shift', 'per_trip' => 'Per Trip', 'formula' => 'Formula'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('calculation_type', $record->calculation_type ?? '') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('calculation_type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="default_value">Default Value <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" id="default_value" name="default_value"
                                    class="form-control shadow-none" placeholder="Amount or %"
                                    value="{{ old('default_value', $record->default_value ?? '') }}">
                                @error('default_value')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="is_editable_in_payroll">Editable in Payroll <span class="text-danger">*</span></label>
                                <select name="is_editable_in_payroll" id="is_editable_in_payroll" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1" {{ (string) old('is_editable_in_payroll', $record->is_editable_in_payroll ?? '1') === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ (string) old('is_editable_in_payroll', $record->is_editable_in_payroll ?? '1') === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                @error('is_editable_in_payroll')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="is_mandatory">Mandatory <span class="text-danger">*</span></label>
                                <select name="is_mandatory" id="is_mandatory" class="form-select shadow-none">
                                    <option value="">--- Select ---</option>
                                    <option value="1" {{ (string) old('is_mandatory', $record->is_mandatory ?? '0') === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ (string) old('is_mandatory', $record->is_mandatory ?? '0') === '0' ? 'selected' : '' }}>No</option>
                                </select>
                                @error('is_mandatory')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-12 mt-3 text-center">
                                <a href="{{ route('salary-components.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary" data-loading-text="Loading...">
                                    {{ isset($record) ? 'Update' : 'Submit' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                $('#role_ids').select2({
                    width: '100%',
                    placeholder: '--- Select Roles ---',
                    allowClear: true
                });

                $('#designation_ids').select2({
                    width: '100%',
                    placeholder: '--- Select Staff Designations ---',
                    allowClear: true
                });

                function toggleDesignation() {
                    var hasStaffRole = $('#role_ids option:selected').toArray().some(function (option) {
                        return $(option).data('role-name') === 'Staff';
                    });

                    if (hasStaffRole) {
                        $('.staff-designation-field').removeClass('d-none');
                    } else {
                        $('.staff-designation-field').addClass('d-none');
                        $('#designation_ids').val(null).trigger('change.select2');
                    }
                }

                toggleDesignation();
                $('#role_ids').on('change', toggleDesignation);

                $('#salaryComponentForm').on('submit', function () {
                    var submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', true).html(submitBtn.data('loading-text'));
                });
            });
        </script>
    @endsection
</x-app-layout>
