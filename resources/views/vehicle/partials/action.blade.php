@canany(['vehicles.view', 'vehicles.edit', 'vehicles.delete'])
    <div class="action-btns">
        @can('vehicles.edit')
            <a href="{{ route('vehicles.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('vehicles.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.show', $row->id) }}">View Details</a>
                    </li>
                    @can('vehicles.edit')
                        <li>
                            <a class="dropdown-item change-status-btn" href="#!"
                                data-url="{{ route('vehicles.change-status', $row->id) }}" data-status="{{ $row->status }}">Change
                                Status</a>
                        </li>
                    @endcan
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.documents.index', $row->id) }}">Documents</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.depot-assignments.index', $row->id) }}">Assign Depot</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.assignments.index', $row->id) }}">Assign Vehicle</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.maintenance-logs.index', $row->id) }}">Maintenance Logs</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('vehicles.fuel-logs.index', $row->id) }}">Fuel Logs</a>
                    </li>
                </ul>
            </div>
        @endcan
        @can('vehicles.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
