<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('routes.schedules.index', $route->id) }}",
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
                { data: 'schedule_date_display', name: 'schedule_date', className: 'text-center' },
                { data: 'planned_start_time', name: 'planned_start_time', className: 'text-center' },
                { data: 'planned_end_time', name: 'planned_end_time', className: 'text-center' },
                { data: 'vehicle_name', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                { data: 'driver_name', name: 'driver.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $(document).on('click', '.form-btn', function () {
            $.ajax({
                url: "{{ route('routes.schedules.create', $route->id) }}",
                type: 'GET',
                data: { id: $(this).data('id') },
                success: function (response) {
                    $('#modalBody').html(response.html);
                    $('#modalTitle').text(response.title);
                    $('#formModal .select2').select2({ dropdownParent: $('#formModal'), width: '100%', allowClear: true });
                    $('#formModal').modal('show');
                },
                error: function () {
                    showToast('error', 'Failed to load form.');
                }
            });
        });

        $(document).on('submit', '#commonForm', function (e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnHtml = submitBtn.html();

            form.find('.error-text').text('');
            form.find('.form-control, .form-select').removeClass('is-invalid');
            submitBtn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: form.attr('action'),
                type: form.find('input[name="_method"]').val() || 'POST',
                data: form.serialize(),
                success: function (response) {
                    table.ajax.reload();
                    $('#formModal').modal('hide');
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            form.find('.' + field + '_error').text(messages[0]);
                            form.find('[name="' + field + '"]').addClass('is-invalid');
                        });
                    } else {
                        showToast('error', xhr.responseJSON?.message || 'Something went wrong');
                    }
                },
                complete: function () {
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });
    });

    function deleteRow(id) {
        deleteRecord('/route-schedules/' + id, 'table', 'Do you really want to delete this route schedule?');
    }
</script>
