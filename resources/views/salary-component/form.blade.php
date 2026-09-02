@section('title')
    {{ isset($record) ? 'Edit Salary Component' : 'Add Salary Component' }}
@endsection
<x-app-layout>
    @php
        $selectedRoleId = collect(old('role_ids', isset($record) ? $record->assignments->pluck('role_id')->unique()->values()->all() : []))
            ->map(fn ($roleId) => (int) $roleId)
            ->first();
        $selectedDesignationId = collect(old('designation_ids', isset($record) ? $record->assignments->pluck('designation_id')->filter()->unique()->values()->all() : []))
            ->map(fn ($designationId) => (int) $designationId)
            ->first();
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
                                <label for="role_ids">User Role <span class="text-danger">*</span></label>
                                <select name="role_ids[]" id="role_ids" class="form-select shadow-none select2">
                                    <option value=""></option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" data-role-name="{{ $role->name }}"
                                            {{ $role->id === $selectedRoleId ? 'selected' : '' }}>
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
                                <label for="designation_ids">HR Designation <span class="text-danger">*</span></label>
                                <select name="designation_ids[]" id="designation_ids" class="form-select shadow-none select2">
                                    <option value=""></option>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}"
                                            {{ $designation->id === $selectedDesignationId ? 'selected' : '' }}>
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

                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="component_name">Component Name <span class="text-danger">*</span></label>
                                <input type="text" id="component_name" name="component_name" class="form-control shadow-none"
                                    value="{{ old('component_name', $record->component_name ?? '') }}">
                                @error('component_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-6 o-f-inp mb-3">
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

                            <div class="col-lg-12 mt-3 modal-btns-last">
                                <a href="{{ route('salary-components.index') }}" class="modal-btn-1">Cancel</a>
                                <button type="submit" class="modal-btn-2" data-loading-text="Loading...">
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
                    placeholder: '--- Select Role ---',
                    allowClear: true
                });

                $('#designation_ids').select2({
                    width: '100%',
                    placeholder: '--- Select HR Designation ---',
                    allowClear: true
                });

                function toggleDesignation() {
                    var hasStaffRole = $('#role_ids option:selected').data('role-name') === 'Staff';

                    if (hasStaffRole) {
                        $('.staff-designation-field').removeClass('d-none');
                        $('#designation_ids').prop('disabled', false);
                    } else {
                        $('.staff-designation-field').addClass('d-none');
                        $('#designation_ids').val(null).prop('disabled', true).trigger('change.select2');
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
