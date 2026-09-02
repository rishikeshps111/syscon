@section('title')
    {{ isset($record) ? 'Edit Holiday' : 'Add Holiday' }}
@endsection
<style>
    .shift-box-xs .row .select2-container--default .select2-selection--multiple{
        background-color:#fff !important;
            border-radius: 5px !important;
                border-color: #22222275 !important;

    }
</style>
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Holiday' : 'Add Holiday' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('holidays.index') }}">Holidays</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form class="js-loading-form" method="POST"
            action="{{ isset($record) ? route('holidays.update', $record->id) : route('holidays.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="code">Holiday Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control shadow-none" id="code"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="holiday_name">Holiday Name <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control shadow-none @error('holiday_name') is-invalid @enderror"
                                    id="holiday_name" name="holiday_name"
                                    value="{{ old('holiday_name', $record->holiday_name ?? '') }}">
                                @error('holiday_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="holiday_date">Holiday Date <span class="text-danger">*</span></label>
                                <input type="date"
                                    class="form-control shadow-none @error('holiday_date') is-invalid @enderror"
                                    id="holiday_date" name="holiday_date"
                                    value="{{ old('holiday_date', isset($record) ? $record->holiday_date->format('Y-m-d') : '') }}">
                                @error('holiday_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                              <div class="col-lg-12 ">
                                   <div class="shift-box-xs">
                                     <div class="row">
                                           <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Type &amp; Category</h5>
                            </div>

                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="holiday_type">Holiday Type<span class="text-danger">*</span></label>
                                <select name="holiday_type" id="holiday_type"
                                    class="form-select shadow-none @error('holiday_type') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($holidayTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('holiday_type', $record->holiday_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('holiday_type') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="applicable_location">Applicable Location<span
                                        class="text-danger">*</span></label>
                                <select name="applicable_location" id="applicable_location"
                                    class="form-select shadow-none @error('applicable_location') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($locationTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('applicable_location', $record->applicable_location ?? 'all') === $value ? 'selected' : '' }}>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('applicable_location') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3 d-none" id="stateField">
                                <label for="state_id">State<span class="text-danger">*</span></label>
                                <select name="state_id" id="state_id"
                                    class="form-select shadow-none select2 @error('state_id') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}" {{ old('state_id', $record->state_id ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                                @error('state_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3 d-none" id="branchField">
                                <label for="branch_location_id">Branch<span class="text-danger">*</span></label>
                                <select name="branch_location_id" id="branch_location_id"
                                    class="form-select shadow-none select2 @error('branch_location_id') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_location_id', $record->branch_location_id ?? '') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_location_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                                     </div>
                                   </div>
                                  
                              </div>
                            
                            
                            <div class="col-lg-12 mb-0 mt-3">
                                 <div class="shift-box-xs">
                                    <div class="row">
                                         <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Applicability</h5>
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="applicable_for">Applicable For<span class="text-danger">*</span></label>
                                <select name="applicable_for" id="applicable_for"
                                    class="form-select shadow-none @error('applicable_for') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($applicableForOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('applicable_for', $record->applicable_for ?? 'all_employees') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('applicable_for') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3 d-none" id="departmentField">
                                <label for="department_ids">Departments<span class="text-danger">*</span></label>
                                <select name="department_ids[]" id="department_ids"
                                    class="form-select shadow-none holiday-multi-select @error('department_ids') is-invalid @enderror"
                                    multiple>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" {{ in_array($department->id, old('department_ids', $record->department_ids ?? [])) ? 'selected' : '' }}>
                                            {{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_ids') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3 d-none" id="designationField">
                                <label for="designation_ids">Designations<span class="text-danger">*</span></label>
                                <select name="designation_ids[]" id="designation_ids"
                                    class="form-select shadow-none holiday-multi-select @error('designation_ids') is-invalid @enderror"
                                    multiple>
                                    @foreach ($designations as $designation)
                                        <option value="{{ $designation->id }}" {{ in_array($designation->id, old('designation_ids', $record->designation_ids ?? [])) ? 'selected' : '' }}>
                                            {{ $designation->name }}</option>
                                    @endforeach
                                </select>
                                @error('designation_ids') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                                    </div>
                                 </div>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="holiday_duration">Holiday Duration<span class="text-danger">*</span></label>
                                <select name="holiday_duration" id="holiday_duration"
                                    class="form-select shadow-none @error('holiday_duration') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($durationOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('holiday_duration', $record->holiday_duration ?? 'full_day') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('holiday_duration') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="is_recurring_yearly">Is Recurring Yearly<span
                                        class="text-danger">*</span></label>
                                <select name="is_recurring_yearly" id="is_recurring_yearly"
                                    class="form-select shadow-none @error('is_recurring_yearly') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    <option value="1" {{ old('is_recurring_yearly', $record->is_recurring_yearly ?? 1) == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('is_recurring_yearly', $record->is_recurring_yearly ?? 1) == 0 ? 'selected' : '' }}>No</option>
                                </select>
                                @error('is_recurring_yearly') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="is_active">Status<span class="text-danger">*</span></label>
                                <select name="is_active" id="is_active"
                                    class="form-select shadow-none @error('is_active') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    <option value="1" {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="description">Description</label>
                                <textarea name="description" id="description"
                                    class="form-control shadow-none @error('description') is-invalid @enderror">{{ old('description', $record->description ?? '') }}</textarea>
                                @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks"
                                    class="form-control shadow-none @error('remarks') is-invalid @enderror">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                                @error('remarks') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 ">
                                <div class="modal-btns-last">
                                    <a href="{{ route('holidays.index') }}" class="modal-btn-1">Cancel</a>
                                    <button type="submit" class="modal-btn-2 js-loading-submit"
                                        data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Submit' }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    @section('scripts')
        <script>
            $('#state_id').select2({
                placeholder: '--- Select ---',
                allowClear: true,
                width: '100%'
            });

            $('#branch_location_id').select2({
                placeholder: '--- Select ---',
                allowClear: true,
                width: '100%'
            });

            $('#department_ids, #designation_ids').select2({
                placeholder: '--- Select ---',
                closeOnSelect: false,
                width: '100%'
            });

            function toggleHolidayFields() {
                var locationType = document.getElementById('applicable_location').value;
                var applicableFor = document.getElementById('applicable_for').value;

                document.getElementById('stateField').classList.toggle('d-none', locationType !== 'state');
                document.getElementById('branchField').classList.toggle('d-none', locationType !== 'branch');
                document.getElementById('departmentField').classList.toggle('d-none', applicableFor !== 'specific_departments');
                document.getElementById('designationField').classList.toggle('d-none', applicableFor !== 'specific_designations');
            }

            document.getElementById('applicable_location').addEventListener('change', toggleHolidayFields);
            document.getElementById('applicable_for').addEventListener('change', toggleHolidayFields);
            toggleHolidayFields();

            document.querySelectorAll('.js-loading-form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var submitButton = form.querySelector('.js-loading-submit');

                    if (!submitButton || submitButton.disabled) {
                        return;
                    }

                    submitButton.disabled = true;
                    submitButton.innerHTML = submitButton.dataset.loadingText || 'Loading...';
                });
            });
        </script>
    @endsection
</x-app-layout>
