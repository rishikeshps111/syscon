<div class="action-btns">
    @can('salary-processing.edit')
        <a href="{{ route('salary-processing.edit', $row->id) }}" class="btn-nowrap btn-cstm">Manage List</a>
    @endcan
    @can('salary-processing.approve')
        @if ($row->status !== 'Approved')
            <button type="button" class="btn-nowrap btn-cstm border-0" onclick="approveSalary('{{ $row->id }}')">Approve</button>
        @endif
    @endcan
    @can('salary-processing.delete')
        <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
            <i class="fa-solid fa-trash"></i>
        </button>
    @endcan
</div>
