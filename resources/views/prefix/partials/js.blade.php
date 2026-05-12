<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('prefixes.index') }}",
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
                { data: 'prefix', name: 'prefix', className: 'text-center' },
                { data: 'module', name: 'module', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $(document).on('click', '.form-btn', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('prefixes.create') }}",
                type: 'GET',
                data: { id: id },
                success: function (response) {
                    $('#modalBody').html(response.html);
                    $('#modalTitle').text(response.title);
                    initSelect();
                    $('#formModal').modal('show');
                },
                error: function (xhr) {
                    console.log('Error:', xhr.responseText);
                    showToast('error', 'Failed to load form.');
                }
            });
        });
        $(document).on('submit', '#commonForm', function (e) {
            e.preventDefault();

            var form = $(this);
            var url = form.attr('action');
            var method = form.find('input[name="_method"]').val() || 'POST';
            var formData = form.serialize();
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnHtml = submitBtn.html();

            form.find('.error-text').text('');
            form.find('.form-control, .form-select').removeClass('is-invalid');
            submitBtn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function (response) {
                    table.ajax.reload();
                    $('#formModal').modal('hide');
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;

                        $.each(errors, function (field, messages) {
                            form.find('.' + field + '_error').text(messages[0]);
                            form.find('[name="' + field + '"]').addClass('is-invalid');
                        });
                    } else {
                        showToast('error', 'Something went wrong');
                    }
                },
                complete: function () {
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('prefixes.status') }}",
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
                error: function (xhr, status, error) {
                    if (xhr.status === 403) {
                        showToast('error', 'You do not have permission to perform this action.');
                    } else if (xhr.status === 404) {
                        showToast('error', 'Record not found.');
                    } else if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let errorMessage = Object.values(errors).flat().join('<br>');
                        showToast('error', errorMessage);
                    } else if (xhr.status === 500) {
                        showToast('error', 'Server error. Please try again later.');
                    } else {
                        let message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                        showToast('error', message);
                    }
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
                url: "{{ route('prefixes.export') }}",
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
                    a.download = 'prefixes.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    // Uncheck all checkboxes
                    $('.row-check').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function (xhr) {
                    showToast('error', 'Export failed.');
                }
            });
        });
    });

    function deleteRow(id) {
        deleteRecord('/prefixes/' + id, 'table', 'Do you really want to delete this record?');
    }

    function initSelect() {
        $('.select2').select2({
            placeholder: "Select an option",
            dropdownParent: $('#formModal'),
            allowClear: true
        });
    }
</script>