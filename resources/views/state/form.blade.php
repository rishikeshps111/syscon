<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('states.update', $record->id) : route('states.store') }}">

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
            State Name <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
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
                <input type="checkbox" class="" id="is_default" name="is_default" value="1" {{ old('is_default', $record->is_default ?? 0) == 1 ? 'checked' : '' }}>
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
