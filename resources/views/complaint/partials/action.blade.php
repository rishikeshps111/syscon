@canany(['complaints.view', 'complaints.edit', 'complaints.delete'])
    <div class="action-btns">
        @can('complaints.edit')
            <a href="{{ route('complaints.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('complaints.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class="dropdown-item" href="{{ route('complaints.show', $row->id) }}">View Complaint</a></li>
                    @can('complaints.edit')
                        <li><a class="dropdown-item change-status-btn" href="#!" data-url="{{ route('complaints.change-status', $row->id) }}" data-status="{{ $row->status }}">Change Status</a></li>
                        <li><a class="dropdown-item assign-action-btn" href="#!" data-url="{{ route('complaints.assign-action', $row->id) }}" data-assigned-to="{{ $row->assigned_to }}" data-action-taken="{{ $row->action_taken }}" data-action-date="{{ $row->action_date?->format('Y-m-d') }}">Assign for Action</a></li>
                    @endcan
                </ul>
            </div>
        @endcan
        @can('complaints.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
