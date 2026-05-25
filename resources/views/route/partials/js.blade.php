<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('routes.index') }}",
                data: function (data) {
                    data.state_id = $('#stateFilter').val();
                    data.district_id = $('#districtFilter').val();
                    data.route_type = $('#routeTypeFilter').val();
                    data.route_category = $('#routeCategoryFilter').val();
                    data.status = $('#statusFilter').val();
                    data.search.value = $('#searchFilter').val();
                }
            },
            columns: [{
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `<input type="checkbox" class="row-check" value="${row.id}">`;
                },
                className: 'text-center'
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'route_code',
                name: 'route_code',
                className: 'text-center'
            },
            {
                data: 'route_name',
                name: 'route_name',
                className: 'text-center'
            },
            {
                data: 'start_end',
                name: 'start_end',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'total_distance_km',
                name: 'total_distance_km',
                className: 'text-center'
            },
            {
                data: 'estimated_duration',
                name: 'estimated_duration',
                className: 'text-center'
            },
            {
                data: 'assigned_vehicle',
                name: 'assigned_vehicle',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'assigned_driver',
                name: 'assigned_driver',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'status',
                name: 'status',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
            ]
        });

        $('.select2-filter').select2({
            width: '100%',
            placeholder: '---Select---',
            allowClear: true
        });

        $('#searchFilters').on('click', function () {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#searchFilter').on('keyup', function () {
            table.ajax.reload();
        });

        $('#stateFilter').on('change', function () {
            var stateId = $(this).val();
            $('#districtFilter option[data-state-id]').each(function () {
                $(this).prop('disabled', stateId && $(this).data('state-id').toString() !==
                    stateId);
            });

            if ($('#districtFilter option:selected').prop('disabled')) {
                $('#districtFilter').val('').trigger('change.select2');
            }

            $('#districtFilter').trigger('change.select2');
        });

        $('#resetFilters').on('click', function () {
            $('#stateFilter, #districtFilter, #routeTypeFilter, #routeCategoryFilter, #statusFilter')
                .val('').trigger('change.select2');
            $('#searchFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            table.ajax.reload();
        });

        $(document).on('click', '.change-status-btn', function () {
            $('#routeStatusId').val($(this).data('id'));
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
                url: "{{ route('routes.status') }}",
                type: "POST",
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
                url: "{{ route('routes.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data) {
                    let blob = new Blob([data], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'routes.xlsx';
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
        deleteRecord('/routes/' + id, 'table', 'Do you really want to delete this route?');
    }
</script>
