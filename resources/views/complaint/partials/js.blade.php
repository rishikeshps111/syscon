<script>
    $(function () {
        var activeRole = @json($activeRole);
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('complaints.index') }}",
                data: function (data) {
                    data.reported_by_role = activeRole;
                    data.search_text = $('#searchFilter').val();
                    data.against_role = $('#againstRoleFilter').val();
                    data.complaint_category_id = $('#categoryFilter').val();
                    data.severity = $('#severityFilter').val();
                    data.status = $('#statusFilter').val();
                    data.date_from = $('#dateFromFilter').val();
                    data.date_to = $('#dateToFilter').val();
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
                { data: 'complaint_date_display', name: 'complaint_date', className: 'text-center' },
                { data: 'reported_by', name: 'reportedBy.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'against', name: 'againstUser.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'category', name: 'category.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'severity_badge', name: 'severity', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[3, 'desc']]
        });

        let searchTimer;
        $('#searchFilter').on('keyup', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(reloadTable, 300);
        });

        $('#searchFilters').on('click', reloadTable);

        $('#againstRoleFilter, #categoryFilter, #severityFilter, #statusFilter, #dateFromFilter, #dateToFilter').on('change', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#againstRoleFilter, #categoryFilter, #severityFilter, #statusFilter, #dateFromFilter, #dateToFilter, #searchFilter').val('');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            reloadTable();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('click', '.change-status-btn', function () {
            $('#changeStatusForm').attr('action', $(this).data('url'));
            $('#modalStatus').val($(this).data('status'));
            $('#changeStatusModal').modal('show');
        });

        $(document).on('click', '.assign-action-btn', function () {
            $('#assignActionForm').attr('action', $(this).data('url'));
            $('#assignedTo').val($(this).data('assigned-to') || '');
            $('#actionTaken').val($(this).data('action-taken') || '');
            $('#actionDate').val($(this).data('action-date') || '');
            $('#assignActionModal').modal('show');
        });

        $('#changeStatusForm, #assignActionForm').on('submit', function (e) {
            e.preventDefault();
            submitActionForm($(this));
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
                url: "{{ route('complaints.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    reported_by_role: activeRole,
                    search_text: $('#searchFilter').val(),
                    against_role: $('#againstRoleFilter').val(),
                    complaint_category_id: $('#categoryFilter').val(),
                    severity: $('#severityFilter').val(),
                    status: $('#statusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val()
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'complaints.xlsx';
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

        function submitActionForm(form) {
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnHtml = submitBtn.html();
            form.find('.error-text').text('');
            submitBtn.prop('disabled', true).html('Loading...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $('.modal').modal('hide');
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
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }
    });

    function deleteRow(id) {
        deleteRecord('/complaints/' + id, 'table', 'Do you really want to delete this complaint?');
    }
</script>
