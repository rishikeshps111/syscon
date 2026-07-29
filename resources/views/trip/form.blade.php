@php
    $haltTimeMinutes = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $parts = explode(':', (string) $value);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    };
@endphp

<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('trips.update', $record->id) : route('trips.store') }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">
    <input type="hidden" name="schedule_type" value="{{ $record->schedule_type ?? 'daily' }}">

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="code" class="form-label m-0">Trip Code</label>
        <input type="text" class="form-control shadow-none" id="code"
            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="service_type_id" class="form-label m-0">Service Type <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="service_type_id" name="service_type_id">
            <option value="">--- Select ---</option>
            @foreach($serviceTypes as $serviceType)
                <option value="{{ $serviceType->id }}" {{ old('service_type_id', $record->service_type_id ?? '') == $serviceType->id ? 'selected' : '' }}>
                    {{ $serviceType->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text service_type_id_error">@error('service_type_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="route_id" class="form-label m-0">Select Route <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="route_id" name="route_id">
            <option value="">--- Select ---</option>
            @foreach($routes as $route)
                <option value="{{ $route->id }}"
                    data-start="{{ $route->startPoint?->name }}"
                    data-end="{{ $route->endPoint?->name }}"
                    data-stops='@json($route->stops->pluck('name')->values())'
                    {{ old('route_id', $record->route_id ?? '') == $route->id ? 'selected' : '' }}>
                    {{ $route->route_name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text route_id_error">@error('route_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="startPointPreview" class="form-label m-0">Start Point</label>
        <input type="text" class="form-control shadow-none" id="startPointPreview" disabled>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="start_time" class="form-label m-0">Start Time <span class="text-danger">*</span></label>
        <input type="time" class="form-control shadow-none" id="start_time" name="start_time"
            value="{{ old('start_time', isset($record) && $record->start_time ? substr($record->start_time, 0, 5) : '') }}">
        <span class="text-danger error-text start_time_error">@error('start_time'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="endPointPreview" class="form-label m-0">Destination Point</label>
        <input type="text" class="form-control shadow-none" id="endPointPreview" disabled>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="end_time" class="form-label m-0">Reach Time <span class="text-danger">*</span></label>
        <input type="time" class="form-control shadow-none" id="end_time" name="end_time"
            value="{{ old('end_time', isset($record) && $record->end_time ? substr($record->end_time, 0, 5) : '') }}">
        <span class="text-danger error-text end_time_error">@error('end_time'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="halt_time" class="form-label m-0">Halt Time (in Minutes)</label>
        <input type="number" class="form-control shadow-none" id="halt_time" name="halt_time" min="0" step="1"
            value="{{ $haltTimeMinutes(old('halt_time', isset($record) ? $record->halt_time : '')) }}">
        <span class="text-danger error-text halt_time_error">@error('halt_time'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="trip_side" class="form-label m-0">Trip Side <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="trip_side" name="trip_side">
            {{-- <option value="">--- Select ---</option> --}}
            @foreach(\App\Models\Trip::TRIP_SIDES as $value => $label)
                <option value="{{ $value }}" {{ old('trip_side', $record->trip_side ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text trip_side_error">@error('trip_side'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2" id="singleDepotWrap">
        <label for="depot_id" class="form-label m-0">Depot <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="depot_id" name="depot_id">
            <option value="">--- Select ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" {{ old('depot_id', $record->depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text depot_id_error">@error('depot_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2 directional-depot-wrap">
        <label for="from_depot_id" class="form-label m-0">From Depot <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="from_depot_id" name="from_depot_id">
            <option value="">--- Select ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" {{ old('from_depot_id', $record->from_depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text from_depot_id_error">@error('from_depot_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2 directional-depot-wrap">
        <label for="to_depot_id" class="form-label m-0">To Depot <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="to_depot_id" name="to_depot_id">
            <option value="">--- Select ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" {{ old('to_depot_id', $record->to_depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text to_depot_id_error">@error('to_depot_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="schedule_km" class="form-label m-0">Schedule Km</label>
        <input type="number" class="form-control shadow-none" id="schedule_km" name="schedule_km" min="0" step="any"
            value="{{ old('schedule_km', $record->schedule_km ?? '') }}">
        <span class="text-danger error-text schedule_km_error">@error('schedule_km'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="state_id" class="form-label m-0">State <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="state_id" name="state_id">
            <option value="">--- Select ---</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}" {{ old('state_id', $record->state_id ?? '') == $state->id ? 'selected' : '' }}>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text state_id_error">@error('state_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="stopsPreview" class="form-label m-0">Stops</label>
        <div id="stopsPreview" class="d-flex flex-wrap gap-2 mt-1">
            <span class="btn btn-sm btn-light text-muted disabled">No stops selected</span>
        </div>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="from_date" class="form-label m-0">From Date</label>
        <input type="date" class="form-control shadow-none" id="from_date" name="from_date"
            value="{{ old('from_date', isset($record) && $record->from_date ? $record->from_date->format('Y-m-d') : '') }}">
        <span class="text-danger error-text from_date_error">@error('from_date'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="to_date" class="form-label m-0">To Date</label>
        <input type="date" class="form-control shadow-none" id="to_date" name="to_date"
            value="{{ old('to_date', isset($record) && $record->to_date ? $record->to_date->format('Y-m-d') : '') }}">
        <span class="text-danger error-text to_date_error">@error('to_date'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="title" class="form-label m-0">Trip Title</label>
        <input type="text" class="form-control shadow-none" id="title" name="title"
            value="{{ old('title', $record->title ?? '') }}" placeholder="TVM to EKM">
        <span class="text-danger error-text title_error">@error('title'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-2">
        <label for="status" class="form-label m-0">Status <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="status" name="status">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" {{ old('status', $record->status ?? 'Active') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text status_error">@error('status'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="notes" class="form-label m-0">Trip Notes</label>
        <textarea class="form-control shadow-none" id="notes" name="notes" rows="3">{{ old('notes', $record->notes ?? '') }}</textarea>
        <span class="text-danger error-text notes_error">@error('notes'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2" id="cancellationReasonWrap">
        <label for="cancellation_reason" class="form-label m-0">Reason for cancellation</label>
        <textarea class="form-control shadow-none" id="cancellation_reason" name="cancellation_reason" rows="3">{{ old('cancellation_reason', $record->cancellation_reason ?? '') }}</textarea>
        <span class="text-danger error-text cancellation_reason_error">@error('cancellation_reason'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        @if(!empty($pageForm))
            <a href="{{ route('trips.index') }}" class="btn btn-secondary me-2">Back</a>
        @else
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
        @endif
        <button type="submit" class="btn btn-primary trip-submit-btn">
            {{ isset($record) ? 'Update' : 'Create' }}
        </button>
    </div>
</form>
