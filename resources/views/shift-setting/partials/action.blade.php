@canany(['shift-settings.view', 'shift-settings.edit', 'shift-settings.delete'])
    <div class="action-btns">
        @can('shift-settings.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <a href="{{ route('shift-settings.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('shift-settings.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
