<script>
    $(function () {
        $('.js-loading-form').on('submit', function (event) {
            if ($(this).data('submitting')) { event.preventDefault(); return; }
            $(this).data('submitting', true).attr('aria-busy', 'true');
            $(this).find('.js-loading-submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Importing…');
            $(this).find('.import-loading-message').prop('hidden', false);
        });
        $(window).on('pageshow', function () {
            $('.js-loading-form').removeData('submitting').removeAttr('aria-busy');
            $('.js-loading-submit').prop('disabled', false).text('Import CSV / Excel');
            $('.import-loading-message').prop('hidden', true);
        });
    });
</script>
