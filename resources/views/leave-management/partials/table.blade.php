<div class="row mb-3">
    @if($type === 'all')
        <div class="col-lg-3 mb-3">
            <div class="o-f-inp">
                <label for="{{ $type }}RoleFilter">User Type</label>
                <select id="{{ $type }}RoleFilter" class="form-select shadow-none leave-select-filter leave-filter">
                    <option value="">---Select---</option>
                    @foreach($filterRoles as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="o-f-inp">
                <label for="{{ $type }}EmployeeFilter">Employee Name</label>
                <select id="{{ $type }}EmployeeFilter" class="form-select shadow-none leave-select-filter leave-filter">
                    <option value="">---Select User Type First---</option>
                </select>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="o-f-inp">
                <label for="{{ $type }}LeaveTypeFilter">Leave Type</label>
                <select id="{{ $type }}LeaveTypeFilter" class="form-select shadow-none leave-select-filter leave-filter">
                    <option value="">---Select---</option>
                    @foreach($leaveTypes as $leaveType)
                        <option value="general:{{ $leaveType->id }}">{{ $leaveType->short_name ?: $leaveType->leave_name }}</option>
                    @endforeach
                    @foreach($driverLeaveTypes as $leaveType)
                        <option value="driver:{{ $leaveType->id }}">Driver - {{ $leaveType->short_name ?: $leaveType->leave_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="o-f-inp">
                <label for="{{ $type }}FromDateFilter">From Date</label>
                <input type="date" id="{{ $type }}FromDateFilter" class="form-control shadow-none leave-filter">
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="o-f-inp">
                <label for="{{ $type }}ToDateFilter">To Date</label>
                <input type="date" id="{{ $type }}ToDateFilter" class="form-control shadow-none leave-filter">
            </div>
        </div>
    @endif
    <div class="col-lg-3 mb-3">
        <div class="o-f-inp">
            <label for="{{ $type }}StatusFilter">Status</label>
            <select id="{{ $type }}StatusFilter" class="form-select shadow-none leave-select-filter leave-filter">
                <option value="">---Select---</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-9 d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <div class="filter-btns-top">
            <button type="button" class="reset-btn reset-leave-filters" data-type="{{ $type }}">Reset</button>
        </div>
        @if($type !== 'all')
            <div class="btn-flex">
                @can('leaves.create')
                    @if($type === 'driver')
                        <a href="{{ route('leaves.driver.create') }}" class="add-btn">Add</a>
                    @else
                        <a href="{{ route('leaves.general.create') }}" class="add-btn">Add</a>
                    @endif
                @endcan
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="mt-3 table-container">
            <div class="row justify-content-end">
                <div class="col-lg-4">
                    <div class="table-search justify-content-end">
                        @can('leaves.view')
                            <button type="button" class="exp-btn export-leaves" data-type="{{ $type }}">Export Data</button>
                        @endcan
                    </div>
                </div>
            </div>
            <div class="table-over">
                <table id="{{ $type }}LeaveTable" class="align-middle mb-0 table tble-cstm mt-3 leave-table" data-type="{{ $type }}" style="width:100%;">
                    <thead>
                        <tr>
                            <th class="text-center nowrap"><input type="checkbox" class="check-all"></th>
                            <th class="text-center nowrap">SL NO</th>
                            <th class="text-center nowrap">Leave Code</th>
                            @if($type === 'driver')
                                <th class="text-center nowrap">Driver</th>
                                <th class="text-center nowrap">From</th>
                                <th class="text-center nowrap">To</th>
                                <th class="text-center nowrap">Days</th>
                                <th class="text-center nowrap">Shift</th>
                                <th class="text-center">Route</th>
                                <th class="text-center">Leave Type</th>
                            @else
                                <th class="text-center nowrap">Employee</th>
                                <th class="text-center nowrap">Role</th>
                                <th class="text-center nowrap">Leave Type</th>
                                <th class="text-center">From</th>
                                <th class="text-center">To</th>
                                <th class="text-center">Days</th>
                            @endif
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
