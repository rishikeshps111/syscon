<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('trip-setups.update', $record->id) : route('trip-setups.store') }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="code" class="form-label m-0">Code</label>
        <input type="text" class="form-control shadow-none" id="code"
            value="{{ $record->code ?? $generatedCode ?? '' }}" disabled>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="service_type_id" class="form-label m-0">
            Service Type <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="service_type_id" name="service_type_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach($serviceTypes as $serviceType)
                <option value="{{ $serviceType->id }}" {{ old('service_type_id', $record->service_type_id ?? '') == $serviceType->id ? 'selected' : '' }}>
                    {{ $serviceType->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text service_type_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="route_id" class="form-label m-0">
            Route <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="route_id" name="route_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach($routes as $route)
                <option value="{{ $route->id }}" {{ old('route_id', $record->route_id ?? '') == $route->id ? 'selected' : '' }}>
                    {{ $route->route_name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text route_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="schedule_type" class="form-label m-0">
            Schedule Type <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none" id="schedule_type" name="schedule_type" style="height: 45px;">
            <option value="daily" {{ old('schedule_type', $record->schedule_type ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily</option>
            <option value="weekly" {{ old('schedule_type', $record->schedule_type ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
            <option value="monthly" {{ old('schedule_type', $record->schedule_type ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
        </select>
        <span class="text-danger error-text schedule_type_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="start_time" class="form-label m-0">
            Start Time <span class="text-danger">*</span>
        </label>
        <input type="time" class="form-control shadow-none" id="start_time" name="start_time"
            value="{{ old('start_time', isset($record) && $record->start_time ? substr($record->start_time, 0, 5) : '') }}">
        <span class="text-danger error-text start_time_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="end_time" class="form-label m-0">
            End Time <span class="text-danger">*</span>
        </label>
        <input type="time" class="form-control shadow-none" id="end_time" name="end_time"
            value="{{ old('end_time', isset($record) && $record->end_time ? substr($record->end_time, 0, 5) : '') }}">
        <span class="text-danger error-text end_time_error"></span>
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
