@php
    $record = $record ?? null;
    $selectedTrips = $selectedTrips ?? [];
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
                <div class="col-lg-12 o-f-inp mb-3">
                    <label for="tripLabel">Trip Sheet Entries <span class="text-danger">*</span></label>
                    <div id="selectedTripInputs">
                        @foreach(old('trip_sheet_entry_ids', collect($selectedTrips)->pluck('id')->all()) as $entryId)
                            <input type="hidden" name="trip_sheet_entry_ids[]" value="{{ $entryId }}">
                        @endforeach
                    </div>
                    <div class="trip-select-panel">
                        <div class="trip-select-summary">
                            <div>
                                <input type="text" class="trip-select-title" id="tripLabel"
                                    value="{{ count($selectedTrips) ? count($selectedTrips) . ' trip selected' : 'No trip selected' }}"
                                    readonly>
                                <span class="trip-select-subtitle">Select one or more trip sheet entries for this duty
                                    date.</span>
                            </div>
                            <button class="btn btn-primary" type="button" id="openTripModal">
                                <i class="fa-solid fa-route me-1"></i> Choose Trip
                            </button>
                        </div>
                        <div id="selectedTripList" class="selected-trip-list">
                            @foreach($selectedTrips as $trip)
                                <div class="selected-trip-pill" data-id="{{ $trip['id'] }}" data-side="{{ $trip['side'] }}">
                                    <span>{{ $trip['label'] }} <small>({{ $trip['side'] }})</small></span>
                                    <button type="button" class="remove-selected-trip"
                                        data-id="{{ $trip['id'] }}">x</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <span
                        class="text-danger error-text trip_sheet_entry_ids_error">@error('trip_sheet_entry_ids'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3">
                    <label for="driver_profile_id">Driver <span class="text-danger">*</span></label>
                    <select class="form-select shadow-none select2" id="driver_profile_id" name="driver_profile_id">
                        <option value="">---Select---</option>
                        @foreach($drivers as $driver)
                            @php
                                $driverSelected = old('driver_profile_id', $record->driver_profile_id ?? '') == $driver->id;
                                $driverExpired = !$driver->expiry_date || $driver->expiry_date->lt(now()->startOfDay());
                                $driverLabel = $driver->user?->name ?: '-';
                            @endphp
                            <option value="{{ $driver->id }}" data-base-label="{{ $driverLabel }}"
                                data-expired="{{ $driverExpired ? 1 : 0 }}" {{ $driverSelected ? 'selected' : '' }} {{ $driverExpired ? 'disabled' : '' }}>
                                {{ $driverLabel }}{{ $driverExpired ? ' - Licence Expired' : '' }}
                            </option>
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
                            @php
                                $vehicleSelected = old('vehicle_id', $record->vehicle_id ?? '') == $vehicle->id;
                                $vehicleLabel = $vehicle->vehicle_no ?: '-';
                            @endphp
                            <option value="{{ $vehicle->id }}" data-base-label="{{ $vehicleLabel }}" {{ $vehicleSelected ? 'selected' : '' }}>
                                {{ $vehicleLabel }}
                            </option>
                        @endforeach
                    </select>
                    <span
                        class="text-danger error-text vehicle_id_error">@error('vehicle_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-lg-4 o-f-inp mb-3 d-none">
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
                <div class="col-lg-4 o-f-inp mb-3 d-none">
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
                    <label for="reporting_time">Reporting To Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control shadow-none" id="reporting_time" name="reporting_time"
                        value="{{ old('reporting_time', isset($record) && $record->reporting_time ? substr($record->reporting_time, 0, 5) : '') }}">
                </div>
                <div class="col-lg-4 o-f-inp mb-3" id="reportingToTimeWrap" style="display:none;">
                    <label for="reporting_to_time">Reporting To Time</label>
                    <input type="time" class="form-control shadow-none" id="reporting_to_time" name="reporting_to_time"
                        value="{{ old('reporting_to_time', isset($record) && $record->reporting_to_time ? substr($record->reporting_to_time, 0, 5) : '') }}">
                </div>
                <div class="col-lg-12 o-f-inp mb-3">
                    <label for="remarks">Remarks</label>
                    <textarea class="form-control shadow-none" id="remarks" name="remarks"
                        rows="3">{{ old('remarks', $record->remarks ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-12 ">
            <div class="modal-btns-last">
                <a href="{{ route('rosters.index') }}" class="modal-btn-1">Back</a>
                <button type="submit"
                    class="modal-btn-2 roster-submit-btn">{{ isset($record) ? 'Update' : 'Submit' }}</button>
            </div>
        </div>
    </div>
</form>