<script>
    $(function () {
        $('#yearFilter, #monthFilter').select2({
            width: '100%',
            placeholder: '---Select---',
            allowClear: true
        });

        var table = $('#attendanceTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('attendance-management.index') }}",
                data: function (data) {
                    data.year = $('#yearFilter').val();
                    data.month = $('#monthFilter').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'month_name', name: 'month', className: 'text-center' },
                { data: 'year', name: 'year', className: 'text-center' },
                { data: 'user_type_display', name: 'user_type', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: []
        });

        $('.attendance-filter').on('change', function () {
            table.ajax.reload();
        });

        $('#resetAttendanceFilters').on('click', function () {
            $('#yearFilter, #monthFilter').val('').trigger('change');
        });
    });
</script>
