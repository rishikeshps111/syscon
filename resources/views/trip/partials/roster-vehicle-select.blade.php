<select class="form-select shadow-none roster-select roster-vehicle-select" name="assignments[{{ $entry->id }}][vehicle_id]" data-placeholder="--- Select Vehicle ---">
    <option value=""></option>
    @foreach ($vehicles as $vehicle)
        <option value="{{ $vehicle->id }}" @selected((int) $entry->vehicle_id === $vehicle->id)>{{ $vehicle->vehicle_no }}</option>
    @endforeach
</select>
