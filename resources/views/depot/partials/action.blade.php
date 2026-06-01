@canany(['depots.view', 'depots.edit', 'depots.delete'])
    <div class="action-btns">
        @can('depots.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <button type="button" class="btn-edit form-btn" data-id="{{ $row->id }}" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        @endcan
        @can('depots.view')
            <a href="{{ route('depots.assignments.index', $row->id) }}" class="btn-edit" title="View Assignments">
                <i class="fa-solid fa-list"></i>
            </a>
        @endcan
        @can('depots.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
