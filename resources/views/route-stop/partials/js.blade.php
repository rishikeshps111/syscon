<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('routes.stops.index', $route->id) }}",
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
                // { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center drag-handle',
                    render: function () { return '<i class="fa-solid fa-grip-vertical" title="Drag to reorder"></i>'; }
                },
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            ordering: false,
            createdRow: function (row, data) {
                $(row).attr({ 'data-id': data.id, draggable: true });
            },
            drawCallback: function () {
                enableStopDragging();
            }
        });

        var draggedRow = null;

        function enableStopDragging() {
            $('#table tbody tr').off('.routeStopDrag')
                .on('dragstart.routeStopDrag', function (event) {
                    draggedRow = this;
                    event.originalEvent.dataTransfer.effectAllowed = 'move';
                    $(this).addClass('opacity-50');
                })
                .on('dragover.routeStopDrag', function (event) {
                    event.preventDefault();
                    if (!draggedRow || draggedRow === this) return;
                    var rect = this.getBoundingClientRect();
                    this.parentNode.insertBefore(draggedRow, event.originalEvent.clientY < rect.top + rect.height / 2 ? this : this.nextSibling);
                })
                .on('drop.routeStopDrag', function (event) {
                    event.preventDefault();
                    saveStopOrder();
                })
                .on('dragend.routeStopDrag', function () {
                    $(this).removeClass('opacity-50');
                    draggedRow = null;
                });
        }

        function saveStopOrder() {
            var ids = $('#table tbody tr').map(function () { return $(this).data('id'); }).get();
            $.ajax({
                url: "{{ route('routes.stops.reorder', $route->id) }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", ids: ids },
                success: function (response) {
                    table.ajax.reload(null, false);
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    table.ajax.reload(null, false);
                    showToast('error', xhr.responseJSON?.message || 'Failed to update route stop order.');
                }
            });
        }

        $(document).on('click', '.form-btn', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('routes.stops.create', $route->id) }}",
                type: 'GET',
                data: { id: id },
                success: function (response) {
                    $('#modalBody').html(response.html);
                    $('#modalTitle').text(response.title);
                    $('#formModal #location_id').select2({
                        dropdownParent: $('#formModal'),
                        width: '100%',
                        placeholder: '--- Select Location ---',
                        allowClear: true
                    });
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

                        if (errors) {
                            $.each(errors, function (field, messages) {
                                form.find('.' + field + '_error').text(messages[0]);
                                form.find('[name="' + field + '"]').addClass('is-invalid');
                            });
                        } else {
                            showToast('error', xhr.responseJSON.message || 'Validation failed.');
                        }
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
                url: "{{ route('routes.stops.export', $route->id) }}",
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
                    a.download = 'route-stops.xlsx';
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
        deleteRecord('/route-stops/' + id, 'table', 'Do you really want to delete this route stop?');
    }
</script>
