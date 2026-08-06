@canany(['housekeeping-management.view', 'housekeeping-management.edit', 'housekeeping-management.delete'])
<div class="action-btns">
    @can('housekeeping-management.edit')
        <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}" data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
        <a href="{{ route('housekeeping-management.edit', $row) }}" class="btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
    @endcan
    @can('housekeeping-management.view')
        <div class="dropdown"><button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            <ul class="dropdown-menu dromenu-cs">
                <li><a class="dropdown-item" href="{{ route('housekeeping-management.show', $row) }}">View Details</a></li>
                <li><a class="dropdown-item" href="{{ route('housekeeping-management.documents.index', $row) }}">Documents</a></li>
                @can('hr-letters.generate')<li><a class="dropdown-item" href="{{ route('hr-letters.create', $row) }}">Generate Letter</a></li>@endcan
                @can('hr-letters.view')<li><a class="dropdown-item" href="{{ route('hr-letters.index', $row) }}">View Letters</a></li>@endcan
            </ul>
        </div>
    @endcan
    @can('housekeeping-management.delete')<button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')"><i class="fa-solid fa-trash"></i></button>@endcan
</div>
@else <span class="text-muted">No Access</span> @endcanany
