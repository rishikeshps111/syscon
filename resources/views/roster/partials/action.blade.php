@canany(['rosters.edit', 'rosters.delete', 'rosters.view'])
    <div class="action-btns">
        @can('rosters.edit')
            <a href="{{ route('rosters.edit', $row->id) }}" class="btn-edit" title="Edit Roster">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @canany(['rosters.view', 'rosters.edit'])
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    @can('rosters.view')
                        <li><a class="dropdown-item" href="{{ route('rosters.show', $row->id) }}">View Details</a></li>
                    @endcan
                    @can('rosters.edit')
                        <li><a class="dropdown-item roster-status-btn" href="#!" data-id="{{ $row->id }}"
                                data-status="{{ $row->status }}">Update Status</a></li>
                        <li><a class="dropdown-item reassign-driver-btn" href="#!"
                                data-url="{{ route('rosters.reassign-driver', $row->id) }}"
                                data-availability-url="{{ route('rosters.availability.roster', $row->id) }}"
                                data-driver="{{ $row->driver_profile_id }}">Reassign Driver</a></li>
                        <li><a class="dropdown-item reassign-vehicle-btn" href="#!"
                                data-url="{{ route('rosters.reassign-vehicle', $row->id) }}"
                                data-availability-url="{{ route('rosters.availability.roster', $row->id) }}"
                                data-vehicle="{{ $row->vehicle_id }}">Reassign Vehicle</a></li>
                        <li><a class="dropdown-item attendance-btn" href="#!" data-id="{{ $row->id }}"
                                data-attendance="{{ $row->attendance_status }}">Mark Attendance</a></li>
                        <li><a class="dropdown-item" href="#!">Notify</a></li>
                    @endcan
                </ul>
            </div>
        @endcanany
        @can('rosters.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete Roster">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
