<div class="action-btns">
    @can('attendance-management.edit')
        <a href="{{ route('attendance-management.manage', ['year' => $row->year, 'month' => $row->month, 'user_type' => $row->user_type]) }}"
            class="btn-cstm btn-nowrap" title="Manage List">
            Manage List
        </a>
    @endcan
    @can('attendance-management.view')
        <a href="{{ route('attendance-management.print', ['year' => $row->year, 'month' => $row->month, 'role' => $row->user_type]) }}"
            class="btn-view" title="Print">
            <i class="fa-solid fa-print"></i>
        </a>
    @endcan
</div>
