<script>
    $(function () {
        $('.select2').select2({ placeholder: 'Select an option', allowClear: true, width: '100%' });

        var isEditing = @json(isset($record));
        refreshRouteDetails(!isEditing);

        $('#route_id').on('change', function () { refreshRouteDetails(true); });
        $('#rounds_per_trip').on('input change', calculateScheduleKm);

        $('#commonForm').on('submit', function () {
            var submitBtn = $(this).find('.trip-submit-btn');
            var loadingText = submitBtn.text().trim() === 'Update' ? 'Updating...' : 'Creating...';
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + loadingText);
        });
    });

    function refreshRouteDetails(applyDefaults) {
        var option = $('#route_id').find(':selected');

        $('#startPointPreview').val(option.data('start') || '');
        $('#endPointPreview').val(option.data('end') || '');
        $('#titlePreview').val(option.data('title') || '');

        if (applyDefaults && option.val()) {
            $('#state_id').val(String(option.attr('data-state-id') || '')).trigger('change.select2');
            $('#depot_id').val(String(option.attr('data-start-id') || '')).trigger('change.select2');
        }

        renderRouteStops(option);
        if (applyDefaults || !$('#schedule_km').val()) {
            calculateScheduleKm();
        }
    }

    function renderRouteStops(option) {
        var list = $('#stopsPreview').empty();
        var points = [];
        var start = option.data('start');
        var startShort = option.attr('data-start-short');
        var end = option.data('end');
        var endShort = option.attr('data-end-short');
        var stops = option.data('stops') || [];

        if (start) points.push({ name: startShort ? start + ' (' + startShort + ')' : start, label: 'Starting Depot' });
        if (Array.isArray(stops)) {
            stops.forEach(function (stop) {
                var label = stop.short_name ? stop.name + ' (' + stop.short_name + ')' : stop.name;
                points.push({ name: label, label: 'Stop' });
            });
        }
        if (end) points.push({ name: endShort ? end + ' (' + endShort + ')' : end, label: 'Ending Depot' });

        if (!points.length) {
            list.removeClass('route-stops-horizontal').append('<span class="text-muted">Select a route to preview its stops.</span>');
            return;
        }

        list.addClass('route-stops-horizontal');
        points.forEach(function (point, index) {
            if (index > 0) {
                list.append('<span class="route-stop-arrow"><i class="fa-solid fa-arrow-right"></i></span>');
            }
            var item = $('<div class="route-stop-item">');
            item.append($('<div class="fw-semibold">').text(point.name));
            item.append($('<small class="text-muted">').text(point.label));
            list.append(item);
        });
    }

    function calculateScheduleKm() {
        var distance = parseFloat($('#route_id').find(':selected').data('distance')) || 0;
        var rounds = parseInt($('#rounds_per_trip').val(), 10) || 0;
        $('#schedule_km').val((distance * 2 * rounds).toFixed(2));
    }
</script>
