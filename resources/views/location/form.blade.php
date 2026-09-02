<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('locations.update', $record->id) : route('locations.store') }}">

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
        <label for="name" class="form-label m-0">
            Location <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="short_name" class="form-label m-0">Short Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control shadow-none" id="short_name" name="short_name"
            value="{{ old('short_name', $record->short_name ?? '') }}" maxlength="50">
        <span class="text-danger error-text short_name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="pincode" class="form-label m-0">
            Pincode
        </label>
        <input type="text" class="form-control shadow-none" id="pincode" name="pincode"
            value="{{ old('pincode', $record->pincode ?? '') }}" maxlength="10">
        <span class="text-danger error-text pincode_error"></span>
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

    <div class="col-lg-6 o-f-inp mb-2">
        <input type="hidden" name="is_default" value="0">
        <div class="form-check mt-2">
            <label for="is_default" class="flex-check">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                    {{ old('is_default', $record->is_default ?? 0) == 1 ? 'checked' : '' }}>
                Is Default ?
            </label>
        </div>
        <span class="text-danger error-text is_default_error"></span>
    </div>

    <div class="col-lg-12 mt-3 modal-btns-last">
        <button type="button" class="modal-btn-1" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="modal-btn-2">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
