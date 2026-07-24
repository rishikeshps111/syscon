<script>
    $(function () {
        $('.select2').select2({
            placeholder: 'Select an option',
            allowClear: true,
            width: '100%'
        });

        refreshRoutePreview();
        toggleCancellationReason();
        toggleDepotFields();

        $('#route_id').on('change', refreshRoutePreview);
        $('#status').on('change', toggleCancellationReason);
        $('#trip_side').on('change', toggleDepotFields);

        $('#commonForm').on('submit', function () {
            var submitBtn = $(this).find('.trip-submit-btn');
            var loadingText = submitBtn.text().trim() === 'Update' ? 'Updating...' : 'Creating...';

            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + loadingText
            );
        });
    });

    function refreshRoutePreview() {
        var option = $('#route_id').find(':selected');
        $('#startPointPreview').val(option.data('start') || '');
        $('#endPointPreview').val(option.data('end') || '');

        var stops = option.data('stops') || [];
        var list = $('#stopsPreview');
        list.empty();

        if (!Array.isArray(stops) || stops.length === 0) {
            list.append('<span class="btn btn-sm btn-light text-muted disabled">No stops selected</span>');
            return;
        }

        stops.forEach(function (stop, index) {
            if (index > 0) {
                list.append('<span class="d-inline-flex align-items-center text-muted"><i class="fa-solid fa-arrow-right"></i></span>');
            }

            list.append($('<span class="btn btn-sm btn-outline-secondary disabled">').text(stop));
        });
    }

    function toggleCancellationReason() {
        $('#cancellationReasonWrap').toggle($('#status').val() === 'Cancelled');
    }

    function toggleDepotFields() {
        var tripSide = $('#trip_side').val();
        var usesSingleDepot = tripSide === 'both';
        var usesDirectionalDepots = tripSide === 'up' || tripSide === 'down';

        $('#singleDepotWrap').toggle(usesSingleDepot);
        $('.directional-depot-wrap').toggle(usesDirectionalDepots);

        $('#depot_id').prop('required', usesSingleDepot);
        $('#from_depot_id, #to_depot_id').prop('required', usesDirectionalDepots);
    }
</script>
