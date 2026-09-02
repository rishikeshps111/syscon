<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('hrms-document-types.update', $record->id) : route('hrms-document-types.store') }}">

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
            Document Type <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="category" class="form-label m-0">Category</label>
        <select class="form-select shadow-none" id="category" name="category" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" {{ old('category', $record->category ?? '') === $category ? 'selected' : '' }}>
                    {{ $category }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text category_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="applicable_for" class="form-label m-0">Applicable For</label>
        <select class="form-select shadow-none" id="applicable_for" name="applicable_for" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($applicableFor as $value => $label)
                <option value="{{ $value }}" {{ old('applicable_for', $record->applicable_for ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text applicable_for_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="allowed_file_types" class="form-label m-0">Allowed File Type</label>
        <select class="form-select shadow-none" id="allowed_file_types" name="allowed_file_types" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($allowedFileTypes as $value => $label)
                <option value="{{ $value }}" {{ old('allowed_file_types', $record->allowed_file_types ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text allowed_file_types_error"></span>
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
        <div class="form-check mt-2 ps-0 ">
            <label for="is_mandatory" class="flex-check pt-1">
                <input type="checkbox" id="is_mandatory" name="is_mandatory" value="1" {{ old('is_mandatory', $record->is_mandatory ?? 0) == 1 ? 'checked' : '' }}>
                Is Mandatory ?
            </label>
        </div>
        <span class="text-danger error-text is_mandatory_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <input type="hidden" name="is_expiry_required" value="0">
        <div class="form-check mt-2 ps-0 ">
            <label for="is_expiry_required" class="flex-check pt-1">
                <input type="checkbox" id="is_expiry_required" name="is_expiry_required" value="1" {{ old('is_expiry_required', $record->is_expiry_required ?? 0) == 1 ? 'checked' : '' }}>
                Expiry Required ?
            </label>
        </div>
        <span class="text-danger error-text is_expiry_required_error"></span>
    </div>

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="description" class="form-label m-0">Description</label>
        <textarea class="form-control shadow-none" id="description" name="description" rows="3">{{ old('description', $record->description ?? '') }}</textarea>
        <span class="text-danger error-text description_error"></span>
    </div>

    <div class="col-lg-12 mt-3 modal-btns-last">
        <button type="button" class="modal-btn-1" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="modal-btn-2">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
