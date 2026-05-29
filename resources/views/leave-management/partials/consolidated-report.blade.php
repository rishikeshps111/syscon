<div class="row mb-3">
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedRoleFilter">User Type</label>
            <select id="consolidatedRoleFilter" class="form-select shadow-none leave-select-filter consolidated-filter">
                <option value="">---Select---</option>
                @foreach($filterRoles as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedEmployeeFilter">Employee Name</label>
            <select id="consolidatedEmployeeFilter"
                class="form-select shadow-none leave-select-filter consolidated-filter">
                <option value="">---Select User Type First---</option>
            </select>
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedLeaveTypeFilter">Leave Type</label>
            <select id="consolidatedLeaveTypeFilter"
                class="form-select shadow-none leave-select-filter consolidated-filter">
                <option value="">---Select---</option>
                @foreach($leaveTypes as $leaveType)
                    <option value="general:{{ $leaveType->id }}">{{ $leaveType->short_name ?: $leaveType->leave_name }}
                    </option>
                @endforeach
                @foreach($driverLeaveTypes as $leaveType)
                    <option value="driver:{{ $leaveType->id }}">Driver -
                        {{ $leaveType->short_name ?: $leaveType->leave_name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedFromDateFilter">From Date</label>
            <input type="date" id="consolidatedFromDateFilter" class="form-control shadow-none consolidated-filter">
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedToDateFilter">To Date</label>
            <input type="date" id="consolidatedToDateFilter" class="form-control shadow-none consolidated-filter">
        </div>
    </div>
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="consolidatedStatusFilter">Status</label>
            <select id="consolidatedStatusFilter"
                class="form-select shadow-none leave-select-filter consolidated-filter">
                <option value="">---Select---</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-6 d-flex justify-content-start align-items-end flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-danger" id="resetConsolidatedReport">Reset</button>
        <button type="button" class="btn btn-primary" id="filterConsolidatedReport">Filter</button>
        <button type="button" class="btn btn-success" id="downloadConsolidatedReport">Download Consolidated
            Report</button>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="mt-3 table-container">
            <div class="table-over">
                <table id="consolidatedLeaveTable" class="align-middle mb-0 table tble-cstm mt-3" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Leave Code</th>
                            <th class="text-center nowrap">Employee</th>
                            <th class="text-center nowrap">User Type</th>
                            <th class="text-center nowrap">Leave Type</th>
                            <th class="text-center nowrap">From</th>
                            <th class="text-center nowrap">To</th>
                            <th class="text-center nowrap">Days</th>
                            <th class="text-center nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>