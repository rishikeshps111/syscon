@canany(['trips.edit', 'trips.assign', 'trips.sheet', 'trips.delete'])
    <div class="action-btns">
        @can('trips.edit')
            <a href="{{ route('trips.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @canany(['trips.assign', 'trips.sheet', 'trips.edit'])
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    @can('trips.assign')
                        {{-- <li>
                            <a class="dropdown-item" href="{{ route('trips.assignments.index', $row->id) }}">Trip Assignment</a>
                        </li> --}}
                    @endcan
                    @can('trips.sheet')
                        <li>
                            <a class="dropdown-item" href="{{ route('trips.sheet', $row->id) }}">Manage Trip Sheet</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('trips.sheet.view', $row->id) }}">View Trip Sheet</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('trips.sheet.import.form', $row->id) }}">Import Trip Sheet</a>
                        </li>
                    @endcan
                    @can('trips.edit')
                        <li>
                            <a class="dropdown-item status-btn" href="#!" data-id="{{ $row->id }}" data-status="{{ $row->status }}"
                                data-reason="{{ $row->cancellation_reason }}">Update Status</a>
                        </li>
                    @endcan
                </ul>
            </div>
        @endcanany
        @can('trips.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany