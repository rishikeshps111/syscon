<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('route-stops.update', $record->id) : route('routes.stops.store', $route->id) }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="name" class="form-label m-0">
            Place Name <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="expected_reach_time" class="form-label m-0">Expected Reach Time</label>
        <input type="time" class="form-control shadow-none" id="expected_reach_time" name="expected_reach_time"
            value="{{ old('expected_reach_time', isset($record) && $record->expected_reach_time ? substr($record->expected_reach_time, 0, 5) : '') }}">
        <span class="text-danger error-text expected_reach_time_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="position" class="form-label m-0">
            Position <span class="text-danger">*</span>
        </label>
        <input type="number" class="form-control shadow-none" id="position" name="position"
            value="{{ old('position', $record->position ?? $nextPosition ?? '') }}" min="1">
        <span class="text-danger error-text position_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
