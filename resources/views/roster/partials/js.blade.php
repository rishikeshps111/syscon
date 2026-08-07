<script>
    $(function () {
        $('.select2-filter').select2({ placeholder: '---Select---', allowClear: true, width: '100%' });

        var generated = false;
        var table = $('#generatedRosterTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            paging: false,
            info: false,
            lengthChange: false,
            ajax: { url: "{{ route('rosters.index') }}", data: filters },
            columns: [
                // { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'code', name: 'code', className: 'text-center' },
                { data: 'date', name: 'duty_date', className: 'text-center' },
                { data: 'depot_name', name: 'depot.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'shift_type_label', name: 'shift_type', className: 'text-center' },
                { data: 'driver_name', name: 'driverProfile.user.name', orderable: false, searchable: false, className: 'text-center' },
                { data: 'vehicle_no', name: 'vehicle.vehicle_no', orderable: false, searchable: false, className: 'text-center' },
                { data: 'trip_code', name: 'tripSheetEntries.sheet.code', orderable: false, searchable: false, className: 'text-center' },
                { data: 'reporting_time_label', name: 'reporting_time', className: 'text-center' },
                // { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                // { data: 'attendance_status', name: 'attendance_status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']]
        });

        $('#generateRoster').on('click', function () {
            if (!validateGenerateFilters()) return;
            generated = true;
            $('#generatedRosterModal').modal('show');
            reloadTable();
        });

        $('#exportGeneratedRoster').on('click', downloadGeneratedRoster);

        $('#generatedRosterModal').on('shown.bs.modal', function () {
            table.columns.adjust();
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
            $('#reassignDriverForm').data('availability-url', $(this).data('availability-url'));
            $('#modalDriver').val($(this).data('driver'));
            markSelectedCard('#driverCardList', $(this).data('driver'));
            refreshReassignAvailability('#driverCardList', 'driver', $(this).data('availability-url'));
            $('#driverCardSearch').val('');
            filterCards('#driverCardList', '');
            $('#reassignDriverModal').modal('show');
        });

        $(document).on('click', '.reassign-vehicle-btn', function () {
            $('#reassignVehicleForm').data('url', $(this).data('url'));
            $('#reassignVehicleForm').data('availability-url', $(this).data('availability-url'));
            $('#modalVehicle').val($(this).data('vehicle'));
            markSelectedCard('#vehicleCardList', $(this).data('vehicle'));
            refreshReassignAvailability('#vehicleCardList', 'vehicle', $(this).data('availability-url'));
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
                showToast('warning', 'Driver already associated with another active roaster in this time slot.');
                return;
            }

            $('#modalDriver').val($(this).data('id'));
            markSelectedCard('#driverCardList', $(this).data('id'));
        });

        $(document).on('click', '.vehicle-card', function () {
            if ($(this).data('assigned') == 1 && !$(this).hasClass('is-selected')) {
                showToast('warning', 'Vehicle already associated with another active roaster in this time slot.');
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
            data.depot_id = $('#depotFilter').val();
            data.date_from = $('#dateFromFilter').val();
            data.date_to = $('#dateToFilter').val();
            data.generate = generated ? 1 : 0;
        }

        function exportPayload(ids) {
            return {
                _token: "{{ csrf_token() }}",
                ids: ids,
                depot_id: $('#depotFilter').val(),
                date_from: $('#dateFromFilter').val(),
                date_to: $('#dateToFilter').val(),
                generate: 1
            };
        }

        function validateGenerateFilters() {
            var from = $('#dateFromFilter').val();
            var to = $('#dateToFilter').val();
            if (!$('#depotFilter').val() || !from || !to) {
                showToast('warning', 'Please select depot, from date and to date.');
                return false;
            }
            if (to < from) {
                showToast('warning', 'To date must be on or after from date.');
                return false;
            }
            return true;
        }

        function downloadGeneratedRoster() {
            var button = $('#exportGeneratedRoster');
            var originalContent = button.html();

            button.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Exporting...'
            );

            $.ajax({
                url: "{{ route('rosters.export') }}",
                type: 'POST',
                data: exportPayload([]),
                xhrFields: { responseType: 'blob' },
                success: function (data) {
                    var url = window.URL.createObjectURL(new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'roasters.xlsx';
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    link.remove();
                },
                error: function () { showToast('error', 'Export failed.'); },
                complete: function () {
                    button.prop('disabled', false).html(originalContent);
                }
            });
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

        function refreshReassignAvailability(listSelector, type, url) {
            if (!url) {
                markUnavailableCards(listSelector, []);
                return;
            }

            $.get(url)
                .done(function (response) {
                    markUnavailableCards(listSelector, type === 'driver' ? (response.driver_ids || []) : (response.vehicle_ids || []));
                })
                .fail(function () {
                    showToast('error', 'Unable to load assignment availability.');
                });
        }

        function markUnavailableCards(listSelector, ids) {
            var unavailable = ids.map(String);

            $(listSelector).find('.assignment-card').each(function () {
                var card = $(this);
                var blocked = unavailable.indexOf(String(card.data('id'))) !== -1;
                var expired = card.data('expired') == 1;

                card.data('assigned', blocked ? 1 : 0);
                card.attr('data-assigned', blocked ? 1 : 0);
                card.toggleClass('is-disabled', expired || blocked);
            });
        }
    });

    function deleteRow(id) {
        deleteRecord('/rosters/' + id, 'table', 'Do you really want to delete this roster?');
    }
</script>
