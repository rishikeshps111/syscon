@canany(['leaves.view', 'leaves.edit', 'leaves.delete'])
    <div class="action-btns">
        @can('leaves.view')
            <a href="{{ route('leaves.show', $row->id) }}" class="btn-view" title="View">
                <i class="fa-solid fa-eye"></i>
            </a>
        @endcan
        @can('leaves.edit')
            <a href="{{ route('leaves.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li>
                        <a class="dropdown-item change-status-btn" href="#!"
                            data-url="{{ route('leaves.change-status', $row->id) }}"
                            data-status="{{ $row->status }}"
                            data-remarks="{{ $row->remarks }}">Change Status</a>
                    </li>
                    <li><a class="dropdown-item change-status-btn" href="#!"
                            data-url="{{ route('leaves.change-status', $row->id) }}"
                            data-status="{{ $row->status }}"
                            data-remarks="{{ $row->remarks }}">Add Remarks</a></li>
                </ul>
            </div>
        @endcan
        @can('leaves.delete')
            <button type="button" class="btn-delete" onclick="deleteLeave('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
