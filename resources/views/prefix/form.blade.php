<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('prefixes.update', $record->id) : route('prefixes.store') }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    {{-- Prefix --}}
    <div class="col-lg-6 o-f-inp mb-2">
        <label for="prefix" class="form-label m-0">
            Prefix <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control shadow-none" id="prefix" name="prefix"
            value="{{ old('prefix', $record->prefix ?? '') }}">
        <span class="text-danger error-text prefix_error"></span>
    </div>

    @php
        $modules = [
            'State Module',
            'District Module',
            'Location Module',
            'Service Type Module',
            'Route Module',
            \App\Models\Trip::PREFIX_MODULE,
            'Vehicle Classification Module',
            'Document Type Module',
            'Depot Module',
        ];
    @endphp

    {{-- Module --}}
    <div class="col-lg-6 o-f-inp mb-2">
        <label for="module" class="form-label m-0">
            Module <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="module" name="module" style="height: 45px;" disabled>
            <option value="">Select Module</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" {{ old('module', $record->module ?? '') == $module ? 'selected' : '' }}>
                    {{ $module }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="module" value="{{ old('module', $record->module ?? '') }}">
        <span class="text-danger error-text module_error"></span>
    </div>

    {{-- Status --}}
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

    {{-- Submit --}}
    <div class="col-lg-12 mt-3 text-center">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary"> {{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>

</form>
