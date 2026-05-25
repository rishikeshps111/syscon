@section('title')
    {{ isset($record) ? 'Edit Driver Leave' : 'Driver Leave' }}
@endsection
<x-app-layout>
    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>Driver Leave</h3>
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
            <input type="hidden" name="leave_for" value="driver">

            <div class="row">
                <div class="col-xl-12">
                    <div class="main-table-container mb-3">
                        <div class="row">
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="user_id">Driver Name <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id" class="form-select shadow-none select2 @error('user_id') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}" @selected(old('user_id', $record->user_id ?? '') == $driver->id)>{{ trim(($driver->code ? $driver->code . ' - ' : '') . $driver->name) }}</option>
                                    @endforeach
                                </select>
                                @error('user_id')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="driver_leave_type">Leave Type <span class="text-danger">*</span></label>
                                <select id="driver_leave_type" name="driver_leave_type" class="form-select shadow-none @error('driver_leave_type') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($driverLeaveTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('driver_leave_type', $record->driver_leave_type ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('driver_leave_type')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="leave_date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="leave_date" name="leave_date" class="form-control shadow-none @error('leave_date') is-invalid @enderror"
                                    value="{{ old('leave_date', isset($record) && $record->leave_date ? $record->leave_date->format('Y-m-d') : '') }}">
                                @error('leave_date')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="shift">Shift <span class="text-danger">*</span></label>
                                <select id="shift" name="shift" class="form-select shadow-none @error('shift') is-invalid @enderror">
                                    <option value="">---Select---</option>
                                    @foreach($shifts as $value => $label)
                                        <option value="{{ $value }}" @selected(old('shift', $record->shift ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('shift')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="col-lg-4 o-f-inp mb-3">
                                <label for="assigned_vehicle_route">Assigned Vehicle / Route <span class="text-danger">*</span></label>
                                <input type="text" id="assigned_vehicle_route" name="assigned_vehicle_route"
                                    class="form-control shadow-none @error('assigned_vehicle_route') is-invalid @enderror"
                                    value="{{ old('assigned_vehicle_route', $record->assigned_vehicle_route ?? '') }}">
                                @error('assigned_vehicle_route')<span class="text-danger">{{ $message }}</span>@enderror
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
                                <label for="reason">Reason <span class="text-danger">*</span></label>
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
