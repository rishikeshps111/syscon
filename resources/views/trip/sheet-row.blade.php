<tr>
    <td class="text-center"><span class="sheet-sl">{{ $index + 1 }}</span></td>
    <td><input type="date" class="form-control shadow-none" name="entries[{{ $index }}][trip_date]" value="{{ $entry?->trip_date?->format('Y-m-d') }}"></td>
    <td><input type="time" class="form-control shadow-none departure-time" name="entries[{{ $index }}][departure_time]" value="{{ $entry?->departure_time ? substr($entry->departure_time, 0, 5) : '' }}"></td>
    <td><input type="time" class="form-control shadow-none arrival-time" name="entries[{{ $index }}][arrival_time]" value="{{ $entry?->arrival_time ? substr($entry->arrival_time, 0, 5) : '' }}"></td>
    <td><input type="time" class="form-control shadow-none actual-start-time" name="entries[{{ $index }}][actual_start_time]" value="{{ $entry?->actual_start_time ? substr($entry->actual_start_time, 0, 5) : ($entry?->departure_time ? substr($entry->departure_time, 0, 5) : '') }}" readonly></td>
    <td><input type="time" class="form-control shadow-none actual-reach-time" name="entries[{{ $index }}][actual_reach_time]" value="{{ $entry?->actual_reach_time ? substr($entry->actual_reach_time, 0, 5) : ($entry?->arrival_time ? substr($entry->arrival_time, 0, 5) : '') }}" readonly></td>
    <td>
        <select class="form-select shadow-none sheet-select" name="entries[{{ $index }}][verified_by]">
            <option value="">---Select---</option>
            @foreach($controllers as $controller)
                @php($controllerName = $controller->user?->name)
                @if($controllerName)
                    <option value="{{ $controllerName }}" {{ $entry?->verified_by === $controllerName ? 'selected' : '' }}>{{ $controllerName }}</option>
                @endif
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select shadow-none sheet-select" name="entries[{{ $index }}][approved_by]">
            <option value="">---Select---</option>
            @foreach($supervisors as $supervisor)
                @php($supervisorName = $supervisor->user?->name)
                @if($supervisorName)
                    <option value="{{ $supervisorName }}" {{ $entry?->approved_by === $supervisorName ? 'selected' : '' }}>{{ $supervisorName }}</option>
                @endif
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select shadow-none sheet-select" name="entries[{{ $index }}][shift]">
            <option value="">---Select---</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->shift_name }}" {{ $entry?->shift === $shift->shift_name ? 'selected' : '' }}>
                    {{ $shift->shift_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select shadow-none sheet-select" name="entries[{{ $index }}][driver_profile_id]">
            <option value="">---Select---</option>
            @foreach($drivers as $driver)
                <option value="{{ $driver->id }}" {{ $entry?->driver_profile_id == $driver->id ? 'selected' : '' }}>{{ $driver->user?->name ?? 'Driver #' . $driver->id }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select class="form-select shadow-none sheet-select" name="entries[{{ $index }}][vehicle_id]">
            <option value="">---Select---</option>
            @foreach($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" {{ $entry?->vehicle_id == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->vehicle_no }}</option>
            @endforeach
        </select>
    </td>
    <td style="min-width: 260px;">
        <textarea class="form-control shadow-none" name="entries[{{ $index }}][notes]" rows="2">{{ $entry?->notes }}</textarea>
    </td>
    <td class="text-center">
        <div class="multi-btns">
            <button type="button" class="add-sheet-row">+</button>
            <button type="button" class="remove-sheet-row" style="background-color: #b23939;">-</button>
            <button type="button" class="copy-sheet-row" style="background-color: #14489b;"><i class="fa-regular fa-copy"></i></button>
        </div>
    </td>
</tr>
