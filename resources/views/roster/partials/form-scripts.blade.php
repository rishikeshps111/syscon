<script>
    var selectedTrips = @json($selectedTrips ?? []);
    var rosterAvailabilityUrl = "{{ isset($record) ? route('rosters.availability.roster', $record->id) : route('rosters.availability') }}";

    $(function () {
        $('.select2').select2({ placeholder: 'Select an option', allowClear: true, width: '100%' });
        renderSelectedTrips();
        refreshAvailability();

        $('#openTripModal').on('click', function () {
            if (!$('#duty_date').val()) {
                showToast('warning', 'Please select duty date first.');
                return;
            }

            $('#tripPickerDutyDate').text($('#duty_date').val());
            $('#tripSelectModal').modal('show');
            searchTrips();
        });

        $('#tripSearchBtn').on('click', searchTrips);
        $('#tripSearchInput').on('keyup', function () {
            clearTimeout(window.tripSearchTimer);
            window.tripSearchTimer = setTimeout(searchTrips, 300);
        });

        $('#duty_date').on('change', function () {
            $('#tripLabel').val('');
            selectedTrips = [];
            renderSelectedTrips();
            $('#driver_profile_id, #vehicle_id').val('').trigger('change');
            refreshAvailability();
        });

        $('#shift_start_time, #shift_end_time').on('change', refreshAvailability);

        $(document).on('click', '.choose-trip-entry', function () {
            applyTrip($(this).data());
            searchTrips();
        });

        $(document).on('click', '.remove-selected-trip', function () {
            var id = String($(this).data('id'));
            selectedTrips = selectedTrips.filter(function (row) {
                return String(row.id) !== id;
            });
            renderSelectedTrips();
        });

        $('#commonForm').on('submit', function () {
            var submitBtn = $(this).find('.roster-submit-btn');
            var loadingText = submitBtn.text().trim() === 'Update' ? 'Updating...' : 'Creating...';
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText
            );
        });
    });

    function searchTrips() {
        var body = $('#tripSearchResults');
        body.html('<div class="trip-result-state text-muted">Searching trips...</div>');

        $.get("{{ route('rosters.trip-entries') }}", {
            duty_date: $('#duty_date').val(),
            q: $('#tripSearchInput').val(),
            selected_ids: selectedTrips.map(function (row) {
                return row.id;
            })
        }).done(function (rows) {
            body.empty();

            if (!rows.length) {
                body.html('<div class="trip-result-state text-muted">No trips found for this duty date.</div>');
                return;
            }

            rows.forEach(function (row) {
                var selected = selectedTrips.some(function (trip) {
                    return String(trip.id) === String(row.id);
                });

                body.append(
                    '<div class="trip-result-card">' +
                        '<div>' +
                            '<div class="trip-result-code">' + escapeHtml(row.sheet_code || '-') + '</div>' +
                            '<div class="trip-result-title">' + escapeHtml(row.trip_title || 'Untitled trip') + '</div>' +
                            '<div class="trip-result-info">' +
                                '<span><i class="fa-solid fa-route"></i>' + escapeHtml(row.trip_code || '-') + '</span>' +
                                '<span><i class="fa-solid fa-arrows-left-right"></i>' + escapeHtml(row.side || '-') + '</span>' +
                                '<span><i class="fa-solid fa-user"></i>' + escapeHtml(row.driver_name || 'No driver assigned') + '</span>' +
                                '<span><i class="fa-solid fa-bus"></i>' + escapeHtml(row.vehicle_no || 'No vehicle assigned') + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="text-end">' +
                            '<button type="button" class="btn btn-sm ' + (selected ? 'btn-secondary' : 'btn-primary') + ' choose-trip-entry" ' +
                            (selected ? 'disabled ' : '') +
                            'data-id="' + row.id + '" ' +
                            'data-label="' + escapeAttribute((row.sheet_code || '') + ' - ' + (row.trip_title || '')) + '" ' +
                            'data-side="' + escapeAttribute(row.side || '') + '" ' +
                            'data-driver="' + (row.driver_profile_id || '') + '" ' +
                            'data-vehicle="' + (row.vehicle_id || '') + '">' + (selected ? 'Selected' : 'Select') + '</button>' +
                        '</div>' +
                    '</div>'
                );
            });
        }).fail(function () {
            body.html('<div class="trip-result-state text-danger">Unable to load trips.</div>');
        });
    }

    function applyTrip(row) {
        if (selectedTrips.some(function (trip) { return String(trip.id) === String(row.id); })) {
            showToast('warning', 'Trip sheet entry already selected.');
            return;
        }

        selectedTrips.push({
            id: row.id || '',
            label: row.label || '',
            side: row.side || '',
            driver: row.driver || '',
            vehicle: row.vehicle || ''
        });

        renderSelectedTrips();

        if (selectedTrips.length === 1) {
            $('#driver_profile_id').val(row.driver || '').trigger('change');
            $('#vehicle_id').val(row.vehicle || '').trigger('change');
            refreshAvailability();
        }
    }

    function refreshAvailability() {
        var payload = availabilityPayload();

        if (!payload.duty_date || !payload.shift_start_time || !payload.shift_end_time) {
            applyAvailability([], []);
            return;
        }

        $.get(rosterAvailabilityUrl, payload)
            .done(function (response) {
                applyAvailability(response.driver_ids || [], response.vehicle_ids || []);
            })
            .fail(function () {
                showToast('error', 'Unable to load driver and vehicle availability.');
            });
    }

    function availabilityPayload() {
        return {
            duty_date: $('#duty_date').val(),
            shift_start_time: $('#shift_start_time').val(),
            shift_end_time: $('#shift_end_time').val()
        };
    }

    function applyAvailability(driverIds, vehicleIds) {
        applySelectAvailability('#driver_profile_id', driverIds, ' - Already Associated in this Time Slot');
        applySelectAvailability('#vehicle_id', vehicleIds, ' - Already Associated in this Time Slot');
    }

    function applySelectAvailability(selector, unavailableIds, suffix) {
        var unavailable = unavailableIds.map(String);
        var select = $(selector);
        var selected = String(select.val() || '');
        var shouldClear = false;

        select.find('option').each(function () {
            var option = $(this);
            var value = String(option.val() || '');

            if (!value) {
                return;
            }

            var baseLabel = option.data('base-label') || option.text().replace(/ - .*/, '');
            var expired = option.data('expired') == 1;
            var unavailableForSlot = unavailable.indexOf(value) !== -1;

            option.text(baseLabel + (expired ? ' - Licence Expired' : (unavailableForSlot ? suffix : '')));
            option.prop('disabled', expired || unavailableForSlot);

            if (selected === value && (expired || unavailableForSlot)) {
                shouldClear = true;
            }
        });

        if (shouldClear) {
            select.val('');
            showToast('warning', 'Selected driver or vehicle is not available for this time slot.');
        }

        select.trigger('change.select2');
    }

    function renderSelectedTrips() {
        var list = $('#selectedTripList');
        var inputs = $('#selectedTripInputs');
        list.empty();
        inputs.empty();

        selectedTrips.forEach(function (row) {
            inputs.append('<input type="hidden" name="trip_sheet_entry_ids[]" value="' + escapeAttribute(row.id) + '">');
            list.append(
                '<div class="selected-trip-pill" data-id="' + escapeAttribute(row.id) + '" data-side="' + escapeAttribute(row.side || '') + '">' +
                    '<span>' + escapeHtml(row.label || '-') + ' <small>(' + escapeHtml(row.side || '-') + ')</small></span>' +
                    '<button type="button" class="remove-selected-trip" data-id="' + escapeAttribute(row.id) + '">x</button>' +
                '</div>'
            );
        });

        if (!selectedTrips.length) {
            list.append('<div class="selected-trip-empty">No trip sheet entries selected yet.</div>');
        }

        $('#tripLabel').val(selectedTrips.length ? selectedTrips.length + (selectedTrips.length === 1 ? ' trip selected' : ' trips selected') : 'No trip selected');
        toggleReportingToTime();
    }

    function toggleReportingToTime() {
        var sides = selectedTrips.map(function (row) {
            return String(row.side || '').toLowerCase();
        });
        var hasBoth = sides.includes('up') && sides.includes('down');
        $('#reportingToTimeWrap').toggle(hasBoth);
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }
</script>
