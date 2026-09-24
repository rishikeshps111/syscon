@canany(['service-codes.edit', 'service-codes.delete', 'service-codes.status'])
    <div class="action-btns">
        @can('service-codes.status')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
        @endcan
        @can('service-codes.edit')
            <button type="button" class="btn-edit form-btn" data-id="{{ $row->id }}" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
        @endcan
        @can('service-codes.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete"><i class="fa-solid fa-trash"></i></button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany

