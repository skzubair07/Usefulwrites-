(function ($) {
    'use strict';

    function trackClick() {
        if (!window.wsbData || !wsbData.ajaxUrl || !wsbData.nonce) {
            return;
        }

        $.post(wsbData.ajaxUrl, {
            action: 'wsb_track_click',
            nonce: wsbData.nonce
        });
    }

    $(function () {
        var $wrapper = $('.wsb-wrapper');
        if (!$wrapper.length) {
            return;
        }

        var $popup = $('#wsb-popup');
        var popupEnabled = $wrapper.data('popup-enabled') === 1 || $wrapper.data('popup-enabled') === '1';
        var popupDelay = parseInt($wrapper.data('popup-delay'), 10);

        if (Number.isNaN(popupDelay) || popupDelay < 0) {
            popupDelay = 0;
        }

        if (popupEnabled && $popup.length) {
            setTimeout(function () {
                $popup.addClass('is-visible').attr('aria-hidden', 'false');
            }, popupDelay * 1000);

            $popup.on('click', '.wsb-popup-close', function () {
                $popup.removeClass('is-visible').attr('aria-hidden', 'true');
            });
        }

        $(document).on('click', '.wsb-track-click', function () {
            trackClick();
        });
    });
})(jQuery);
