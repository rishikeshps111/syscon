<script>
    $(function () {
        $('#depotFilter, #stateFilter, #districtFilter').select2({
            placeholder: '---Select---',
            allowClear: true,
            width: '100%'
        });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: "{{ route('controller-management.index') }}",
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
                { data: 'depot', name: 'controllerProfile.depot.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'employment_type', name: 'controllerProfile.employment_type', orderable: false, searchable: false, className: 'text-center' },
                { data: 'location', name: 'controllerProfile.location.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'date_of_joining', name: 'controllerProfile.date_of_joining', orderable: false, searchable: false, className: 'text-center' },
                { data: 'gross_salary', name: 'controllerProfile.gross_salary', orderable: false, searchable: false, className: 'text-center' },
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

        $('#stateFilter').on('change', function () {
            if (isResettingFilters) {
                return;
            }

            let stateId = $(this).val();
            resetDistrictFilter('Loading...');

            if (!stateId) {
                resetDistrictFilter('---Select---');
                reloadTable();
                return;
            }

            $.ajax({
                url: "{{ route('branch-locations.districts-by-state') }}",
                type: 'GET',
                data: { state_id: stateId },
                success: function (districts) {
                    let options = '<option value="">---Select---</option>';
                    districts.forEach(function (district) {
                        options += `<option value="${district.id}">${district.name}</option>`;
                    });
                    $('#districtFilter').html(options).prop('disabled', false).val('').trigger('change.select2');
                    reloadTable();
                },
                error: function () {
                    resetDistrictFilter('---Select---');
                    showToast('error', 'Unable to load districts.');
                    reloadTable();
                }
            });
        });

        $('#resetFilters').on('click', function () {
            isResettingFilters = true;
            $('#depotFilter, #employmentTypeFilter, #stateFilter, #districtFilter, #statusFilter').val('');
            resetDistrictFilter('---Select---');
            $('#depotFilter, #stateFilter, #districtFilter').trigger('change.select2');
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
                url: "{{ route('controller-management.status') }}",
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
                url: "{{ route('controller-management.export') }}",
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
                    a.download = 'controller-management.xlsx';
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
            data.employment_type = $('#employmentTypeFilter').val();
            data.state_id = $('#stateFilter').val();
            data.district_id = $('#districtFilter').val();
            data.status = $('#statusFilter').val();
            data.date_of_joining = $('#dateOfJoiningFilter').val();
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }

        function resetDistrictFilter(label) {
            $('#districtFilter')
                .html(`<option value="">${label}</option>`)
                .prop('disabled', label === 'Loading...')
                .val('')
                .trigger('change.select2');
        }
    });

    function deleteRow(id) {
        deleteRecord('/controller-management/' + id, 'table', 'Do you really want to delete this controller?');
    }
</script>

