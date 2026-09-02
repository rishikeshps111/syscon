<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('driver-change-reasons.index') }}",
                data: function (data) { data.status = $('#statusFilter').val(); }
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $('#statusFilter').on('change', function () { $('#checkAll').prop('checked', false); table.ajax.reload(); });
        $('#resetFilters').on('click', function () {
            $('#statusFilter').val(''); $('#checkAll').prop('checked', false); $('.row-check').prop('checked', false); table.ajax.reload();
        });

        $(document).on('click', '.form-btn', function () {
            $.get("{{ route('driver-change-reasons.create') }}", { id: $(this).data('id') })
                .done(function (response) { $('#modalBody').html(response.html); $('#modalTitle').text(response.title); $('#formModal').modal('show'); })
                .fail(function () { showToast('error', 'Failed to load form.'); });
        });

        $(document).on('submit', '#commonForm', function (e) {
            e.preventDefault();
            var form = $(this), submitBtn = form.find('button[type="submit"]'), original = submitBtn.html();
            form.find('.error-text').text(''); form.find('.form-control, .form-select').removeClass('is-invalid');
            submitBtn.prop('disabled', true).html('Loading...');
            $.ajax({
                url: form.attr('action'), type: form.find('input[name="_method"]').val() || 'POST', data: form.serialize(),
                success: function (response) { table.ajax.reload(); $('#formModal').modal('hide'); showToast('success', response.message); },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) { form.find('.' + field + '_error').text(messages[0]); form.find('[name="' + field + '"]').addClass('is-invalid'); });
                    } else { showToast('error', xhr.responseJSON?.message || 'Something went wrong'); }
                },
                complete: function () { submitBtn.prop('disabled', false).html(original); }
            });
        });

        $(document).on('click', '.toggleStatus', function () {
            var input = $(this), newStatus = input.data('status') == 1 ? 0 : 1;
            $.post("{{ route('driver-change-reasons.status') }}", { _token: "{{ csrf_token() }}", id: input.data('id'), status: newStatus })
                .done(function (response) { table.ajax.reload(); showToast('success', response.message); })
                .fail(function () { table.ajax.reload(); showToast('error', 'An error occurred. Please try again.'); });
        });

        $('#checkAll').on('change', function () { $('.row-check').prop('checked', this.checked); });
        $(document).on('click', '#exportSelected', function () {
            var ids = $('.row-check:checked').map(function () { return $(this).val(); }).get();
            if (!ids.length) { showToast('warning', 'Please select at least one row to export.'); return; }
            $.ajax({ url: "{{ route('driver-change-reasons.export') }}", type: 'POST', data: { _token: "{{ csrf_token() }}", ids: ids }, xhrFields: { responseType: 'blob' },
                success: function (data) { var url = URL.createObjectURL(new Blob([data])); var a = $('<a>').attr({ href: url, download: 'driver-change-reasons.xlsx' }).appendTo('body'); a[0].click(); a.remove(); URL.revokeObjectURL(url); showToast('success', 'Export completed successfully.'); },
                error: function () { showToast('error', 'Export failed.'); }
            });
        });
    });

    function deleteRow(id) { deleteRecord('/driver-change-reasons/' + id, 'table', 'Do you really want to delete this driver change reason?'); }
</script>
