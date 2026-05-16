<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('leave-types.index') }}",
                data: function (data) {
                    data.search_text = $('#searchFilter').val();
                    data.leave_category = $('#categoryFilter').val();
                    data.applicable_for = $('#applicableForFilter').val();
                    data.status = $('#statusFilter').val();
                }
            },
            columns: [
                {
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        return `<input type="checkbox" class="row-check" value="${row.id}">`;
                    },
                    className: 'text-center'
                },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'leave_name', name: 'leave_name', className: 'text-center' },
                { data: 'leave_category', name: 'leave_category', className: 'text-center' },
                { data: 'max_leaves_per_year', name: 'max_leaves_per_year', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[3, 'asc']]
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                $('#checkAll').prop('checked', false);
                table.ajax.reload();
            }, 300);
        });

        $('#categoryFilter, #applicableForFilter, #statusFilter').on('change', function () {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function () {
            $('#searchFilter').val('');
            $('#categoryFilter').val('');
            $('#applicableForFilter').val('');
            $('#statusFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            table.ajax.reload();
        });

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('leave-types.status') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    status: newStatus
                },
                success: function (res) {
                    table.ajax.reload();
                    showToast('success', res.message);
                },
                error: function (xhr) {
                    table.ajax.reload();
                    let message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    showToast('error', message);
                }
            });
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('click', '#exportSelected', function () {
            let selectedIds = [];
            $('.row-check:checked').each(function () {
                selectedIds.push($(this).val());
            });
            if (selectedIds.length === 0) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }
            $.ajax({
                url: "{{ route('leave-types.export') }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: selectedIds },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'leave-types.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    $('.row-check').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function () {
                    showToast('error', 'Export failed.');
                }
            });
        });
    });

    function deleteRow(id) {
        deleteRecord('/leave-types/' + id, 'table', 'Do you really want to delete this leave type?');
    }
</script>
