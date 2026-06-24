<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('salary-processing.index') }}",
                data: function (data) {
                    data.year = $('#yearFilter').val();
                    data.month = $('#monthFilter').val();
                    data.depot_id = $('#depotFilter').val();
                    data.role_id = $('#roleFilter').val();
                }
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'month_name', name: 'month', className: 'text-center' },
                { data: 'year', name: 'year', className: 'text-center' },
                { data: 'depot_name', name: 'depot.name', orderable: false, className: 'text-center' },
                { data: 'role_name', name: 'role.name', orderable: false, className: 'text-center' },
                { data: 'payment_method', name: 'payment_method', className: 'text-center' },
                { data: 'created_by_name', name: 'creator.name', orderable: false, className: 'text-center' },
                { data: 'created_date_time', name: 'created_at', className: 'text-center' },
                { data: 'approved_by_name', name: 'approver.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'approval_status_label', name: 'status', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $('#yearFilter, #monthFilter, #depotFilter, #roleFilter').on('change', function () {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function () {
            $('#yearFilter, #monthFilter, #depotFilter, #roleFilter').val('');
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });
    });

    function approveSalary(id) {
        Swal.fire({
            title: 'Approve salary processing?',
            text: 'Once approved, this salary processing will be marked as approved.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/salary-processing/' + id + '/approve',
                type: 'POST',
                data: { _token: "{{ csrf_token() }}" },
                success: function (response) {
                    showToast('success', response.message || 'Approved successfully.');
                    $('#table').DataTable().ajax.reload(null, false);
                },
                error: function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Approval failed.');
                }
            });
        });
    }

    function deleteRow(id) {
        deleteRecord('/salary-processing/' + id, 'table', 'Do you really want to delete this salary processing?');
    }
</script>
