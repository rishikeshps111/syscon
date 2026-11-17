(function ($) {
    $(function () {
        var form = document.querySelector('.js-update-sample');
        if (!form) return;
        var scope = form.querySelector('[name="sample_scope"]');
        var picker = form.querySelector('.js-update-user-picker');
        var users = form.querySelector('[name="user_ids[]"]');
        var error = form.querySelector('.js-update-selection-error');
        $(users).select2({
            width: '100%',
            placeholder: 'Search name, employee code or Ref Code',
            minimumInputLength: 0,
            ajax: {
                url: users.dataset.searchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term || '', page: params.page || 1 }; },
                processResults: function (result) { return result; }
            }
        });
        function updateScope() {
            var selected = scope.value === 'selected';
            picker.hidden = !selected;
            users.disabled = !selected;
            error.hidden = true;
        }
        scope.addEventListener('change', updateScope);
        $(users).on('change', function () { error.hidden = true; });
        form.addEventListener('submit', function (event) {
            if (scope.value === 'selected' && !users.selectedOptions.length) {
                event.preventDefault();
                error.hidden = false;
                $(users).select2('open');
            }
        });
        updateScope();
    });
})(jQuery);
