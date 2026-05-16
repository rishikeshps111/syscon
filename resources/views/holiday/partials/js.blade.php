<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('holidays.index') }}",
                data: filters
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
                { data: 'holiday_name', name: 'holiday_name', className: 'text-center' },
                { data: 'holiday_date', name: 'holiday_date', className: 'text-center' },
                { data: 'type', name: 'holiday_type', className: 'text-center' },
                { data: 'location', name: 'applicable_location', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[4, 'asc']]
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadViews, 300);
        });

        $('#yearFilter, #typeFilter, #locationFilter, #statusFilter').on('change', reloadViews);

        $('#resetFilters').on('click', function () {
            $('#searchFilter').val('');
            $('#yearFilter').val('{{ now()->year }}');
            $('#typeFilter').val('');
            $('#locationFilter').val('');
            $('#statusFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadViews();
        });

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('holidays.status') }}",
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
                url: "{{ route('holidays.export') }}",
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
                    a.download = 'holidays.xlsx';
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

        function filters(data) {
            data.search_text = $('#searchFilter').val();
            data.year = $('#yearFilter').val();
            data.holiday_type = $('#typeFilter').val();
            data.location = $('#locationFilter').val();
            data.status = $('#statusFilter').val();
        }

        function reloadViews() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }
    });

    function deleteRow(id) {
        deleteRecord('/holidays/' + id, 'table', 'Do you really want to delete this holiday?');
    }
</script>
