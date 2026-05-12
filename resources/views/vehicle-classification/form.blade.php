<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('vehicle-classifications.update', $record->id) : route('vehicle-classifications.store') }}">

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
        <label for="name" class="form-label m-0">
            Vehicle Type <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="capacity" class="form-label m-0">Capacity <span class="text-danger">*</span></label>
        <input type="number" class="form-control shadow-none" id="capacity" name="capacity"
            value="{{ old('capacity', $record->capacity ?? '') }}" min="0">
        <span class="text-danger error-text capacity_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="fuel_type" class="form-label m-0">Fuel Type <span class="text-danger">*</span></label>
        <select class="form-select shadow-none" id="fuel_type" name="fuel_type" style="height: 45px;">
            <option value="">--- Select ---</option>
            <option value="petrol" {{ old('fuel_type', $record->fuel_type ?? '') === 'petrol' ? 'selected' : '' }}>Petrol
            </option>
            <option value="diesel" {{ old('fuel_type', $record->fuel_type ?? '') === 'diesel' ? 'selected' : '' }}>Diesel
            </option>
            <option value="ev" {{ old('fuel_type', $record->fuel_type ?? '') === 'ev' ? 'selected' : '' }}>EV</option>
            <option value="hybrid" {{ old('fuel_type', $record->fuel_type ?? '') === 'hybrid' ? 'selected' : '' }}>Hybrid
            </option>
        </select>
        <span class="text-danger error-text fuel_type_error"></span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="description" class="form-label m-0">Description</label>
        <textarea class="form-control shadow-none" id="description" name="description"
            rows="3">{{ old('description', $record->description ?? '') }}</textarea>
        <span class="text-danger error-text description_error"></span>
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