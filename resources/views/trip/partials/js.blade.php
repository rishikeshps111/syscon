<script>
    $(function () {
        $('#depotFilter, #oemFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('trips.index') }}",
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
                { data: 'trip_title', name: 'title', className: 'text-center' },
                { data: 'from_location', name: 'from_location', orderable: false, searchable: false, className: 'text-center' },
                { data: 'to_location', name: 'to_location', orderable: false, searchable: false, className: 'text-center' },
                { data: 'halt_time', name: 'halt_time', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']]
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 300);
        });

        $('#searchFilters').on('click', reloadTable);
        $('#depotFilter, #oemFilter, #dateFromFilter, #dateToFilter, #statusFilter').on('change', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#depotFilter, #oemFilter, #dateFromFilter, #dateToFilter, #statusFilter, #searchFilter').val('');
            $('#depotFilter, #oemFilter').trigger('change.select2');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadTable();
        });

        $(document).on('click', '.status-btn', function () {
            $('#statusTripId').val($(this).data('id'));
            $('#modalTripStatus').val($(this).data('status'));
            $('#modalCancellationReason').val($(this).data('reason') || '');
            toggleModalCancellationReason();
            $('#changeTripStatusModal').modal('show');
        });

        $('#modalTripStatus').on('change', toggleModalCancellationReason);

        $('#changeTripStatusForm').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var button = $('#tripStatusSubmitBtn');
            var originalText = button.data('original-text') || button.text();
            form.find('.error-text').text('');
            button.data('original-text', originalText).prop('disabled', true).text('Please wait...');

            $.ajax({
                url: "{{ route('trips.status') }}",
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#changeTripStatusModal').modal('hide');
                    table.ajax.reload();
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            form.find('.' + field + '_error').text(messages[0]);
                        });
                    } else {
                        showToast('error', xhr.responseJSON?.message || 'Something went wrong');
                    }
                },
                complete: function () {
                    button.prop('disabled', false).text(originalText);
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
                url: "{{ route('trips.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    search_text: $('#searchFilter').val(),
                    depot_id: $('#depotFilter').val(),
                    oem_id: $('#oemFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    status: $('#statusFilter').val()
                },
                xhrFields: { responseType: 'blob' },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'trip-management.xlsx';
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
            data.depot_id = $('#depotFilter').val();
            data.oem_id = $('#oemFilter').val();
            data.date_from = $('#dateFromFilter').val();
            data.date_to = $('#dateToFilter').val();
            data.status = $('#statusFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }
    });

    function deleteRow(id) {
        deleteRecord('/trips/' + id, 'table', 'Do you really want to delete this trip?');
    }

    function refreshRoutePreview() {
        var option = $('#route_id').find(':selected');
        $('#startPointPreview').val(option.data('start') || '');
        $('#endPointPreview').val(option.data('end') || '');
        $('#stopsPreview').val(option.data('stops') || '');
    }

    function toggleCancellationReason() {
        $('#cancellationReasonWrap').toggle($('#status').val() === 'Cancelled');
    }

    function toggleModalCancellationReason() {
        $('#modalCancellationReasonWrap').toggle($('#modalTripStatus').val() === 'Cancelled');
    }
</script>
