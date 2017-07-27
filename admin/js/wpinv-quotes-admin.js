(function ($) {
    'use strict';

    $(document).ready(function () {
        var invDetails = jQuery('#gdmbx2-metabox-wpinv_details').html();
        if (invDetails) {
            jQuery('#submitpost', jQuery('.wpinv')).detach().appendTo(jQuery('#wpinv-details'));
            jQuery('#submitdiv', jQuery('.wpinv')).hide();
            jQuery('.post-type-wpi_quote #major-publishing-actions').find('input[type=submit]').attr('name', 'save_invoice').val(wpinv_quotes_admin.save_quote);
        }
    });

})(jQuery);
