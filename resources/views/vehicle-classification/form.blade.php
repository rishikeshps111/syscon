<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('vehicle-classifications.update', $record->id) : route('vehicle-classifications.store') }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="title" class="form-label m-0">
            Title <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="title" name="title"
            value="{{ old('title', $record->title ?? '') }}">
        <span class="text-danger error-text title_error"></span>
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
