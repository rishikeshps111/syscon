<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('branch-locations.update', $record->id) : route('branch-locations.store') }}">

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
        <label for="state_id" class="form-label m-0">
            State <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="state_id" name="state_id" style="height: 45px;">
            <option value="">Select State</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" {{ old('state_id', $record->state_id ?? '') == $state->id ? 'selected' : '' }}>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text state_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="district_id" class="form-label m-0">
            District <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="district_id" name="district_id" style="height: 45px;"
            {{ isset($record) ? '' : 'disabled' }}>
            <option value="">{{ isset($record) ? 'Select District' : 'Select State First' }}</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" {{ old('district_id', $record->district_id ?? '') == $district->id ? 'selected' : '' }}>
                    {{ $district->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text district_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="location_id" class="form-label m-0">
            Location <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="location_id" name="location_id" style="height: 45px;"
            {{ isset($record) ? '' : 'disabled' }}>
            <option value="">{{ isset($record) ? 'Select Location' : 'Select District First' }}</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" {{ old('location_id', $record->location_id ?? '') == $location->id ? 'selected' : '' }}>
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text location_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="name" class="form-label m-0">
            Name <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="status" class="form-label m-0">
            Status <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none" id="status" name="status" style="height: 45px;">
            <option value="active" {{ old('status', $record->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $record->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ old('status', $record->status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
        <span class="text-danger error-text status_error"></span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="remarks" class="form-label m-0">Remarks</label>
        <textarea class="form-control shadow-none" id="remarks" name="remarks" rows="3">{{ old('remarks', $record->remarks ?? '') }}</textarea>
        <span class="text-danger error-text remarks_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
