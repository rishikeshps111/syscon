<script>
    $(function () {
        var tables = {};
        var consolidatedTable = null;
        var filterUsersByRole = @json($filterUsersByRole ?? []);

        $('.leave-select-filter').select2({
            width: '100%',
            placeholder: '---Select---',
            allowClear: true
        });

        $('.leave-table').each(function () {
            var tableElement = $(this);
            var type = tableElement.data('type');
            var columns = [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code_display', name: 'code', className: 'text-center' },
            ];

            if (type === 'driver') {
                columns = columns.concat([
                    { data: 'employee_name', name: 'user.name', orderable: false, className: 'text-center' },
                    { data: 'from_display', name: 'from_date', className: 'text-center' },
                    { data: 'to_display', name: 'to_date', className: 'text-center' },
                    { data: 'days_display', name: 'number_of_days', className: 'text-center' },
                    { data: 'shift', name: 'shift', className: 'text-center' },
                    { data: 'assigned_vehicle_route', name: 'assigned_vehicle_route', className: 'text-center' },
                    { data: 'leave_type_name', name: 'leaveType.leave_name', orderable: false, className: 'text-center' },
                ]);
            } else {
                columns = columns.concat([
                    { data: 'employee_name', name: 'user.name', orderable: false, className: 'text-center' },
                    { data: 'role_name', name: 'role_name', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'leave_type_name', name: 'leaveType.leave_name', orderable: false, className: 'text-center' },
                    { data: 'from_display', name: 'from_date', className: 'text-center' },
                    { data: 'to_display', name: 'to_date', className: 'text-center' },
                    { data: 'days_display', name: 'number_of_days', className: 'text-center' },
                ]);
            }

            columns = columns.concat([
                { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ]);

            tables[type] = tableElement.DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('leaves.index') }}",
                    data: function (data) {
                        data.table_type = type;
                        data.status = $('#' + type + 'StatusFilter').val();
                        data.employee_name = $('#' + type + 'EmployeeFilter').val();
                        data.leave_type = $('#' + type + 'LeaveTypeFilter').val();
                        data.role = $('#' + type + 'RoleFilter').val();
                        data.from_date = $('#' + type + 'FromDateFilter').val();
                        data.to_date = $('#' + type + 'ToDateFilter').val();
                    }
                },
                columns: columns,
                order: []
            });
        });

        $('.reset-leave-filters').on('click', function () {
            var type = $(this).data('type');
            $('#' + type + 'FromDateFilter, #' + type + 'ToDateFilter').val('');
            $('#' + type + 'LeaveTypeFilter, #' + type + 'RoleFilter, #' + type + 'StatusFilter').val('').trigger('change');
            loadEmployeeFilterOptions(type);
            tables[type].ajax.reload();
        });

        function loadEmployeeFilterOptions(type) {
            var role = $('#' + type + 'RoleFilter').val();
            var employeeFilter = $('#' + type + 'EmployeeFilter');

            if (! employeeFilter.is('select')) {
                return;
            }

            employeeFilter.empty();

            if (! role || ! filterUsersByRole[role]) {
                employeeFilter.append(new Option('---Select User Type First---', ''));
                employeeFilter.val('').trigger('change.select2');
                return;
            }

            employeeFilter.append(new Option('---Select---', ''));
            filterUsersByRole[role].forEach(function (user) {
                employeeFilter.append(new Option(user.name, user.search_name));
            });
            employeeFilter.val('').trigger('change.select2');
        }

        $('.leave-select-filter, input[type="date"].leave-filter').on('change', function () {
            var type = $(this).closest('.tab-pane').find('.leave-table').data('type');

            if (! type || ! tables[type]) {
                return;
            }

            if ($(this).attr('id') === type + 'RoleFilter') {
                loadEmployeeFilterOptions(type);
            }

            tables[type].ajax.reload();
        });

        var employeeFilterTimer = null;
        $('[id$="EmployeeFilter"]').on('input', function () {
            var type = $(this).closest('.tab-pane').find('.leave-table').data('type');
            clearTimeout(employeeFilterTimer);
            employeeFilterTimer = setTimeout(function () {
                if (tables[type]) {
                    tables[type].ajax.reload();
                }
            }, 400);
        });

        $('.leave-filter').on('keypress', function (event) {
            if (event.which === 13) {
                event.preventDefault();
            }
        });

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
            var type = $(this).data('table-type');
            if (tables[type]) {
                tables[type].columns.adjust();
            }
            if (type === 'consolidated' && consolidatedTable) {
                consolidatedTable.columns.adjust();
            }
        });

        $(document).on('change', '.check-all', function () {
            $(this).closest('table').find('.row-check').prop('checked', this.checked);
        });

        $(document).on('click', '.change-status-btn', function () {
            $('#changeStatusForm').attr('action', $(this).data('url'));
            $('#modalStatus').val($(this).data('status'));
            $('#modalRemarks').val($(this).data('remarks') || '');
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
                    reloadAllTables();
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

        $('.export-leaves').on('click', function () {
            var type = $(this).data('type');
            var table = $('#' + type + 'LeaveTable');
            var ids = [];

            table.find('.row-check:checked').each(function () {
                ids.push($(this).val());
            });

            if (ids.length === 0) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }

            $.ajax({
                url: "{{ route('leaves.export') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: ids,
                    table_type: type,
                    status: $('#' + type + 'StatusFilter').val(),
                    employee_name: $('#' + type + 'EmployeeFilter').val(),
                    leave_type: $('#' + type + 'LeaveTypeFilter').val(),
                    role: $('#' + type + 'RoleFilter').val(),
                    from_date: $('#' + type + 'FromDateFilter').val(),
                    to_date: $('#' + type + 'ToDateFilter').val()
                },
                xhrFields: { responseType: 'blob' },
                success: function (data) {
                    var blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'leave-management.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    table.find('.row-check').prop('checked', false);
                    table.find('.check-all').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Export failed.');
                }
            });
        });

        function consolidatedFilters() {
            return {
                status: $('#consolidatedStatusFilter').val(),
                employee_name: $('#consolidatedEmployeeFilter').val(),
                leave_type: $('#consolidatedLeaveTypeFilter').val(),
                role: $('#consolidatedRoleFilter').val(),
                from_date: $('#consolidatedFromDateFilter').val(),
                to_date: $('#consolidatedToDateFilter').val()
            };
        }

        function initConsolidatedTable() {
            if (consolidatedTable) {
                consolidatedTable.ajax.reload();
                return;
            }

            consolidatedTable = $('#consolidatedLeaveTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('leaves.consolidated-report.data') }}",
                    data: function (data) {
                        Object.assign(data, consolidatedFilters());
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'code_display', name: 'code', className: 'text-center' },
                    { data: 'employee_name', name: 'user.name', orderable: false, className: 'text-center' },
                    { data: 'role_name', name: 'role_name', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'leave_type_name', name: 'leaveType.leave_name', orderable: false, className: 'text-center' },
                    { data: 'from_display', name: 'from_date', className: 'text-center' },
                    { data: 'to_display', name: 'to_date', className: 'text-center' },
                    { data: 'days_display', name: 'number_of_days', className: 'text-center' },
                    { data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: []
            });
        }

        $('#consolidatedRoleFilter').on('change', function () {
            loadEmployeeFilterOptions('consolidated');
        });

        $('#filterConsolidatedReport').on('click', function () {
            initConsolidatedTable();
        });

        $('#resetConsolidatedReport').on('click', function () {
            $('#consolidatedFromDateFilter, #consolidatedToDateFilter').val('');
            $('#consolidatedLeaveTypeFilter, #consolidatedRoleFilter, #consolidatedStatusFilter').val('').trigger('change');
            loadEmployeeFilterOptions('consolidated');
            if (consolidatedTable) {
                consolidatedTable.destroy();
                consolidatedTable = null;
                $('#consolidatedLeaveTable tbody').empty();
            }
        });

        $('#downloadConsolidatedReport').on('click', function () {
            var query = $.param(consolidatedFilters());
            window.location.href = "{{ route('leaves.consolidated-report.pdf') }}" + (query ? '?' + query : '');
        });

        function currentType() {
            return $('#leaveTabs .nav-link.active').data('table-type') || 'all';
        }

        function reloadCurrentTable() {
            tables[currentType()].ajax.reload();
        }

        function reloadAllTables() {
            Object.keys(tables).forEach(function (type) {
                tables[type].ajax.reload();
            });
        }
    });

    function deleteLeave(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you really want to delete this leave?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/leaves/' + id,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function () {
                    $('.leave-table').each(function () {
                        $(this).DataTable().ajax.reload();
                    });
                    showToast('success', 'Leave deleted successfully.');
                },
                error: function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Something went wrong');
                }
            });
        });
    }
</script>
