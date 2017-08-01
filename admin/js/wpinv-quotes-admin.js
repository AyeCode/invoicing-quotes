(function ($) {
    'use strict';

    $(document).ready(function () {
        var $postForm = $('.post-type-wpi_quote form#post');
        if ($('[name="wpinv_status"]', $postForm).length) {
            var origStatus = $('[name="wpinv_status"]', $postForm).val();
            $('[name="original_post_status"]', $postForm).val(origStatus);
            $('[name="hidden_post_status"]', $postForm).val(origStatus);
            $('[name="post_status"]', $postForm).replaceWith('<input type="hidden" value="' + origStatus + '" id="post_status" name="post_status">');
            $postForm.on('change', '[name="wpinv_status"]', function(e) {
                e.preventDefault();
                $('[name="post_status"]', $postForm).replaceWith('<input type="hidden" value="' + $(this).val() + '" id="post_status" name="post_status">');
            });
        }

        var invDetails = jQuery('#gdmbx2-metabox-wpinv_details').html();
        if (invDetails) {
            jQuery('#submitpost', jQuery('.wpinv')).detach().appendTo(jQuery('#wpinv-details'));
            jQuery('#submitdiv', jQuery('.wpinv')).hide();
            jQuery('.post-type-wpi_quote #major-publishing-actions').find('input[type=submit]').attr('name', 'save_invoice').val(wpinv_quotes_admin.save_quote);
        }

        $('.post-type-wpi_quote [name="post"] #submitpost [type="submit"]').on('click', function(e) {
            if (parseInt($(document.body).find('.wpinv-line-items > .item').length) < 1) {
                alert(WPInv_Admin.emptyInvoice);
                $('#wpinv_invoice_item').focus();
                return false;
            }
        });

        $( "#wpinv_convert_quote" ).click(function() {
            if( ! confirm( wpinv_quotes_admin.convert_quote ) ) {
                return false;
            }
        });

    });

})(jQuery);
