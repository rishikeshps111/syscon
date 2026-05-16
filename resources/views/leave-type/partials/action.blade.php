@canany(['leave-types.view', 'leave-types.edit', 'leave-types.delete'])
    <div class="action-btns">
        {{-- @can('leave-types.view')
            <a href="{{ route('leave-types.show', $row->id) }}" class="btn-view" title="View">
                <i class="fa-solid fa-eye"></i>
            </a>
        @endcan --}}
        @can('leave-types.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <a href="{{ route('leave-types.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('leave-types.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
