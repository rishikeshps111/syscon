@canany(['branch-locations.edit', 'branch-locations.delete'])
    <div class="action-btns">
        @can('branch-locations.edit')
            <button type="button" class="btn-edit form-btn" data-id="{{ $row->id }}" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class="dropdown-item change-status-btn" href="#!" data-id="{{ $row->id }}"
                            data-status="{{ $row->status }}" data-bs-toggle="modal" data-bs-target="#changeStatus">Change
                            Status</a></li>
                </ul>
            </div>
        @endcan
        @can('branch-locations.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
