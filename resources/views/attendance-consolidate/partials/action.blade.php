<div class="action-btns">
    <a class="btn-edit" href="{{ route('attendance-consolidate.show', $row->id) }}"><i
            class="fa-solid fa-eye"></i></a>
    <a class="btn-view" href="{{ route('attendance-consolidate.download', $row->id) }}"><i
            class="fa-solid fa-download"></i></a>
    @can('attendance-management.delete')
        <button type="button" class="btn-delete delete-consolidated-attendance"
            data-url="{{ route('attendance-consolidate.destroy', $row->id) }}" title="Delete">
            <i class="fa-solid fa-trash"></i>
        </button>
    @endcan
</div>
