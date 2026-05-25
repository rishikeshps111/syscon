<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('route-assignments.update', $record->id) : route('routes.assignments.store', $route->id) }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="vehicle_id" class="form-label m-0">Vehicle <span class="text-danger">*</span></label>
        <select id="vehicle_id" name="vehicle_id" class="form-select shadow-none select2">
            <option value="">---Select---</option>
            @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $record->vehicle_id ?? '') == $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text vehicle_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="driver_id" class="form-label m-0">Driver <span class="text-danger">*</span></label>
        <select id="driver_id" name="driver_id" class="form-select shadow-none select2">
            <option value="">---Select---</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" @selected(old('driver_id', $record->driver_id ?? '') == $driver->id)>{{ trim(($driver->code ? $driver->code . ' - ' : '') . $driver->name) }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text driver_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="trip_id" class="form-label m-0">Trip</label>
        <select id="trip_id" class="form-select shadow-none" disabled>
            <option value="">---Select---</option>
        </select>
        <span class="text-danger error-text trip_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="shift_type" class="form-label m-0">Shift Type <span class="text-danger">*</span></label>
        <select id="shift_type" name="shift_type" class="form-select shadow-none">
            <option value="">---Select---</option>
            @foreach($shiftTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('shift_type', $record->shift_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text shift_type_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="start_time" class="form-label m-0">Start Time <span class="text-danger">*</span></label>
        <input type="time" id="start_time" name="start_time" class="form-control shadow-none"
            value="{{ old('start_time', isset($record) && $record->start_time ? substr($record->start_time, 0, 5) : '') }}">
        <span class="text-danger error-text start_time_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="end_time" class="form-label m-0">End Time <span class="text-danger">*</span></label>
        <input type="time" id="end_time" name="end_time" class="form-control shadow-none"
            value="{{ old('end_time', isset($record) && $record->end_time ? substr($record->end_time, 0, 5) : '') }}">
        <span class="text-danger error-text end_time_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="effective_from" class="form-label m-0">Effective From <span class="text-danger">*</span></label>
        <input type="date" id="effective_from" name="effective_from" class="form-control shadow-none"
            value="{{ old('effective_from', isset($record) && $record->effective_from ? $record->effective_from->format('Y-m-d') : '') }}">
        <span class="text-danger error-text effective_from_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="effective_to" class="form-label m-0">Effective To</label>
        <input type="date" id="effective_to" name="effective_to" class="form-control shadow-none"
            value="{{ old('effective_to', isset($record) && $record->effective_to ? $record->effective_to->format('Y-m-d') : '') }}">
        <span class="text-danger error-text effective_to_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="status" class="form-label m-0">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select shadow-none">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $record->status ?? 'Active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text status_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
