@can('oems.delete')
    <div class="action-btns">
        <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
            <i class="fa-solid fa-trash"></i>
        </button>
    </div>
@else
    <span class="text-muted">No Access</span>
@endcan
