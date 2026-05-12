<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('document-types.update', $record->id) : route('document-types.store') }}">

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
            Document <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="applicable_for" class="form-label m-0">Applies To</label>
        <select class="form-select shadow-none" id="applicable_for" name="applicable_for" style="height: 45px;">
            <option value="">--- Select ---</option>
            <option value="driver" {{ old('applicable_for', $record->applicable_for ?? '') === 'driver' ? 'selected' : '' }}>Driver</option>
            <option value="vehicle" {{ old('applicable_for', $record->applicable_for ?? '') === 'vehicle' ? 'selected' : '' }}>Vehicle</option>
            <option value="oem" {{ old('applicable_for', $record->applicable_for ?? '') === 'oem' ? 'selected' : '' }}>OEM</option>
            <option value="supervisor" {{ old('applicable_for', $record->applicable_for ?? '') === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
            <option value="controller" {{ old('applicable_for', $record->applicable_for ?? '') === 'controller' ? 'selected' : '' }}>Controller</option>
        </select>
        <span class="text-danger error-text applicable_for_error"></span>
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
        <input type="hidden" name="is_mandatory" value="0">
        <div class="form-check mt-2">
            <label for="is_mandatory" class="flex-check">
                <input type="checkbox" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory', $record->is_mandatory ?? 0) == 1 ? 'checked' : '' }}>
                Is Mandatory ?
            </label>
        </div>
        <span class="text-danger error-text is_mandatory_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <input type="hidden" name="is_expiry_required" value="0">
        <div class="form-check mt-2">
            <label for="is_expiry_required" class="flex-check">
                <input type="checkbox" id="is_expiry_required" name="is_expiry_required" value="1" {{ old('is_expiry_required', $record->is_expiry_required ?? 0) == 1 ? 'checked' : '' }}>
                Expiry Required ?
            </label>
        </div>
        <span class="text-danger error-text is_expiry_required_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
