<script>
    $(function () {
        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('salary-components.index') }}",
                data: function (data) {
                    data.role_id = $('#roleFilter').val();
                    data.designation_id = $('#designationFilter').val();
                }
            },
            columns: [
                {
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'role_name', name: 'role_name', orderable: false, className: 'text-center' },
                { data: 'designation_name', name: 'designation_name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'component_name', name: 'component_name', className: 'text-center' },
                { data: 'type_label', name: 'type', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        });

        $('#roleFilter, #designationFilter').select2({ width: '100%', allowClear: true });

        var allDesignationOptions = $('#designationFilter option').clone();

        function filterDesignations() {
            var selectedRoleId = $('#roleFilter').val();
            var $designation = $('#designationFilter');
            var selectedDesignation = $designation.val();
            var selectedOptionRoleType = $designation.find('option:selected').data('role-type');
            var selectedRoleName = $('#roleFilter option:selected').data('role-name');
            var matchingOptions = allDesignationOptions.filter(function () {
                return !$(this).val() || (selectedRoleName && $(this).data('role-type') === selectedRoleName);
            }).clone();

            $('#designationFilterWrapper').removeClass('d-none');
            $designation.empty().append(matchingOptions);

            if (!selectedRoleId || (selectedOptionRoleType && selectedOptionRoleType !== selectedRoleName)) {
                selectedDesignation = '';
            }

            $designation.val(selectedDesignation).trigger('change.select2');
        }

        filterDesignations();

        $('#roleFilter').on('change', function () {
            filterDesignations();

            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#designationFilter').on('change', function () {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        });

        $('#resetFilters').on('click', function () {
            $('#roleFilter').val('').trigger('change.select2');
            $('#designationFilter').val('').trigger('change.select2');
            $('#designationFilterWrapper').removeClass('d-none');
            $('#checkAll').prop('checked', false);
            $('.row-check').prop('checked', false);
            table.ajax.reload();
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
                url: "{{ route('salary-components.export') }}",
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
                    a.download = 'salary-components.xlsx';
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
        deleteRecord('/salary-components/' + id, 'table', 'Do you really want to delete this salary component?');
    }
</script>
