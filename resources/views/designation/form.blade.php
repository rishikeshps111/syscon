<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('designations.update', $record->id) : route('designations.store') }}">

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
            Designation <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="name" name="name"
            value="{{ old('name', $record->name ?? '') }}">
        <span class="text-danger error-text name_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="department_id" class="form-label m-0">
            Department <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="department_id" name="department_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    {{ old('department_id', $record->department_id ?? '') == $department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text department_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="level_id" class="form-label m-0">
            Level <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="level_id" name="level_id" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($levels as $level)
                <option value="{{ $level->id }}"
                    {{ old('level_id', $record->level_id ?? '') == $level->id ? 'selected' : '' }}>
                    {{ $level->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text level_id_error"></span>
    </div>

    <div class="col-lg-6 o-f-inp mb-2">
        <label for="reporting_to" class="form-label m-0">
            Reporting To
        </label>
        <select class="form-select shadow-none select2" id="reporting_to" name="reporting_to" style="height: 45px;">
            <option value="">--- Select ---</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}"
                    {{ old('reporting_to', $record->reporting_to ?? '') == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text reporting_to_error"></span>
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

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="description" class="form-label m-0">Description</label>
        <textarea class="form-control shadow-none" id="description" name="description" rows="3">{{ old('description', $record->description ?? '') }}</textarea>
        <span class="text-danger error-text description_error"></span>
    </div>

    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
