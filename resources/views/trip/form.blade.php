<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('trips.update', $record->id) : route('trips.store') }}">
    @csrf
    @if(isset($record)) @method('PUT') @endif

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="code" class="form-label m-0">Trip Code</label>
        <input type="text" class="form-control shadow-none" id="code"
            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="route_id" class="form-label m-0">Route <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="route_id" name="route_id">
            <option value="">--- Select Route ---</option>
            @foreach($routes as $route)
                <option value="{{ $route->id }}" data-state-id="{{ $route->state_id }}"
                    data-start-id="{{ $route->start_point_id }}" data-start="{{ $route->startPoint?->name }}"
                    data-start-short="{{ $route->startPoint?->short_name }}" data-end="{{ $route->endPoint?->name }}"
                    data-end-short="{{ $route->endPoint?->short_name }}" data-distance="{{ $route->total_distance_km }}"
                    data-title="{{ $route->route_name }}"
                    data-stops="{{ json_encode($route->stops->map(fn($stop) => ['name' => $stop->location?->name ?? $stop->name, 'short_name' => $stop->location?->short_name])->values()) }}"
                    @selected((int) old('route_id', $record->route_id ?? 0) === $route->id)>
                    {{ $route->route_name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text route_id_error">@error('route_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="state_id" class="form-label m-0">State <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="state_id" name="state_id">
            <option value="">--- Select State ---</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}" @selected((int) old('state_id', $record->state_id ?? 0) === $state->id)>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text state_id_error">@error('state_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="depot_id" class="form-label m-0">Depot <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="depot_id" name="depot_id">
            <option value="">--- Select Depot ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" @selected((int) old('depot_id', $record->depot_id ?? 0) === $depot->id)>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text depot_id_error">@error('depot_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label class="form-label m-0">Start Point</label>
        <input type="text" class="form-control shadow-none" id="startPointPreview" disabled>
    </div>
    <div class="col-lg-4 o-f-inp mb-3">
        <label class="form-label m-0">End Point</label>
        <input type="text" class="form-control shadow-none" id="endPointPreview" disabled>
    </div>

    <div class="col-lg-12 mb-3">
        <label class="form-label m-0">Route Stops</label>
        <div id="stopsPreview" class="route-stops-horizontal mt-2"></div>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label class="form-label m-0">Trip Title</label>
        <input type="text" class="form-control shadow-none" id="titlePreview"
            value="{{ old('title', $record->title ?? '') }}" readonly>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="vehicle_classification_id" class="form-label m-0">Vehicle Classification <span
                class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="vehicle_classification_id" name="vehicle_classification_id">
            <option value="">--- Select ---</option>
            @foreach($vehicleClassifications as $classification)
                <option value="{{ $classification->id }}" @selected((int) old('vehicle_classification_id', $record->vehicle_classification_id ?? 0) === $classification->id)>{{ $classification->title }}</option>
            @endforeach
        </select>
        <span
            class="text-danger error-text vehicle_classification_id_error">@error('vehicle_classification_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="trip_nature_id" class="form-label m-0">Trip Nature <span class="text-danger">*</span></label>
        <select class="form-select shadow-none select2" id="trip_nature_id" name="trip_nature_id">
            <option value="">--- Select ---</option>
            @foreach($tripNatures as $nature)
                <option value="{{ $nature->id }}" @selected((int) old('trip_nature_id', $record->trip_nature_id ?? 0) === $nature->id)>{{ $nature->title }}</option>
            @endforeach
        </select>
        <span class="text-danger error-text trip_nature_id_error">@error('trip_nature_id'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="rounds_per_trip" class="form-label m-0">Rounds per Trip <span class="text-danger">*</span></label>
        <input type="number" min="1" step="1" class="form-control shadow-none" id="rounds_per_trip"
            name="rounds_per_trip" value="{{ old('rounds_per_trip', $record->rounds_per_trip ?? 1) }}">
        <span
            class="text-danger error-text rounds_per_trip_error">@error('rounds_per_trip'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="schedule_km" class="form-label m-0">Schedule Km</label>
        <input type="number" min="0" step="0.01" class="form-control shadow-none" id="schedule_km" name="schedule_km"
            value="{{ old('schedule_km', $record->schedule_km ?? '') }}">
        <span class="text-danger error-text schedule_km_error">@error('schedule_km'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="total_trips" class="form-label m-0">Total Trips <span class="text-danger">*</span></label>
        <input type="number" min="1" step="1" class="form-control shadow-none" id="total_trips" name="total_trips"
            value="{{ old('total_trips', $record->total_trips ?? 1) }}">
        <span class="text-danger error-text total_trips_error">@error('total_trips'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-4 o-f-inp mb-3">
        <label for="from_date" class="form-label m-0">From Date</label>
        <input type="date" class="form-control shadow-none" id="from_date" name="from_date"
            value="{{ old('from_date', isset($record) && $record->from_date ? $record->from_date->format('Y-m-d') : '') }}">
        <span class="text-danger error-text from_date_error">@error('from_date'){{ $message }}@enderror</span>
    </div>
    <div class="col-lg-4 o-f-inp mb-3">
        <label for="to_date" class="form-label m-0">To Date</label>
        <input type="date" class="form-control shadow-none" id="to_date" name="to_date"
            value="{{ old('to_date', isset($record) && $record->to_date ? $record->to_date->format('Y-m-d') : '') }}">
        <span class="text-danger error-text to_date_error">@error('to_date'){{ $message }}@enderror</span>
    </div>



    <div class="col-lg-4 o-f-inp mb-3">
        <label for="status" class="form-label m-0">Status <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="status" name="status">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $record->status ?? 'Active') === $value)>{{ $label }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text status_error">@error('status'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 o-f-inp mb-3">
        <label for="notes" class="form-label m-0">Trip Notes</label>
        <textarea class="form-control shadow-none" id="notes" name="notes"
            rows="3">{{ old('notes', $record->notes ?? '') }}</textarea>
        <span class="text-danger error-text notes_error">@error('notes'){{ $message }}@enderror</span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        @if(!empty($pageForm))
            <a href="{{ route('trips.index') }}" class="btn btn-secondary me-2">Back</a>
        @else
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
        @endif
        <button type="submit"
            class="btn btn-primary trip-submit-btn">{{ isset($record) ? 'Update' : 'Create' }}</button>
    </div>
</form>

<style>
    .route-stops-horizontal {
        display: flex;
        align-items: center;
        gap: 10px;
        overflow-x: auto;
        padding: 10px 4px;
    }

    .route-stop-item {
        min-width: max-content;
        padding: 8px 12px;
        border: 1px solid #d9dee8;
        border-radius: 8px;
        background: #fff;
        text-align: center;
    }

    .route-stop-arrow {
        color: #6c757d;
        flex: 0 0 auto;
    }
</style>
