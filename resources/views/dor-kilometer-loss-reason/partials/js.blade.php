<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('dor-kilometer-loss-reasons.index') }}",
                data: function (data) {
                    data.status = $('#statusFilter').val();
                    data.dor_account_responsible_id = $('#accountFilter').val();
                }
            },
            columns: [
                { data: 'checkbox', orderable: false, searchable: false, className: 'text-center', render: function (data, type, row) { return `<input type="checkbox" class="row-check" value="${row.id}">`; } },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'account_responsible', name: 'accountResponsible.name', className: 'text-center' },
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $('#statusFilter, #accountFilter').on('change', function () {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function () {
            $('#statusFilter, #accountFilter').val('');
            $('#checkAll, .row-check').prop('checked', false);
            table.ajax.reload();
        });

        $(document).on('click', '.form-btn', function () {
            $.get("{{ route('dor-kilometer-loss-reasons.create') }}", { id: $(this).data('id') })
                .done(function (response) {
                    $('#modalBody').html(response.html);
                    $('#modalTitle').text(response.title);
                    $('#formModal').modal('show');
                })
                .fail(function () {
                    showToast('error', 'Failed to load form.');
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

        $(document).on('click', '.toggleStatus', function () {
            $.post("{{ route('dor-kilometer-loss-reasons.status') }}", {
                _token: "{{ csrf_token() }}",
                id: $(this).data('id'),
                status: $(this).data('status') == 1 ? 0 : 1
            }).done(function (response) {
                table.ajax.reload();
                showToast('success', response.message);
            }).fail(function (xhr) {
                table.ajax.reload();
                showToast('error', xhr.responseJSON?.message || 'An error occurred. Please try again.');
            });
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $('#exportSelected').on('click', function () {
            var selectedIds = $('.row-check:checked').map(function () { return $(this).val(); }).get();

            if (!selectedIds.length) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }

            $.ajax({
                url: "{{ route('dor-kilometer-loss-reasons.export') }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: selectedIds },
                xhrFields: { responseType: 'blob' },
                success: function (data) {
                    var a = document.createElement('a');
                    a.href = window.URL.createObjectURL(new Blob([data]));
                    a.download = 'dor-kilometer-loss-reasons.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(a.href);
                    document.body.removeChild(a);
                    $('#checkAll, .row-check').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function () {
                    showToast('error', 'Export failed.');
                }
            });
        });
    });

    function deleteRow(id) {
        deleteRecord('/dor-kilometer-loss-reasons/' + id, 'table', 'Do you really want to delete this reason?');
    }
</script>