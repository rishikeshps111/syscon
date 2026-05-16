@section('title')
    {{ isset($record) ? 'Edit Shift Setting' : 'Add Shift Setting' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ isset($record) ? 'Edit Shift Setting' : 'Add Shift Setting' }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item active">Settings</li>
                    <li class="breadcrumb-item"><a href="{{ route('shift-settings.index') }}">Shift Settings</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form class="js-loading-form" method="POST"
            action="{{ isset($record) ? route('shift-settings.update', $record->id) : route('shift-settings.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-lg-12 mb-3">
                    <div class="main-table-container">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="number_of_shifts_per_day">Number of Shifts per Day <span class="text-danger">*</span></label>
                                <input type="text" class="form-control shadow-none" id="number_of_shifts_per_day"
                                    name="number_of_shifts_per_day" value="2" readonly>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="code">Shift Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control shadow-none" id="code"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>

                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="shift_name">Shift Name<span class="text-danger">*</span></label>
                                <select name="shift_name" id="shift_name"
                                    class="form-select shadow-none @error('shift_name') is-invalid @enderror">
                                    <option value="">--- Select ---</option>
                                    @foreach ($shiftNames as $shiftName)
                                        <option value="{{ $shiftName }}" {{ old('shift_name', $record->shift_name ?? '') === $shiftName ? 'selected' : '' }}>{{ $shiftName }}</option>
                                    @endforeach
                                </select>
                                @error('shift_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Timing</h5>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="start_time">Start Time<span class="text-danger">*</span></label>
                                <input type="time" class="form-control shadow-none @error('start_time') is-invalid @enderror"
                                    id="start_time" name="start_time" value="{{ old('start_time', isset($record) ? substr($record->start_time, 0, 5) : '') }}">
                                @error('start_time') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="end_time">End Time<span class="text-danger">*</span></label>
                                <input type="time" class="form-control shadow-none @error('end_time') is-invalid @enderror"
                                    id="end_time" name="end_time" value="{{ old('end_time', isset($record) ? substr($record->end_time, 0, 5) : '') }}">
                                @error('end_time') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="break_duration_minutes">Break Duration <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control shadow-none @error('break_duration_minutes') is-invalid @enderror"
                                    id="break_duration_minutes" name="break_duration_minutes" value="{{ old('break_duration_minutes', $record->break_duration_minutes ?? '') }}">
                                @error('break_duration_minutes') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Work Rules</h5>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="total_working_hours">Total Working Hours <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control shadow-none @error('total_working_hours') is-invalid @enderror"
                                    id="total_working_hours" name="total_working_hours" value="{{ old('total_working_hours', $record->total_working_hours ?? '') }}">
                                @error('total_working_hours') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="grace_time_minutes">Grace Time (Late Entry) <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control shadow-none @error('grace_time_minutes') is-invalid @enderror"
                                    id="grace_time_minutes" name="grace_time_minutes" value="{{ old('grace_time_minutes', $record->grace_time_minutes ?? '') }}">
                                @error('grace_time_minutes') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="minimum_working_hours">Minimum Working Hours <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" class="form-control shadow-none @error('minimum_working_hours') is-invalid @enderror"
                                    id="minimum_working_hours" name="minimum_working_hours" value="{{ old('minimum_working_hours', $record->minimum_working_hours ?? '') }}">
                                @error('minimum_working_hours') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Attendance Rules</h5>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="check_in_window_start" class="flex-check">
                                    <input type="checkbox" name="check_in_window_start" id="check_in_window_start" value="1"
                                        {{ old('check_in_window_start', $record->check_in_window_start ?? false) ? 'checked' : '' }}>Is
                                    Check-in Window Start
                                </label>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="check_in_window_end" class="flex-check">
                                    <input type="checkbox" name="check_in_window_end" id="check_in_window_end" value="1"
                                        {{ old('check_in_window_end', $record->check_in_window_end ?? false) ? 'checked' : '' }}>Is
                                    Check-in Window End
                                </label>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="check_out_flexibility" class="flex-check">
                                    <input type="checkbox" name="check_out_flexibility" id="check_out_flexibility" value="1"
                                        {{ old('check_out_flexibility', $record->check_out_flexibility ?? false) ? 'checked' : '' }}>Is
                                    Check-out Flexibility
                                </label>
                            </div>

                            <div class="col-lg-12 mb-0 mt-3">
                                <h5 class="title-w-sec">Overtime Settings &amp; Status</h5>
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="enable_overtime">Enable Overtime <span class="text-danger">*</span></label>
                                <select name="enable_overtime" id="enable_overtime"
                                    class="form-select shadow-none @error('enable_overtime') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    <option value="1" {{ old('enable_overtime', $record->enable_overtime ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('enable_overtime', $record->enable_overtime ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                </select>
                                @error('enable_overtime') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-lg-6 o-f-inp mb-3">
                                <label for="is_active">Status<span class="text-danger">*</span></label>
                                <select name="is_active" id="is_active"
                                    class="form-select shadow-none @error('is_active') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    <option value="1" {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-lg-12 d-flex justify-content-center align-items-center">
                                <div class="btn-flex">
                                    <a href="{{ route('shift-settings.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="submit-btn js-loading-submit"
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
