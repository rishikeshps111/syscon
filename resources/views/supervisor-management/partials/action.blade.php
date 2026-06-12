@canany(['supervisor-management.view', 'supervisor-management.edit', 'supervisor-management.delete'])
    <div class="action-btns">
        @can('supervisor-management.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <a href="{{ route('supervisor-management.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('supervisor-management.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class="dropdown-item" href="{{ route('supervisor-management.show', $row->id) }}">View Details</a></li>
                    <li><a class="dropdown-item" href="{{ route('supervisor-management.documents.index', $row->id) }}">Documents</a>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('supervisor-management.depot-assignments.index', $row->id) }}">Assign Depot</a></li>
                    @can('supervisor-management.edit')
                        <li><a class="dropdown-item regenerate-passcode" href="#!" data-url="{{ route('supervisor-management.regenerate-passcode', $row->id) }}">Regenerate Passcode</a></li>
                    @endcan
                </ul>
            </div>
        @endcan
        @can('supervisor-management.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
