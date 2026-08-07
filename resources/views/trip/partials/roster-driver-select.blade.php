<select class="form-select shadow-none roster-select roster-driver-select" name="assignments[{{ $entry->id }}][driver_profile_id]" data-placeholder="--- Select Driver ---">
    <option value=""></option>
    @foreach ($drivers as $driver)
        <option value="{{ $driver->id }}" @selected((int) $entry->driver_profile_id === $driver->id)>{{ $driver->user?->code }} - {{ $driver->user?->name }}</option>
    @endforeach
</select>
