@canany(['controller-management.view', 'controller-management.edit', 'controller-management.delete'])
    <div class="action-btns">
        @can('controller-management.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <a href="{{ route('controller-management.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('controller-management.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class="dropdown-item" href="{{ route('controller-management.show', $row->id) }}">View Details</a></li>
                    <li><a class="dropdown-item" href="{{ route('controller-management.documents.index', $row->id) }}">Documents</a>
                    </li>
                    @can('hr-letters.generate')<li><a class="dropdown-item" href="{{ route('hr-letters.create', $row->id) }}">Generate Letter</a></li>@endcan
                    @can('hr-letters.view')<li><a class="dropdown-item" href="{{ route('hr-letters.index', $row->id) }}">View Letters</a></li>@endcan
                    <li><a class="dropdown-item" href="{{ route('controller-management.depot-assignments.index', $row->id) }}">Assign Depot</a></li>
                    @can('controller-management.edit')
                        <li><a class="dropdown-item regenerate-passcode" href="#!" data-url="{{ route('controller-management.regenerate-passcode', $row->id) }}">Regenerate Passcode</a></li>
                    @endcan
                </ul>
            </div>
        @endcan
        @can('controller-management.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
