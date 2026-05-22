<script>
    $(function () {
        $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('vehicles.index') }}",
                data: filters
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'vehicle_no', name: 'vehicle_no', className: 'text-center' },
                { data: 'type', name: 'vehicle_type', className: 'text-center' },
                { data: 'fuel', name: 'fuel_type', className: 'text-center' },
                { data: 'oem_name', name: 'oem.oem_name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'capacity', name: 'capacity_seating', orderable: false, searchable: false, className: 'text-center' },
                { data: 'insurance_expiry_badge', name: 'insurance_expiry', className: 'text-center' },
                { data: 'fitness_expiry_badge', name: 'fitness_expiry', className: 'text-center' },
                { data: 'gps_status', name: 'gps_enabled', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: []
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 300);
        });

        $('#searchFilters').on('click', reloadTable);
        $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').on('change', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter, #searchFilter').val('');
            $('#stateFilter, #oemFilter, #vehicleTypeFilter, #fuelTypeFilter, #statusFilter, #gpsFilter').trigger('change.select2');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadTable();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('change', '.row-check', function () {
            $('#checkAll').prop('checked', $('.row-check').length === $('.row-check:checked').length);
        });

        $(document).on('click', '.change-status-btn', function () {
            $('#changeStatusForm').attr('action', $(this).data('url'));
            $('#modalStatus').val($(this).data('status'));
            $('#changeStatusModal').modal('show');
        });

        $('#changeStatusForm').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnHtml = submitBtn.html();
            form.find('.error-text').text('');
            submitBtn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('#changeStatusModal').modal('hide');
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
                    submitBtn.prop('disabled', false).html(originalBtnHtml);
                }
            });
        });

        $('#exportSelected').on('click', function () {
            let selectedIds = [];
            $('.row-check:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }

            $.ajax({
                url: "{{ route('vehicles.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    search_text: $('#searchFilter').val(),
                    state_id: $('#stateFilter').val(),
                    oem_id: $('#oemFilter').val(),
                    vehicle_type: $('#vehicleTypeFilter').val(),
                    fuel_type: $('#fuelTypeFilter').val(),
                    status: $('#statusFilter').val(),
                    gps_enabled: $('#gpsFilter').val()
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'vehicles.xlsx';
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
            data.state_id = $('#stateFilter').val();
            data.oem_id = $('#oemFilter').val();
            data.vehicle_type = $('#vehicleTypeFilter').val();
            data.fuel_type = $('#fuelTypeFilter').val();
            data.status = $('#statusFilter').val();
            data.gps_enabled = $('#gpsFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }
    });

    function deleteRow(id) {
        deleteRecord('/vehicles/' + id, 'table', 'Do you really want to delete this vehicle?');
    }
</script>
