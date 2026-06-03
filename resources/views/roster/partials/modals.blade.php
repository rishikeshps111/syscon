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
    <div class="modal-dialog modal-dialog-centered">
        <form id="reassignDriverForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reassign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body o-f-inp">
                <label for="modalDriver">Driver</label>
                <select id="modalDriver" name="driver_profile_id" class="form-select shadow-none select2-modal">
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Reassign</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="reassignVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="reassignVehicleForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reassign Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body o-f-inp">
                <label for="modalVehicle">Vehicle</label>
                <select id="modalVehicle" name="vehicle_id" class="form-select shadow-none select2-modal">
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary modal-submit-btn">Reassign</button>
            </div>
        </form>
    </div>
</div>
