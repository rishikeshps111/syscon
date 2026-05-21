@canany(['oems.view', 'oems.edit', 'oems.delete'])
    <div class="action-btns">
        @can('oems.edit')
            <a href="{{ route('oems.edit', $row->id) }}" class="btn-edit" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
            </a>
        @endcan
        @can('oems.view')
            <div class="dropdown">
                <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dromenu-cs">
                    <li><a class=" dropdown-item" href="{{ route('oems.show', $row->id) }}">View Details</a>
                    </li>
                    <li><a class=" dropdown-item" href="{{ route('oems.documents.index', $row->id) }}">Documents</a>
                    </li>
                    <li><a class=" dropdown-item" href="{{ route('oems.bank-details.index', $row->id) }}">Bank Details</a>
                    </li>
                    @can('oems.edit')
                        @unless ($row->is_verified)
                            <li><a class="dropdown-item verify-oem-btn" href="#!" data-url="{{ route('oems.verify', $row->id) }}">Verify
                                    OEM</a></li>
                        @endunless
                        <li><a class="dropdown-item change-status-btn" href="#!"
                                data-url="{{ route('oems.change-status', $row->id) }}" data-status="{{ $row->status }}">Change
                                Status</a></li>
                    @endcan
                </ul>
            </div>
            <a href="{{ route('oems.state-mappings.index', $row->id) }}" class="btn-cstm btn-nowrap">Mappings</a>
        @endcan
        @can('oems.delete')
            <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    </div>
@else
    <span class="text-muted">No Access</span>
@endcanany