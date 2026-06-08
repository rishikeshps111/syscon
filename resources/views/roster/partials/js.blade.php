<script>
    $(function () {
        $('.select2-filter').select2({ placeholder: '---Select---', allowClear: true, width: '100%' });

        var table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: { url: "{{ route('rosters.index') }}", data: filters },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'date', name: 'duty_date', className: 'text-center' },
                { data: 'shift_type_label', name: 'shift_type', className: 'text-center' },
                { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                { data: 'trip_code', name: 'tripSheetEntries.sheet.code', orderable: false, searchable: false, className: 'text-center' },
                { data: 'reporting_time_label', name: 'reporting_time', className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'attendance_status', name: 'attendance_status', orderable: false, searchable: false, className: 'text-center' },
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
        $('#stateFilter, #oemFilter, #depotFilter, #driverFilter, #dateFromFilter, #dateToFilter, #shiftTypeFilter, #statusFilter').on('change', reloadTable);

        $('#resetFilters').on('click', function () {
            $('#stateFilter, #oemFilter, #depotFilter, #driverFilter, #dateFromFilter, #dateToFilter, #shiftTypeFilter, #statusFilter, #searchFilter').val('');
            $('.select2-filter').trigger('change.select2');
            $('#checkAll').prop('checked', false);
            reloadTable();
        });

        $('#checkAll').on('change', function () {
            $('.row-check').prop('checked', this.checked);
        });

        $(document).on('click', '.roster-status-btn', function () {
            $('#statusRosterId').val($(this).data('id'));
            $('#modalStatus').val($(this).data('status'));
            $('#statusModal').modal('show');
        });

        $(document).on('click', '.attendance-btn', function () {
            $('#attendanceRosterId').val($(this).data('id'));
            $('#modalAttendance').val($(this).data('attendance') || 'present');
            $('#attendanceModal').modal('show');
        });

        $(document).on('click', '.reassign-driver-btn', function () {
            $('#reassignDriverForm').data('url', $(this).data('url'));
            $('#modalDriver').val($(this).data('driver'));
            markSelectedCard('#driverCardList', $(this).data('driver'));
            $('#driverCardSearch').val('');
            filterCards('#driverCardList', '');
            $('#reassignDriverModal').modal('show');
        });

        $(document).on('click', '.reassign-vehicle-btn', function () {
            $('#reassignVehicleForm').data('url', $(this).data('url'));
            $('#modalVehicle').val($(this).data('vehicle'));
            markSelectedCard('#vehicleCardList', $(this).data('vehicle'));
            $('#vehicleCardSearch').val('');
            filterCards('#vehicleCardList', '');
            $('#reassignVehicleModal').modal('show');
        });

        $('#driverCardSearch').on('keyup', function () {
            filterCards('#driverCardList', $(this).val());
        });

        $('#vehicleCardSearch').on('keyup', function () {
            filterCards('#vehicleCardList', $(this).val());
        });

        $(document).on('click', '.driver-card', function () {
            if ($(this).data('expired') == 1) {
                showToast('warning', 'Licence expired driver cannot be selected.');
                return;
            }

            if ($(this).data('assigned') == 1 && !$(this).hasClass('is-selected')) {
                showToast('warning', 'Driver already associated with another roaster.');
                return;
            }

            $('#modalDriver').val($(this).data('id'));
            markSelectedCard('#driverCardList', $(this).data('id'));
        });

        $(document).on('click', '.vehicle-card', function () {
            if ($(this).data('assigned') == 1 && !$(this).hasClass('is-selected')) {
                showToast('warning', 'Vehicle already associated with another roaster.');
                return;
            }

            $('#modalVehicle').val($(this).data('id'));
            markSelectedCard('#vehicleCardList', $(this).data('id'));
        });

        $('#statusForm').on('submit', function (e) {
            e.preventDefault();
            submitModal($(this), "{{ route('rosters.status') }}", '#statusModal');
        });

        $('#attendanceForm').on('submit', function (e) {
            e.preventDefault();
            submitModal($(this), "{{ route('rosters.attendance') }}", '#attendanceModal');
        });

        $('#reassignDriverForm, #reassignVehicleForm').on('submit', function (e) {
            e.preventDefault();
            var modal = this.id === 'reassignDriverForm' ? '#reassignDriverModal' : '#reassignVehicleModal';
            submitModal($(this), $(this).data('url'), modal);
        });

        $('#exportSelected').on('click', function () {
            let selectedIds = [];
            $('.row-check:checked').each(function () { selectedIds.push($(this).val()); });

            if (selectedIds.length === 0) {
                showToast('warning', 'Please select at least one row to export.');
                return;
            }

            $.ajax({
                url: "{{ route('rosters.export') }}",
                type: 'POST',
                data: exportPayload(selectedIds),
                xhrFields: { responseType: 'blob' },
                success: function (data) {
                    let blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'roasters.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    $('#checkAll, .row-check').prop('checked', false);
                    showToast('success', 'Export completed successfully.');
                },
                error: function () { showToast('error', 'Export failed.'); }
            });
        });

        function submitModal(form, url, modal) {
            var button = form.find('.modal-submit-btn');
            button.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    $(modal).modal('hide');
                    table.ajax.reload();
                    showToast('success', response.message);
                },
                error: function (xhr) {
                    showToast('error', xhr.responseJSON?.message || 'Something went wrong');
                },
                complete: function () {
                    button.prop('disabled', false);
                }
            });
        }

        function filters(data) {
            data.search_text = $('#searchFilter').val();
            data.state_id = $('#stateFilter').val();
            data.oem_id = $('#oemFilter').val();
            data.depot_id = $('#depotFilter').val();
            data.driver_profile_id = $('#driverFilter').val();
            data.date_from = $('#dateFromFilter').val();
            data.date_to = $('#dateToFilter').val();
            data.shift_type = $('#shiftTypeFilter').val();
            data.status = $('#statusFilter').val();
        }

        function exportPayload(ids) {
            return {
                _token: "{{ csrf_token() }}",
                ids: ids,
                search_text: $('#searchFilter').val(),
                state_id: $('#stateFilter').val(),
                oem_id: $('#oemFilter').val(),
                depot_id: $('#depotFilter').val(),
                driver_profile_id: $('#driverFilter').val(),
                date_from: $('#dateFromFilter').val(),
                date_to: $('#dateToFilter').val(),
                shift_type: $('#shiftTypeFilter').val(),
                status: $('#statusFilter').val()
            };
        }

        function reloadTable() {
            $('#checkAll').prop('checked', false);
            table.ajax.reload();
        }

        function markSelectedCard(listSelector, id) {
            $(listSelector).find('.assignment-card').removeClass('is-selected');
            $(listSelector).find('.assignment-card').each(function () {
                $(this).toggleClass('is-selected', String($(this).data('id')) === String(id));
            });
        }

        function filterCards(listSelector, search) {
            var value = String(search || '').toLowerCase();
            $(listSelector).find('.assignment-card').each(function () {
                $(this).toggle(!value || String($(this).data('search') || '').indexOf(value) !== -1);
            });
        }
    });

    function deleteRow(id) {
        deleteRecord('/rosters/' + id, 'table', 'Do you really want to delete this roster?');
    }
</script>
