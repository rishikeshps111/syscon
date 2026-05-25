@canany(['routes.view', 'routes.edit', 'routes.delete'])
    <div class="action-btns">
        @can('routes.edit')
            <a href="{{ route('routes.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('routes.view')
            <a href="{{ route('routes.preview', $row->id) }}" class="btn-cstm btn-nowrap" title="View">View Route Map
            </a>
        @endcan
        @can('routes.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li>
                        <a class="dropdown-item" href="{{ route('routes.stops.index', $row->id) }}">Manage Stops</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('routes.assignments.index', $row->id) }}">Manage Assignments</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('routes.schedules.index', $row->id) }}">Manage Schedules</a>
                    </li>
                    @can('routes.edit')
                        <li>
                            <a class="dropdown-item change-status-btn" href="#!" data-id="{{ $row->id }}"
                                data-status="{{ $row->status }}">Change Status</a>
                        </li>
                    @endcan
                </ul>
            </div>
        @endcan
        @can('routes.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
