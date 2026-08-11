<script>
    $(function () {
        $('#designationFilter, #depotFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('staff-management.index') }}",
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
                { data: 'ref_code', name: 'ref_code', defaultContent: '-', className: 'text-center' },
                { data: 'name', name: 'name', className: 'text-center' },
                { data: 'role', name: 'role', orderable: false, searchable: false, className: 'text-center' },
                { data: 'designation', name: 'staffProfile.designation.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'date_of_joining', name: 'staffProfile.date_of_joining', orderable: false, searchable: false, className: 'text-center' },
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

        let isResettingFilters = false;

        $('#roleFilter').on('change', function () {
            $('#designationFilterWrap').toggle($(this).val() === 'Staff');
            if ($(this).val() !== 'Staff') $('#designationFilter').val('').trigger('change.select2');
        });

        $('#resetFilters').on('click', function () {
            isResettingFilters = true;
            $('#roleFilter, #designationFilter, #depotFilter, #employmentTypeFilter, #statusFilter').val('');
            $('#designationFilter, #depotFilter').trigger('change.select2');
            $('#designationFilterWrap').show();
            $('#dateOfJoiningFilter').val('');
            $('#searchFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            isResettingFilters = false;
            reloadTable();
        });

        $(document).on('click', '.toggleStatus', function () {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 0 : 1;

            $.ajax({
                url: "{{ route('staff-management.status') }}",
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

        $(document).on('click', '.regenerate-passcode', function (event) {
            event.preventDefault();
            const url = $(this).data('url');
            Swal.fire({
                title: 'Are you sure?', text: 'Do you really want to regenerate this passcode?', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Yes, regenerate it', cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post(url, { _token: "{{ csrf_token() }}" }).done(function (res) {
                    table.ajax.reload(null, false);
                    showPasscodePopup(res.passcode);
                }).fail(function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Unable to regenerate passcode.');
                });
            });
        });

        function showPasscodePopup(passcode) {
            Swal.fire({
                icon: 'success',
                title: 'Passcode Regenerated',
                html: '<p class="mb-2">The new passcode is:</p>' +
                    '<input id="generatedPasscode" class="swal2-input text-center fw-bold" value="' + passcode + '" readonly>',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-regular fa-copy me-1"></i> Copy Passcode',
                cancelButtonText: 'Close',
                focusConfirm: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    copyGeneratedPasscode(passcode);
                }
            });
        }

        function copyGeneratedPasscode(passcode) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(String(passcode)).then(function () {
                    showToast('success', 'Passcode copied to clipboard.');
                }).catch(function () {
                    fallbackCopyPasscode(passcode);
                });
                return;
            }

            fallbackCopyPasscode(passcode);
        }

        function fallbackCopyPasscode(passcode) {
            var input = document.createElement('textarea');
            input.value = String(passcode);
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast('success', 'Passcode copied to clipboard.');
        }

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
                url: "{{ route('staff-management.export') }}",
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
                    a.download = 'staff-management.xlsx';
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
            data.role = $('#roleFilter').val();
            data.designation_id = $('#designationFilter').val();
            data.depot_id = $('#depotFilter').val();
            data.employment_type = $('#employmentTypeFilter').val();
            data.status = $('#statusFilter').val();
            data.date_of_joining = $('#dateOfJoiningFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }

    });

    function deleteRow(id) {
        deleteRecord('/staff-management/' + id, 'table', 'Do you really want to delete this staff?');
    }
</script>
