@canany(['hr-letter-templates.edit', 'hr-letter-templates.delete'])
<div class="action-btns">
    @can('hr-letter-templates.edit')<a href="{{ route('hr-letter-templates.edit', $row->id) }}" class="btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>@endcan
    @can('hr-letter-templates.delete')<button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete"><i class="fa-solid fa-trash"></i></button>@endcan
</div>
@else <span class="text-muted">No Access</span> @endcanany
