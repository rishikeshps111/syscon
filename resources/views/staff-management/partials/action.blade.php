@php
    $module = match ($role) {
        'Housekeeping' => 'housekeeping-management',
        'Controller' => 'controller-management',
        'Supervisor' => 'supervisor-management',
        default => 'staff-management',
    };
    $documentRoute = match ($role) {
        'Housekeeping' => 'housekeeping-management.documents.index',
        'Controller' => 'controller-management.documents.index',
        'Supervisor' => 'supervisor-management.documents.index',
        default => 'staff-management.documents.index',
    };
    $canView = auth()->user()->can($module . '.view');
    $canEdit = auth()->user()->can($module . '.edit');
    $canDelete = auth()->user()->can($module . '.delete');
@endphp

<div class="action-btns">
    @if($canEdit)
        <input type="checkbox" class="toggle-btn toggleStatus" data-id="{{ $row->id }}"
            data-status="{{ $row->is_active ? 1 : 0 }}" {{ $row->is_active ? 'checked' : '' }}>
        <a href="{{ route('staff-management.edit', $row) }}" class="btn-edit" title="Edit">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
    @endif

    @if($canView)
        <div class="dropdown">
            <button class="dropdown-toggle tgle-cs-btns" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu dromenu-cs">
                <li><a class="dropdown-item" href="{{ route($module . '.show', $row) }}">View Details</a></li>
                <li><a class="dropdown-item" href="{{ route($documentRoute, $row) }}">Documents</a></li>
                @can('hr-letters.generate')
                    <li><a class="dropdown-item" href="{{ route('hr-letters.create', $row) }}">Generate Letter</a></li>
                @endcan
                @can('hr-letters.view')
                    <li><a class="dropdown-item" href="{{ route('hr-letters.index', $row) }}">View Letters</a></li>
                @endcan
                @if(in_array($role, ['Controller', 'Supervisor'], true))
                    <li><a class="dropdown-item" href="{{ route($module . '.depot-assignments.index', $row) }}">Assign Depot</a></li>
                    @if($canEdit)
                        <li><a class="dropdown-item regenerate-passcode" href="#" data-url="{{ route($module . '.regenerate-passcode', $row) }}">Regenerate Passcode</a></li>
                    @endif
                @endif
            </ul>
        </div>
    @endif

    @if($canDelete)
        <button type="button" class="btn-delete" onclick="deleteRow('{{ $row->id }}')" title="Delete">
            <i class="fa-solid fa-trash"></i>
        </button>
    @endif
</div>
