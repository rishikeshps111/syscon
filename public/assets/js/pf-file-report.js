(function ($) {
    $(function () {
        var form = document.getElementById('pfFileForm');
        if (!form) return;
        var button = $(form).find('button[type="submit"]');
        var original = button.html();
        var modal = new bootstrap.Modal(document.getElementById('pfFileModal'));
        var body = $('#pfFileBody');
        var download = $('#downloadPfExcel');
        var active = null;
        $(form).find('select').select2({ width: '100%' });
        function setDownload(url) {
            download.attr('href', url || '#').attr('aria-disabled', url ? 'false' : 'true').toggleClass('disabled', !url);
        }
        function invalidate() {
            if (active) { active.abort(); active = null; }
            setDownload(null);
            body.text('Generate a report to view details.');
        }
        $(form).find('select').on('change', invalidate);
        $(form).on('submit', function (event) {
            event.preventDefault();
            if (button.prop('disabled')) return;
            setDownload(null);
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Generating...');
            body.text('Generating PF file...');
            modal.show();
            active = $.get(form.action, $(form).serialize()).done(function (response) {
                body.html(response.html);
                setDownload(response.success ? response.download_excel_url : null);
            }).fail(function (xhr, status) {
                if (status !== 'abort') body.text(xhr.responseJSON?.message || 'Unable to generate PF file.');
                setDownload(null);
            }).always(function () {
                button.prop('disabled', false).html(original);
                active = null;
            });
        });
        $('#resetPfFilters').on('click', function () {
            invalidate();
            $('#pf_month, #pf_depot, #pf_role').val('').trigger('change.select2');
            $('#pf_year').val(form.dataset.defaultYear).trigger('change.select2');
        });
        download.on('click', function (event) {
            if (download.attr('aria-disabled') === 'true') event.preventDefault();
        });
        if (body.attr('data-generated') === '1') modal.show();
    });
})(jQuery);
