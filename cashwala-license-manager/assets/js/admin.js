(function ($) {
    'use strict';

    $(document).on('click', '.cwlmp-copy-keys', function () {
        const target = $($(this).data('target'));
        if (!target.length) {
            return;
        }

        target[0].select();
        document.execCommand('copy');
    });
})(jQuery);
