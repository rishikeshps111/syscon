@section('title')
    {{ isset($record) ? 'Edit General Leave' : 'General Leave System' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>General Leave System</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">HRMS</li>
                    <li class="breadcrumb-item"><a href="{{ route('leaves.index') }}">Leave Management</a></li>
                    <li class="breadcrumb-item active">{{ isset($record) ? 'Edit' : 'Add' }}</li>
                </ol>
            </nav>
        </div>

        <form class="js-loading-form" method="POST" enctype="multipart/form-data"
            action="{{ isset($record) ? route('leaves.update', $record->id) : route('leaves.store') }}">
            @csrf
            @if(isset($record))
                @method('PUT')
            @endif
            <input type="hidden" name="leave_for" value="general">

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container mb-3">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="code">Leave Code</label>
                                <input type="text" id="code" class="form-control shadow-none"
                                    value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="user_id">Employee Name <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" class="form-select shadow-none select2 @error('user_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected(old('user_id', $record->user_id ?? '') == $employee->id)>{{ trim(($employee->code ? $employee->code . ' - ' : '') . $employee->name) }}</option>
                                    @endforeach
                                </select>
                                @error('user_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="leave_type_id">Leave Type <span class="text-danger">*</span></label>
                                <select id="leave_type_id" name="leave_type_id" class="form-select shadow-none select2 @error('leave_type_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" @selected(old('leave_type_id', $record->leave_type_id ?? '') == $leaveType->id)>{{ $leaveType->short_name ?: $leaveType->leave_name }}</option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="from_date">From Date <span class="text-danger">*</span></label>
                                <input type="date" id="from_date" name="from_date" class="form-control shadow-none @error('from_date') is-invalid @enderror"
                                    value="{{ old('from_date', isset($record) && $record->from_date ? $record->from_date->format('Y-m-d') : '') }}">
                                @error('from_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="to_date">To Date <span class="text-danger">*</span></label>
                                <input type="date" id="to_date" name="to_date" class="form-control shadow-none @error('to_date') is-invalid @enderror"
                                    value="{{ old('to_date', isset($record) && $record->to_date ? $record->to_date->format('Y-m-d') : '') }}">
                                @error('to_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="number_of_days">Number of Days <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0.5" id="number_of_days" name="number_of_days"
                                    class="form-control shadow-none @error('number_of_days') is-invalid @enderror"
                                    value="{{ old('number_of_days', $record->number_of_days ?? '') }}">
                                @error('number_of_days')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3 file-input">
                                <label for="attachment">Attachment</label>
                                <input type="file" id="attachment" name="attachment" class="form-control shadow-none @error('attachment') is-invalid @enderror">
                                @error('attachment')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select shadow-none @error('status') is-invalid @enderror">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $record->status ?? 'Pending') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-12 o-f-inp mb-3">
                                <label for="reason">Reason</label>
                                <textarea id="reason" name="reason" class="form-control shadow-none @error('reason') is-invalid @enderror">{{ old('reason', $record->reason ?? '') }}</textarea>
                                @error('reason')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 d-flex justify-content-center align-items-center">
                        <div class="btn-flex">
                            <a href="{{ route('leaves.index') }}" class="reset-btn">Cancel</a>
                            <button type="submit" class="submit-btn js-loading-submit" data-loading-text="Saving...">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
    @section('scripts')
        <script>
            $(function () {
                $('.select2').select2({ width: '100%', placeholder: '---Select---', allowClear: true });
                $('.js-loading-form').on('submit', function () {
                    $(this).find('.js-loading-submit').prop('disabled', true).html('Saving...');
                });
            });
        </script>
    @endsection
</x-app-layout>
