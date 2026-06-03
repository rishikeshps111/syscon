@php
    $record = $record ?? null;
    $selectedEntry = $record?->tripSheetEntry ?? null;
    $selectedTripLabel = $selectedEntry
        ? trim(($selectedEntry->sheet?->code ?: '') . ' - ' . ($selectedEntry->sheet?->trip?->trip_title ?: ''))
        : '';
@endphp

<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('rosters.update', $record->id) : route('rosters.store') }}">
    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <div class="col-xl-12">
        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Basic Information</h5>
            <hr>
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="code">Roster Code<span class="text-danger">*</span></label>
                    <input type="text" class="form-control shadow-none" id="code"
                        value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="state_id">State <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="state_id" name="state_id">
                        <option value="">---Select---</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $record->state_id ?? '') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error-text state_id_error">@error('state_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="oem_id">Vendor <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="oem_id" name="oem_id">
                        <option value="">---Select---</option>
                        @foreach($oems as $oem)
                            <option value="{{ $oem->id }}" {{ old('oem_id', $record->oem_id ?? '') == $oem->id ? 'selected' : '' }}>{{ $oem->oem_name }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error-text oem_id_error">@error('oem_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="depot_id">Depot <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="depot_id" name="depot_id">
                        <option value="">---Select---</option>
                        @foreach($depots as $depot)
                            <option value="{{ $depot->id }}" data-state="{{ $depot->state_id }}" {{ old('depot_id', $record->depot_id ?? '') == $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-danger error-text depot_id_error">@error('depot_id'){{ $message }}@enderror</span>
                </div>
            </div>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Shift Details</h5>
            <hr>
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="duty_date">Duty Date<span class="text-danger">*</span></label>
                    <input type="date" class="form-control shadow-none" id="duty_date" name="duty_date"
                        value="{{ old('duty_date', isset($record) && $record->duty_date ? $record->duty_date->format('Y-m-d') : '') }}">
                    <span
                        class="text-danger error-text duty_date_error">@error('duty_date'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="shift_type">Shift Type<span class="text-danger">*</span></label>
                    <select class="form-select shadow-none" id="shift_type" name="shift_type">
                        <option value="">---Select---</option>
                        @foreach($shiftTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('shift_type', $record->shift_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span
                        class="text-danger error-text shift_type_error">@error('shift_type'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="shift_start_time">Shift Start Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control shadow-none" id="shift_start_time" name="shift_start_time"
                        value="{{ old('shift_start_time', isset($record) && $record->shift_start_time ? substr($record->shift_start_time, 0, 5) : '') }}">
                    <span
                        class="text-danger error-text shift_start_time_error">@error('shift_start_time'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="shift_end_time">Shift End Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control shadow-none" id="shift_end_time" name="shift_end_time"
                        value="{{ old('shift_end_time', isset($record) && $record->shift_end_time ? substr($record->shift_end_time, 0, 5) : '') }}">
                    <span
                        class="text-danger error-text shift_end_time_error">@error('shift_end_time'){{ $message }}@enderror</span>
                </div>
            </div>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Assignment</h5>
            <hr>
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="tripLabel">Trip <span class="text-danger">*</span></label>
                    <input type="hidden" id="trip_sheet_entry_id" name="trip_sheet_entry_id"
                        value="{{ old('trip_sheet_entry_id', $record->trip_sheet_entry_id ?? '') }}">
                    <div class="input-group">
                        <input type="text" class="form-control shadow-none" id="tripLabel"
                            value="{{ $selectedTripLabel }}" readonly>
                        <button class="btn btn-primary" type="button" id="openTripModal">Choose</button>
                    </div>
                    <span
                        class="text-danger error-text trip_sheet_entry_id_error">@error('trip_sheet_entry_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="driver_profile_id">Driver <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="driver_profile_id" name="driver_profile_id">
                        <option value="">---Select---</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_profile_id', $record->driver_profile_id ?? '') == $driver->id ? 'selected' : '' }}>{{ $driver->user?->name }}</option>
                        @endforeach
                    </select>
                    <span
                        class="text-danger error-text driver_profile_id_error">@error('driver_profile_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="vehicle_id">Vehicle <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="vehicle_id" name="vehicle_id">
                        <option value="">---Select---</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $record->vehicle_id ?? '') == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_no }}</option>
                        @endforeach
                    </select>
                    <span
                        class="text-danger error-text vehicle_id_error">@error('vehicle_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="supervisor_profile_id">Supervisor</label>
                    <select class="form-select shadow-none select2" id="supervisor_profile_id"
                        name="supervisor_profile_id">
                        <option value="">---Select---</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" {{ old('supervisor_profile_id', $record->supervisor_profile_id ?? '') == $supervisor->id ? 'selected' : '' }}>
                                {{ $supervisor->user?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="controller_profile_id">Controller</label>
                    <select class="form-select shadow-none select2" id="controller_profile_id"
                        name="controller_profile_id">
                        <option value="">---Select---</option>
                        @foreach($controllers as $controller)
                            <option value="{{ $controller->id }}" {{ old('controller_profile_id', $record->controller_profile_id ?? '') == $controller->id ? 'selected' : '' }}>
                                {{ $controller->user?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="status">Status</label>
                    <select class="form-select shadow-none" id="status" name="status">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $record->status ?? 'assigned') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="main-table-container mb-3">
            <h5 class="title-w-sec">Additional</h5>
            <hr>
            <div class="row">
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="reporting_time">Reporting Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control shadow-none" id="reporting_time" name="reporting_time"
                        value="{{ old('reporting_time', isset($record) && $record->reporting_time ? substr($record->reporting_time, 0, 5) : '') }}">
                </div>
                <div class="col-lg-12 o-f-inp mb-3">
                    <label for="remarks">Remarks</label>
                    <textarea class="form-control shadow-none" id="remarks" name="remarks"
                        rows="3">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-12 d-flex justify-content-center align-items-center">
            <div class="btn-flex">
                <a href="{{ route('rosters.index') }}" class="reset-btn">Back</a>
                <button type="submit"
                    class="submit-btn roster-submit-btn">{{ isset($record) ? 'Update' : 'Submit' }}</button>
            </div>
        </div>
    </div>
</form>