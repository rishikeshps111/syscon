<form id="commonForm" class="row" method="POST"
    action="{{ isset($record) ? route('route-stops.update', $record->id) : route('routes.stops.store', $route->id) }}">

    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <input type="hidden" name="id" value="{{ $record->id ?? '' }}">

    <div class="col-lg-12 o-f-inp mb-2">
        <label for="location_id" class="form-label m-0">
            Place Name <span class="text-danger">*</span>
        </label>
        <select class="form-select shadow-none select2" id="location_id" name="location_id">
            <option value="">--- Select Location ---</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((int) old('location_id', $record->location_id ?? 0) === $location->id)>
                    {{ $location->name }}{{ $location->short_name ? ' (' . $location->short_name . ')' : '' }}
                </option>
            @endforeach
        </select>
        <span class="text-danger error-text location_id_error"></span>
    </div>

    <div class="col-lg-12 mt-3 modal-btns-last">
        <button type="button" class="modal-btn-1" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="modal-btn-2">{{ isset($record) ? 'Update' : 'Add' }}</button>
    </div>
</form>
