<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="statusForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="statusRosterId">
            <div class="modal-header">
                <h5 class="modal-title">Update Roaster Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body o-f-inp">
                <label for="modalStatus">Status</label>
                <select id="modalStatus" name="status" class="form-select shadow-none">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Update</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="attendanceForm" class="modal-content">
            @csrf
            <input type="hidden" name="id" id="attendanceRosterId">
            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body o-f-inp">
                <label for="modalAttendance">Attendance Status</label>
                <select id="modalAttendance" name="attendance_status" class="form-select shadow-none">
                    @foreach($attendanceStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="reassignDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="reassignDriverForm" class="modal-content">
            @csrf
            <input type="hidden" id="modalDriver" name="driver_profile_id">
            <div class="modal-header">
                <h5 class="modal-title">Reassign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="o-f-inp mb-3">
                    <label for="driverCardSearch">Search Driver</label>
                    <input type="text" id="driverCardSearch" class="form-control shadow-none" placeholder="Code / name / aadhaar / license">
                </div>
                <div class="assignment-card-list" id="driverCardList">
                    @foreach($drivers as $driver)
                        @php
                            $licenseExpired = ! $driver->expiry_date || $driver->expiry_date->lt(now()->startOfDay());
                            $alreadyAssigned = in_array($driver->id, $assignedDriverIds ?? [], true);
                            $driverSearch = strtolower(trim(($driver->user?->code ?: '') . ' ' . ($driver->user?->name ?: '') . ' ' . ($driver->aadhaar_number ?: '') . ' ' . ($driver->license_number ?: '')));
                        @endphp
                        <button type="button"
                            class="assignment-card driver-card {{ $licenseExpired ? 'is-disabled' : '' }}"
                            data-id="{{ $driver->id }}"
                            data-search="{{ $driverSearch }}"
                            data-assigned="{{ $alreadyAssigned ? 1 : 0 }}"
                            data-expired="{{ $licenseExpired ? 1 : 0 }}">
                            <strong>{{ $driver->user?->code ?: '-' }} - {{ $driver->user?->name ?: '-' }}</strong>
                            <span>Aadhaar Number: {{ $driver->aadhaar_number ?: '-' }}</span>
                            <span>License Number: {{ $driver->license_number ?: '-' }}</span>
                            <span>Expiry Date: {{ $driver->expiry_date?->format('d-m-Y') ?: '-' }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Reassign</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="reassignVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="reassignVehicleForm" class="modal-content">
            @csrf
            <input type="hidden" id="modalVehicle" name="vehicle_id">
            <div class="modal-header">
                <h5 class="modal-title">Reassign Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="o-f-inp mb-3">
                    <label for="vehicleCardSearch">Search Vehicle</label>
                    <input type="text" id="vehicleCardSearch" class="form-control shadow-none" placeholder="Code / vehicle no / chassis">
                </div>
                <div class="assignment-card-list" id="vehicleCardList">
                    @foreach($vehicles as $vehicle)
                        @php
                            $alreadyAssigned = in_array($vehicle->id, $assignedVehicleIds ?? [], true);
                            $capacity = trim(($vehicle->capacity_seating !== null ? $vehicle->capacity_seating . ' Seats' : '') . ($vehicle->capacity_load !== null ? ' / ' . $vehicle->capacity_load . ' Load' : ''));
                            $vehicleSearch = strtolower(trim(($vehicle->vehicle_code ?: '') . ' ' . ($vehicle->vehicle_no ?: '') . ' ' . ($vehicle->chassis_no ?: '')));
                        @endphp
                        <button type="button"
                            class="assignment-card vehicle-card"
                            data-id="{{ $vehicle->id }}"
                            data-search="{{ $vehicleSearch }}"
                            data-assigned="{{ $alreadyAssigned ? 1 : 0 }}">
                            <strong>{{ $vehicle->vehicle_code ?: '-' }} - {{ $vehicle->vehicle_no ?: '-' }}</strong>
                            <span>Capacity: {{ $capacity ?: '-' }}</span>
                            <span>Chassis Number: {{ $vehicle->chassis_no ?: '-' }}</span>
                            <span>RC Validity: {{ $vehicle->registration_valid_upto?->format('d-m-Y') ?: '-' }}</span>
                            <span>Fitness Expiry: {{ $vehicle->fitness_expiry?->format('d-m-Y') ?: '-' }}</span>
                            <span>Pollution Expiry: {{ $vehicle->pollution_expiry?->format('d-m-Y') ?: '-' }}</span>
                            <span>Insurance Expiry: {{ $vehicle->insurance_expiry?->format('d-m-Y') ?: '-' }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Reassign</button>
            </div>
        </form>
    </div>
</div>

<style>
    .assignment-card-list {
        display: grid;
        gap: 10px;
        max-height: 430px;
        overflow-y: auto;
    }

    .assignment-card {
        background: #fff;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        display: grid;
        gap: 4px;
        padding: 12px;
        text-align: left;
        width: 100%;
    }

    .assignment-card strong {
        color: #101828;
    }

    .assignment-card span {
        color: #667085;
        font-size: 13px;
    }

    .assignment-card.is-selected {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, .12);
    }

    .assignment-card.is-disabled {
        background: #f8f9fa;
        cursor: not-allowed;
        opacity: .65;
    }
</style>
