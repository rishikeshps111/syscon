@canany(['salary-templates.edit', 'salary-templates.delete'])
    <div class="action-btns justify-content-center">
        @can('salary-templates.edit')
            <a href="{{ route('salary-templates.edit', $row) }}" class="btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
        @endcan
        @can('salary-templates.delete')
            <button type="button" class="btn-delete" onclick="deleteRow({{ $row->id }})" title="Delete"><i class="fa-solid fa-trash"></i></button>
        @endcan
    </div>
@endcanany
