<script>
    $(function () {
        $('#stateFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('driver-management.index') }}",
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
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'phone_number', name: 'phone', orderable: false, searchable: false, className: 'text-center' },
                { data: 'license_type', name: 'driverProfile.license_type', orderable: false, searchable: false, className: 'text-center' },
                { data: 'license_expiry', name: 'driverProfile.expiry_date', orderable: false, searchable: false, className: 'text-center' },
                { data: 'verification_status', name: 'driverProfile.verification_status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[3, 'asc']]
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 300);
        });

        $('#searchFilters').on('click', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#stateFilter, #employmentTypeFilter, #licenseTypeFilter, #verificationStatusFilter, #statusFilter, #expiryFilter').val('');
            $('#stateFilter').trigger('change.select2');
            $('#searchFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadTable();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('driver-management.status') }}",
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
                url: "{{ route('driver-management.export') }}",
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
                    a.download = 'driver-management.xlsx';
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
            data.employment_type = $('#employmentTypeFilter').val();
            data.license_type = $('#licenseTypeFilter').val();
            data.verification_status = $('#verificationStatusFilter').val();
            data.status = $('#statusFilter').val();
            data.expiry_filter = $('#expiryFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }
    });

    function deleteRow(id) {
        deleteRecord('/driver-management/' + id, 'table', 'Do you really want to delete this driver?');
    }
</script>

<style>
    .driver-license-badge {
        border-radius: 6px;
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        min-width: 112px;
        padding: 6px 8px;
    }

    .driver-license-badge small {
        font-size: 11px;
        font-weight: 500;
    }

    .driver-license-expired {
        animation: driverBadgeBlink 1s infinite;
        background: #ffe7e7;
        color: #b42318;
    }

    .driver-license-warning {
        animation: driverBadgeBlink 1.3s infinite;
        background: #fff4cc;
        color: #946200;
    }

    .driver-license-active {
        background: #e8f7ee;
        color: #16803c;
    }

    .driver-verification-pending {
        background: #fff4cc;
        border-radius: 5px;
        color: #946200;
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 10px;
    }

    @keyframes driverBadgeBlink {
        50% {
            opacity: .45;
        }
    }
</style>
