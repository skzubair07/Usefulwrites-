(function ($) {
    'use strict';

    function getQueryParam(name) {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }

    function track(action, funnelId, step) {
        if (!window.CWFB || !funnelId || !step) {
            return;
        }

        $.post(window.CWFB.ajaxUrl, {
            action: action,
            nonce: window.CWFB.nonce,
            funnel_id: funnelId,
            step: step
        });
    }

    $(function () {
        var funnelId = parseInt(getQueryParam('cwfunnel'), 10);
        if (!funnelId) {
            return;
        }

        var bodyClass = document.body.className;
        var step = '';

        if (bodyClass.indexOf('cwfb-step-landing') !== -1) {
            step = 'landing';
        } else if (bodyClass.indexOf('cwfb-step-checkout') !== -1) {
            step = 'checkout';
        } else if (bodyClass.indexOf('cwfb-step-thankyou') !== -1) {
            step = 'thankyou';
        }

        if (!step) {
            var path = window.location.pathname.toLowerCase();
            if (path.indexOf('thank') !== -1) {
                step = 'thankyou';
            } else if (path.indexOf('checkout') !== -1) {
                step = 'checkout';
            } else {
                step = 'landing';
            }
        }

        track('cwfb_track_visit', funnelId, step);

        if (step === 'thankyou') {
            track('cwfb_track_conversion', funnelId, step);
        }
    });
})(jQuery);
