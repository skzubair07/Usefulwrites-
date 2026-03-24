(function ($) {
    'use strict';

    if (typeof CWFunnel === 'undefined') {
        return;
    }

    function postTrack(action, step) {
        return $.post(CWFunnel.ajax_url, {
            action: action,
            nonce: CWFunnel.nonce,
            funnel_id: CWFunnel.funnel_id,
            step: step
        });
    }

    $(document).ready(function () {
        postTrack('cwfb_track_visit', CWFunnel.step);

        $(document).on('click', '.cwfb-next-step', function () {
            postTrack('cwfb_track_conversion', CWFunnel.step);
        });
    });
})(jQuery);
