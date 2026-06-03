<script>
    $(function () {
        $('.select2').select2({ placeholder: 'Select an option', allowClear: true, width: '100%' });

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
            $('#trip_sheet_entry_id').val('');
            $('#tripLabel').val('');
            $('#driver_profile_id, #vehicle_id').val('').trigger('change');
        });

        $(document).on('click', '.choose-trip-entry', function () {
            applyTrip($(this).data());
            $('#tripSelectModal').modal('hide');
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
            q: $('#tripSearchInput').val()
        }).done(function (rows) {
            body.empty();

            if (!rows.length) {
                body.html('<div class="trip-result-state text-muted">No trips found for this duty date.</div>');
                return;
            }

            rows.forEach(function (row) {
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
                            '<button type="button" class="btn btn-sm btn-primary choose-trip-entry" ' +
                            'data-id="' + row.id + '" ' +
                            'data-label="' + escapeAttribute((row.sheet_code || '') + ' - ' + (row.trip_title || '')) + '" ' +
                            'data-driver="' + (row.driver_profile_id || '') + '" ' +
                            'data-vehicle="' + (row.vehicle_id || '') + '">Select</button>' +
                        '</div>' +
                    '</div>'
                );
            });
        }).fail(function () {
            body.html('<div class="trip-result-state text-danger">Unable to load trips.</div>');
        });
    }

    function applyTrip(row) {
        $('#trip_sheet_entry_id').val(row.id || '');
        $('#tripLabel').val(row.label || '');
        $('#driver_profile_id').val(row.driver || '').trigger('change');
        $('#vehicle_id').val(row.vehicle || '').trigger('change');
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
