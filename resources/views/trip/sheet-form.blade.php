@section('title')
    Trip Sheet Entry
@endsection
<x-app-layout>
    @php
        $isEdit = $mode === 'edit';
        $title = match ($mode) {
            'edit' => 'Edit Trip Sheet Entry',
            'duplicate' => 'Duplicate Trip Sheet Entry',
            default => 'Add Trip Sheet Entry',
        };
        $entryId = $isEdit ? $entry?->id : null;
        $selectedDate = old('date', $entry?->sheet?->date?->format('Y-m-d'));
        $selectedStatus = old('status', $entry?->sheet?->status ?? 'pending');
        $defaultAssignment = $selectedDate
            ? $record->assignments->first(fn($assignment) => $assignment->from_date?->format('Y-m-d') <= $selectedDate && $assignment->to_date?->format('Y-m-d') >= $selectedDate)
            : null;
        $selectedDriverId = old('driver_profile_id', $entry?->driver_profile_id ?: $defaultAssignment?->driver_profile_id);
        $selectedVehicleId = old('vehicle_id', $entry?->vehicle_id ?: $defaultAssignment?->vehicle_id);
        $assignmentDefaults = $record->assignments->map(fn($assignment) => [
            'from_date' => $assignment->from_date?->format('Y-m-d'),
            'to_date' => $assignment->to_date?->format('Y-m-d'),
            'driver_profile_id' => $assignment->driver_profile_id,
            'vehicle_id' => $assignment->vehicle_id,
        ])->values();
        $timeValue = fn($field) => old($field, $entry?->{$field} ? substr($entry->{$field}, 0, 5) : '');
        $dateTimeValue = fn($field) => old($field, $entry?->{$field}?->format('Y-m-d\TH:i') ?? '');
        $verificationGroups = [
            ['is_vehicle_verified', 'vehicle_verified_by', 'vehicle_verified_at', 'Vehicle Verified'],
            ['is_driver_verified', 'driver_verified_by', 'driver_verified_at', 'Driver Verified'],
            ['is_initial_verified', 'initial_verification_by', 'initial_verification_at', 'Initial Verification'],
            ['is_final_verified', 'final_verification_by', 'final_verification_at', 'Final Verification'],
        ];
        $incidentStatusFields = [
            'energy_status' => 'Energy Status',
            'accident_status' => 'Accident Status',
            'vehicle_breakdown' => 'Vehicle Breakdown',
            'medical_emergency' => 'Medical Emergency',
            'passenger_issue' => 'Passenger Issue',
            'security_threat' => 'Security Threat',
        ];
    @endphp

    <section class="section dashboard section-top-padding">
        <div class="page-title">
            <h3>{{ $title }}</h3>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.index') }}">Trip Management</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trips.sheet', $record->id) }}">Trip Sheet</a></li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </nav>
        </div>

        <div class="main-table-container mb-3">
            <div class="row">
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip No</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->code }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip</label>
                    <input type="text" class="form-control shadow-none" value="{{ $record->trip_title }}" disabled>
                </div>
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Trip Side</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ \App\Models\Trip::TRIP_SIDES[$record->trip_side] ?? '-' }}" disabled>
                </div>
                @if($record->trip_side === 'both')
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->depot?->name ?? '-' }}" disabled>
                    </div>
                @else
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>From Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->fromDepot?->name ?? '-' }}" disabled>
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label>To Depot</label>
                        <input type="text" class="form-control shadow-none"
                            value="{{ $record->toDepot?->name ?? '-' }}" disabled>
                    </div>
                @endif
                <div class="col-lg-3 o-f-inp mb-3">
                    <label>Date Range</label>
                    <input type="text" class="form-control shadow-none"
                        value="{{ $record->from_date?->format('d M Y') }} - {{ $record->to_date?->format('d M Y') }}"
                        disabled>
                </div>
                <div class="col-lg-12 o-f-inp">
                    <label>Stops</label>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @forelse($record->route?->stops ?? [] as $stop)
                            @if(!$loop->first)
                                <span class="d-inline-flex align-items-center text-muted">
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            @endif
                            <span class="btn btn-sm btn-outline-secondary disabled">{{ $stop->name }}</span>
                        @empty
                            <span class="btn btn-sm btn-light text-muted disabled">No stops selected</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="main-table-container">
            <form method="POST" action="{{ route('trips.sheet.store', $record->id) }}" id="tripSheetForm">
                @csrf
                <input type="hidden" name="entry_id" id="entryId" value="{{ old('entry_id', $entryId) }}">

                <div class="row">
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label for="sheetDate">Date <span class="text-danger">*</span></label>
                        <input type="date" id="sheetDate" name="date" class="form-control shadow-none"
                            value="{{ $selectedDate }}" min="{{ $record->from_date?->format('Y-m-d') }}"
                            max="{{ $record->to_date?->format('Y-m-d') }}">
                        @error('date') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>

                         <div class="col-lg-3 o-f-inp mb-3">
                        <label for="departureTime">Departure Time</label>
                        <input type="time" id="departureTime" name="departure_time" class="form-control shadow-none"
                            value="{{ $timeValue('departure_time') }}">
                    </div>

                    <div class="col-lg-3 o-f-inp mb-3">
                        <label for="arrivalTime">Arrival Time</label>
                        <input type="time" id="arrivalTime" name="arrival_time" class="form-control shadow-none"
                            value="{{ $timeValue('arrival_time') }}">
                    </div>
                  
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label for="sheetStatus">Status <span class="text-danger">*</span></label>
                        <select id="sheetStatus" name="status" class="form-select shadow-none sheet-select">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label for="driverProfileId">Driver</label>
                        <select id="driverProfileId" name="driver_profile_id"
                            class="form-select shadow-none sheet-select">
                            <option value="">---Select---</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ (string) $selectedDriverId === (string) $driver->id ? 'selected' : '' }}>
                                    {{ $driver->user?->name ?? 'Driver #' . $driver->id }}
                                </option>
                            @endforeach
                        </select>
                        @error('driver_profile_id') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3">
                        <label for="vehicleId">Vehicle</label>
                        <select id="vehicleId" name="vehicle_id" class="form-select shadow-none sheet-select">
                            <option value="">---Select---</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ (string) $selectedVehicleId === (string) $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_no }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-3 o-f-inp mb-3 d-none">
                        <label for="tripOrderSequenceNo">Trip Order Sequence No</label>
                        <input type="number" min="0" id="tripOrderSequenceNo" name="trip_order_sequence_no"
                            class="form-control shadow-none"
                            value="{{ old('trip_order_sequence_no', $entry?->trip_order_sequence_no) }}">
                        @error('trip_order_sequence_no') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-12 o-f-inp mb-3">
                        <hr />
                    </div>

                      <div class="col-lg-4 o-f-inp mb-3">
                        <label for="actualStartTime">Actual Start Time</label>
                        <input type="time" id="actualStartTime" name="actual_start_time"
                            class="form-control shadow-none" value="{{ $timeValue('actual_start_time') }}">
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="actualReachTime">Actual Reach Time</label>
                        <input type="time" id="actualReachTime" name="actual_reach_time"
                            class="form-control shadow-none" value="{{ $timeValue('actual_reach_time') }}">
                    </div>

                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="startingKm">Starting Km</label>
                        <input type="number" min="0" id="startingKm" name="starting_km" class="form-control shadow-none"
                            value="{{ old('starting_km', $entry?->starting_km) }}">
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="endingKm">Ending Km</label>
                        <input type="number" min="0" id="endingKm" name="ending_km" class="form-control shadow-none"
                            value="{{ old('ending_km', $entry?->ending_km) }}">
                        @error('ending_km') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="startingElectricCharge">Starting Electric Charge (%)</label>
                        <input type="number" min="0" max="100" id="startingElectricCharge"
                            name="starting_electric_charge" class="form-control shadow-none"
                            value="{{ old('starting_electric_charge', $entry?->starting_electric_charge) }}">
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="endingElectricCharge">Ending Electric Charge (%)</label>
                        <input type="number" min="0" max="100" id="endingElectricCharge"
                            name="ending_electric_charge" class="form-control shadow-none"
                            value="{{ old('ending_electric_charge', $entry?->ending_electric_charge) }}">
                        @error('ending_electric_charge') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-12 o-f-inp mb-3">
                        <hr />
                    </div>


                    @foreach($verificationGroups as [$flag, $by, $at, $label])
                    <div class="col-lg-4 o-f-inp mb-3">
                        @php($isChecked = old($flag, $entry?->{$flag}))
                        <label>{{ $label }}</label>
                        <div class="mt-2">
                            <input class="btn-check sheet-toggle-input" type="checkbox" value="1" id="{{ $flag }}"
                                name="{{ $flag }}" autocomplete="off" {{ $isChecked ? 'checked' : '' }}>
                            <label
                                class="btn btn-sm {{ $isChecked ? 'btn-success' : 'btn-outline-secondary' }} sheet-toggle-btn"
                                for="{{ $flag }}">
                                <i class="fa-solid {{ $isChecked ? 'fa-toggle-on' : 'fa-toggle-off' }} me-1"></i>
                                <span>{{ $isChecked ? 'Yes' : 'No' }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="{{ $by }}">{{ $label }} By</label>
                        <select id="{{ $by }}" name="{{ $by }}" class="form-select shadow-none sheet-select">
                            <option value="">---Select---</option>
                            @foreach($verifiers as $verifier)
                                <option value="{{ $verifier }}" {{ old($by, $entry?->{$by}) === $verifier ? 'selected' : '' }}>{{ $verifier }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 o-f-inp mb-3">
                        <label for="{{ $at }}">{{ $label }} Timestamp</label>
                        <input type="datetime-local" id="{{ $at }}" name="{{ $at }}" class="form-control shadow-none"
                            value="{{ $dateTimeValue($at) }}">
                    </div>
                    @endforeach

                    <div class="col-lg-6 o-f-inp mb-3">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2"
                            class="form-control shadow-none">{{ old('notes', $entry?->notes) }}</textarea>
                    </div>
                    <div class="col-lg-6 o-f-inp mb-3">
                        <label for="vehicleCondition">Vehicle Condition</label>
                        <textarea id="vehicleCondition" name="vehicle_condition" rows="2"
                            class="form-control shadow-none">{{ old('vehicle_condition', $entry?->vehicle_condition) }}</textarea>
                    </div>
                      <div class="col-lg-12 o-f-inp mb-3">
                        <hr />
                    </div>

                    @foreach($incidentStatusFields as $field => $label)
                        @php($isChecked = old($field, $entry?->{$field}))
                        <div class="col-lg-4 o-f-inp mb-3">
                            <label>{{ $label }}</label>
                            <div class="mt-2">
                                <input class="btn-check sheet-toggle-input" type="checkbox" value="1"
                                    id="{{ $field }}" name="{{ $field }}" autocomplete="off"
                                    {{ $isChecked ? 'checked' : '' }}>
                                <label class="btn btn-sm {{ $isChecked ? 'btn-success' : 'btn-outline-secondary' }} sheet-toggle-btn"
                                    for="{{ $field }}">
                                    <i class="fa-solid {{ $isChecked ? 'fa-toggle-on' : 'fa-toggle-off' }} me-1"></i>
                                    <span>{{ $isChecked ? 'Yes' : 'No' }}</span>
                                </label>
                            </div>
                            @error($field) <div class="text-danger mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endforeach

                    <div class="col-lg-12 o-f-inp mb-3">
                        <label for="accidentRemarks">Accident Remarks</label>
                        <textarea id="accidentRemarks" name="accident_remarks" rows="3"
                            class="form-control shadow-none">{{ old('accident_remarks', $entry?->accident_remarks) }}</textarea>
                        @error('accident_remarks') <div class="text-danger mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>


                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('trips.sheet', $record->id) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="tripSheetSubmitBtn"
                        data-loading-text="<i class='fa-solid fa-spinner fa-spin me-1'></i> Saving">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </section>

    @section('scripts')
        <script>
            $(function () {
                var startTime = @json($record->start_time ? substr($record->start_time, 0, 5) : '');
                var endTime = @json($record->end_time ? substr($record->end_time, 0, 5) : '');
                var assignmentDefaults = @json($assignmentDefaults);

                $('.sheet-select').select2({
                    placeholder: '---Select---',
                    allowClear: true,
                    width: '100%'
                });

                $('#sheetDate').on('change', function () {
                    applyAssignmentDefaults(false);
                });

                $('#tripSheetForm').on('submit', function () {
                    var button = $('#tripSheetSubmitBtn');
                    button.prop('disabled', true).html(button.data('loading-text'));
                });

                $('.sheet-toggle-input').on('change', function () {
                    var checked = $(this).is(':checked');
                    var button = $('label[for="' + this.id + '"]');
                    button.toggleClass('btn-success', checked).toggleClass('btn-outline-secondary', !checked);
                    button.find('i').toggleClass('fa-toggle-on', checked).toggleClass('fa-toggle-off', !checked);
                    button.find('span').text(checked ? 'Yes' : 'No');
                });

                function applyAssignmentDefaults(force) {
                    var date = $('#sheetDate').val();
                    var assignment = assignmentDefaults.find(function (row) {
                        return row.from_date && row.to_date && row.from_date <= date && row.to_date >= date;
                    });

                    if (!assignment) {
                        return;
                    }

                    if (force || !$('#driverProfileId').val()) {
                        $('#driverProfileId').val(assignment.driver_profile_id || '').trigger('change');
                    }

                    if (force || !$('#vehicleId').val()) {
                        $('#vehicleId').val(assignment.vehicle_id || '').trigger('change');
                    }
                }

                if (!$('#departureTime').val()) {
                    $('#departureTime').val(startTime);
                }
                if (!$('#arrivalTime').val()) {
                    $('#arrivalTime').val(endTime);
                }
                applyAssignmentDefaults(false);
            });
        </script>
    @endsection
</x-app-layout>
