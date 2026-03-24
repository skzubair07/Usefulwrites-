(function($){
    'use strict';

    $(document).on('change', '#cwbp-select-all', function(){
        $('input[name="contact_ids[]"]').prop('checked', $(this).is(':checked'));
    });
})(jQuery);
