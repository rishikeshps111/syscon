@canany(['routes.edit', 'routes.delete'])
    <div class="action-btns">
        @can('routes.edit')
            <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
                data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
            <button type="button" class="btn-edit form-btn" data-id="{{ $row->id }}" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class="dropdown-item" href="{{ route('routes.stops.index', $row->id) }}">Add Stops</a></li>
                    <li><a class="dropdown-item" href="{{ route('routes.preview', $row->id) }}">Route Preview</a></li>
                </ul>
            </div>
        @endcan
        @can('routes.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany
