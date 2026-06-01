<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('depots.index') }}",
                data: function (data) {
                    data.state_id = $('#stateFilter').val();
                    data.district_id = $('#districtFilter').val();
                    data.location_id = $('#locationFilter').val();
                    data.status = $('#statusFilter').val();
                }
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
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'short_name', name: 'short_name', className: 'text-center' },
                { data: 'state_name', name: 'state.name', className: 'text-center' },
                { data: 'district_name', name: 'district.name', className: 'text-center' },
                { data: 'location_name', name: 'location.name', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $('.multi-select').select2({});

        $('#stateFilter').on('change', function () {
            loadDistrictOptions($('#districtFilter'), $(this).val(), '', '--- Select District ---');
            resetLocationSelect($('#locationFilter'), '--- Select District First ---');
            clearChecks();
            table.ajax.reload();
        });

        $('#districtFilter').on('change', function () {
            loadLocationOptions($('#locationFilter'), $('#stateFilter').val(), $(this).val(), '', '--- Select Location ---');
            clearChecks();
            table.ajax.reload();
        });

        $('#locationFilter, #statusFilter').on('change', function () {
            clearChecks();
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function () {
            $('#stateFilter').val(null).trigger('change');
            resetDistrictSelect($('#districtFilter'), '--- Select State First ---');
            resetLocationSelect($('#locationFilter'), '--- Select District First ---');
            $('#statusFilter').val('');
            clearChecks();
            table.ajax.reload();
        });

        $(document).on('click', '.form-btn', function () {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('depots.create') }}",
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

        $(document).on('change', '#formModal #state_id', function () {
            loadDistrictOptions($('#formModal #district_id'), $(this).val(), '', 'Select District');
            resetLocationSelect($('#formModal #location_id'), 'Select District First');
        });

        $(document).on('change', '#formModal #district_id', function () {
            loadLocationOptions(
                $('#formModal #location_id'),
                $('#formModal #state_id').val(),
                $(this).val(),
                '',
                'Select Location'
            );
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

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('depots.status') }}",
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
                error: function (xhr) {
                    table.ajax.reload();
                    let message = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                    }
                    showToast('error', message);
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
                url: "{{ route('depots.export') }}",
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
                    a.download = 'depots.xlsx';
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
        deleteRecord('/depots/' + id, 'table', 'Do you really want to delete this depot?');
    }

    function clearChecks() {
        $('#checkAll').prop('checked', false);
        $('.row-check').prop('checked', false);
    }

    function resetDistrictSelect($select, placeholder) {
        $select.empty()
            .append(new Option(placeholder, ''))
            .val('')
            .prop('disabled', true)
            .trigger('change.select2');
    }

    function resetLocationSelect($select, placeholder) {
        $select.empty()
            .append(new Option(placeholder, ''))
            .val('')
            .prop('disabled', true)
            .trigger('change.select2');
    }

    function loadDistrictOptions($select, stateId, selectedDistrictId, placeholder) {
        resetDistrictSelect($select, 'Loading...');

        if (!stateId) {
            resetDistrictSelect($select, '--- Select State First ---');
            return;
        }

        $.ajax({
            url: "{{ route('depots.districts-by-state') }}",
            type: 'GET',
            data: { state_id: stateId },
            success: function (districts) {
                $select.empty().append(new Option(placeholder, ''));

                $.each(districts, function (index, district) {
                    $select.append(new Option(district.name, district.id));
                });

                $select.prop('disabled', false).val(selectedDistrictId || '').trigger('change.select2');
            },
            error: function () {
                resetDistrictSelect($select, '--- Select State First ---');
                showToast('error', 'Failed to load districts.');
            }
        });
    }

    function loadLocationOptions($select, stateId, districtId, selectedLocationId, placeholder) {
        resetLocationSelect($select, 'Loading...');

        if (!stateId || !districtId) {
            resetLocationSelect($select, '--- Select District First ---');
            return;
        }

        $.ajax({
            url: "{{ route('depots.locations-by-district') }}",
            type: 'GET',
            data: { state_id: stateId, district_id: districtId },
            success: function (locations) {
                $select.empty().append(new Option(placeholder, ''));

                $.each(locations, function (index, location) {
                    $select.append(new Option(location.name, location.id));
                });

                $select.prop('disabled', false).val(selectedLocationId || '').trigger('change.select2');
            },
            error: function () {
                resetLocationSelect($select, '--- Select District First ---');
                showToast('error', 'Failed to load locations.');
            }
        });
    }

    function initSelect() {
        $('#formModal .select2').select2({
            placeholder: "Select an option",
            dropdownParent: $('#formModal'),
            allowClear: true
        });
    }
</script>
