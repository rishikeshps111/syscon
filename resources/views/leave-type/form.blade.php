@section('title')
    {{ isset($record) ? 'Edit Leave Type' : 'Add Leave Type' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Leave Type' : 'Add Leave Type' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('leave-types.index') }}">Leave Types</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form class="row js-loading-form" method="POST"
            action="{{ isset($record) ? route('leave-types.update', $record->id) : route('leave-types.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="col-lg-12 mb-3">
                <div class="main-table-container">
                    <div class="row">
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="code" class="form-label m-0">Leave Type Code</label>
                            <input type="text" class="form-control shadow-none" id="code"
                                value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="leave_name" class="form-label m-0">Leave Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-none @error('leave_name') is-invalid @enderror"
                                id="leave_name" name="leave_name" value="{{ old('leave_name', $record->leave_name ?? '') }}">
                            @error('leave_name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="short_name" class="form-label m-0">Short Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-none @error('short_name') is-invalid @enderror"
                                id="short_name" name="short_name" value="{{ old('short_name', $record->short_name ?? '') }}">
                            @error('short_name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="leave_category" class="form-label m-0">Leave Category <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('leave_category') is-invalid @enderror"
                                id="leave_category" name="leave_category">
                                <option value="">--- Select ---</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" {{ old('leave_category', $record->leave_category ?? '') === $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('leave_category') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="max_leaves_per_year" class="form-label m-0">Max Leaves Per Year</label>
                            <input type="number" step="0.5" min="0" class="form-control shadow-none @error('max_leaves_per_year') is-invalid @enderror"
                                id="max_leaves_per_year" name="max_leaves_per_year" value="{{ old('max_leaves_per_year', $record->max_leaves_per_year ?? '') }}">
                            @error('max_leaves_per_year') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="max_carry_forward_limit" class="form-label m-0">Max Carry Forward Limit</label>
                            <input type="number" step="0.5" min="0" class="form-control shadow-none @error('max_carry_forward_limit') is-invalid @enderror"
                                id="max_carry_forward_limit" name="max_carry_forward_limit" value="{{ old('max_carry_forward_limit', $record->max_carry_forward_limit ?? '') }}">
                            @error('max_carry_forward_limit') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="applicable_for" class="form-label m-0">Applicable For <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('applicable_for') is-invalid @enderror"
                                id="applicable_for" name="applicable_for">
                                @foreach ($applicableFor as $value => $label)
                                    <option value="{{ $value }}" {{ old('applicable_for', $record->applicable_for ?? 'all_employees') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('applicable_for') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="gender_specific" class="form-label m-0">Gender Specific <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('gender_specific') is-invalid @enderror"
                                id="gender_specific" name="gender_specific">
                                @foreach ($genders as $value => $label)
                                    <option value="{{ $value }}" {{ old('gender_specific', $record->gender_specific ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('gender_specific') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="minimum_service_required" class="form-label m-0">Minimum Service Required</label>
                            <input type="text" class="form-control shadow-none @error('minimum_service_required') is-invalid @enderror"
                                id="minimum_service_required" name="minimum_service_required" placeholder="e.g. 6 Months"
                                value="{{ old('minimum_service_required', $record->minimum_service_required ?? '') }}">
                            @error('minimum_service_required') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="minimum_leave_days" class="form-label m-0">Minimum Leave Days <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" min="0.5" class="form-control shadow-none @error('minimum_leave_days') is-invalid @enderror"
                                id="minimum_leave_days" name="minimum_leave_days" value="{{ old('minimum_leave_days', $record->minimum_leave_days ?? 1) }}">
                            @error('minimum_leave_days') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="maximum_leave_days_per_request" class="form-label m-0">Maximum Leave Days Per Request</label>
                            <input type="number" step="0.5" min="0.5" class="form-control shadow-none @error('maximum_leave_days_per_request') is-invalid @enderror"
                                id="maximum_leave_days_per_request" name="maximum_leave_days_per_request"
                                value="{{ old('maximum_leave_days_per_request', $record->maximum_leave_days_per_request ?? '') }}">
                            @error('maximum_leave_days_per_request') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="advance_notice_days" class="form-label m-0">Advance Notice Required (Days)</label>
                            <input type="number" min="0" class="form-control shadow-none @error('advance_notice_days') is-invalid @enderror"
                                id="advance_notice_days" name="advance_notice_days" value="{{ old('advance_notice_days', $record->advance_notice_days ?? '') }}">
                            @error('advance_notice_days') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="carry_forward_allowed" class="form-label m-0">Carry Forward Allowed <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('carry_forward_allowed') is-invalid @enderror"
                                id="carry_forward_allowed" name="carry_forward_allowed">
                                <option value="1" {{ old('carry_forward_allowed', $record->carry_forward_allowed ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('carry_forward_allowed', $record->carry_forward_allowed ?? 0) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('carry_forward_allowed') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="encashment_allowed" class="form-label m-0">Encashment Allowed <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('encashment_allowed') is-invalid @enderror"
                                id="encashment_allowed" name="encashment_allowed">
                                <option value="1" {{ old('encashment_allowed', $record->encashment_allowed ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('encashment_allowed', $record->encashment_allowed ?? 0) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('encashment_allowed') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="allow_half_day" class="form-label m-0">Allow Half Day <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('allow_half_day') is-invalid @enderror"
                                id="allow_half_day" name="allow_half_day">
                                <option value="1" {{ old('allow_half_day', $record->allow_half_day ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('allow_half_day', $record->allow_half_day ?? 0) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('allow_half_day') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="requires_approval" class="form-label m-0">Requires Approval <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('requires_approval') is-invalid @enderror"
                                id="requires_approval" name="requires_approval">
                                <option value="1" {{ old('requires_approval', $record->requires_approval ?? 1) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ old('requires_approval', $record->requires_approval ?? 1) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('requires_approval') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-lg-4 o-f-inp mb-2">
                            <label for="is_active" class="form-label m-0">Status <span class="text-danger">*</span></label>
                            <select class="form-select shadow-none @error('is_active') is-invalid @enderror"
                                id="is_active" name="is_active">
                                <option value="1" {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-6 o-f-inp mb-2">
                            <label for="description" class="form-label m-0">Description</label>
                            <textarea class="form-control shadow-none @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $record->description ?? '') }}</textarea>
                            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-lg-6 o-f-inp mb-2">
                            <label for="remarks" class="form-label m-0">Remarks</label>
                            <textarea class="form-control shadow-none @error('remarks') is-invalid @enderror" id="remarks" name="remarks" rows="3">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                            @error('remarks') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-lg-12 mt-3 text-center">
                        <a href="{{ route('leave-types.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary js-loading-submit"
                            data-loading-text="Loading...">{{ isset($record) ? 'Update' : 'Add' }}</button>
                    </div>
                </div>
            </div>
        </form>
    </section>
    @section('scripts')
        <script>
            document.querySelectorAll('.js-loading-form').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var submitButton = form.querySelector('.js-loading-submit');

                    if (! submitButton || submitButton.disabled) {
                        return;
                    }

                    submitButton.dataset.originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = submitButton.dataset.loadingText || 'Loading...';
                });
            });
        </script>
    @endsection
</x-app-layout>
