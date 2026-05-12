<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('routes.update', $record->id) : route('routes.store') }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="code" class="form-label m-0">Route Code</label>
        <input type="text" class="form-control shadow-none" id="code"
            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="name" class="form-label m-0">
            Route Name <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="state_id" class="form-label m-0">
            State <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="state_id" name="state_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}" {{ old('state_id', $record->state_id ?? '') == $state->id ? 'selected' : '' }}>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text state_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="route_type" class="form-label m-0">
            Route Type <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none" id="route_type" name="route_type" style="height: 45px;">
            <option value="Intracity" {{ old('route_type', $record->route_type ?? 'Intracity') === 'Intracity' ? 'selected' : '' }}>Intracity</option>
            <option value="intercity" {{ old('route_type', $record->route_type ?? '') === 'intercity' ? 'selected' : '' }}>Intercity</option>
        </select>
        <span class="text-danger error-text route_type_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="start_point_id" class="form-label m-0">
            Start Point <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="start_point_id" name="start_point_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" {{ old('start_point_id', $record->start_point_id ?? '') == $depot->id ? 'selected' : '' }}>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text start_point_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="end_point_id" class="form-label m-0">
            End Point <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="end_point_id" name="end_point_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach($depots as $depot)
                <option value="{{ $depot->id }}" {{ old('end_point_id', $record->end_point_id ?? '') == $depot->id ? 'selected' : '' }}>
                    {{ $depot->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text end_point_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="distance" class="form-label m-0">Distance</label>
        <input type="number" class="form-control shadow-none" id="distance" name="distance"
            value="{{ old('distance', $record->distance ?? '') }}" min="0">
        <span class="text-danger error-text distance_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="estimated_duration" class="form-label m-0">Estimate Duration</label>
        <input type="time" class="form-control shadow-none" id="estimated_duration" name="estimated_duration"
            value="{{ old('estimated_duration', isset($record) && $record->estimated_duration ? substr($record->estimated_duration, 0, 5) : '') }}">
        <span class="text-danger error-text estimated_duration_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="is_active" class="form-label m-0">
            Status <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none" id="is_active" name="is_active" style="height: 45px;">
            <option value="1" {{ old('is_active', $record->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active', $record->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
        </select>
        <span class="text-danger error-text is_active_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
