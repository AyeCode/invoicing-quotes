(function ($) {
    'use strict';

    $(document).ready(function () {
        $('.post-type-wpi_quote form#post #titlediv [name="post_title"]').attr('readonly', true);

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

        var WPInv_Quote_Export = {
            init: function() {
                this.submit();
                this.clearMessage();
            },
            submit: function() {
                var $this = this;
                $('.wpi-quote-export-form').submit(function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    var submitBtn = $form.find('input[type="submit"]');
                    if (!submitBtn.attr('disabled')) {
                        var data = $form.serialize();
                        submitBtn.attr('disabled', true);
                        $form.find('.wpi-msg-wrap').remove();
                        $form.append('<div class="wpi-msg-wrap"><div class="wpi-progress"><div></div><span>0%</span></div><span class="wpi-export-loader"><i class="fa fa-spin fa-spinner"></i></span></div>');
                        // start the process
                        $this.step(1, data, $form, $this);
                    }
                });
            },
            step: function(step, data, $form, $this) {
                var message = $form.find('.wpi-msg-wrap');
                var post_data = {
                    action: 'wpinv_quote_ajax_export',
                    step: step,
                    data: data,
                };
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: post_data,
                    beforeSend: function(jqXHR, settings) {},
                    success: function(res) {
                        if (res && typeof res == 'object') {
                            if (res.success) {
                                if ('done' == res.data.step || res.data.done >= 100) {
                                    $form.find('input[type="submit"]').removeAttr('disabled');
                                    $('.wpi-progress > span').text(parseInt(res.data.done) + '%');
                                    $('.wpi-progress div').animate({
                                        width: res.data.done + '%'
                                    }, 100, function() {});
                                    if (res.msg) {
                                        message.html('<div id="wpi-export-success" class="updated notice is-dismissible"><p>' + msg + '<span class="notice-dismiss"></span></p></div>');
                                    }
                                    if (res.data.file && res.data.file.u) {
                                        message.append('<span class="wpi-export-file"><a href="' + res.data.file.u + '" target="_blank"><i class="fa fa-download"></i> ' + res.data.file.u + '</a><span> - ' + res.data.file.s + '<span><span>');
                                    }
                                    message.find('.wpi-export-loader').html('<i class="fa fa-check-circle"></i>');
                                } else {
                                    var next = parseInt(res.data.step) > 0 ? parseInt(res.data.step) : 1;
                                    $('.wpi-progress > span').text(parseInt(res.data.done) + '%');
                                    $('.wpi-progress div').animate({
                                        width: res.data.done + '%'
                                    }, 100, function() {});
                                    $this.step(parseInt(next), data, $form, $this);
                                }
                            } else {
                                $form.find('input[type="submit"]').removeAttr('disabled');
                                if (res.msg) {
                                    message.html('<div class="updated error"><p>' + res.msg + '</p></div>');
                                }
                            }
                        } else {
                            $form.find('input[type="submit"]').removeAttr('disabled');
                            message.html('<div class="updated error">' + res + '</div>');
                        }
                    }
                }).fail(function(res) {
                    if (window.console && window.console.log) {
                        console.log(res);
                    }
                });
            },
            clearMessage: function() {
                $('body').on('click', '#wpi-export-success .notice-dismiss', function() {
                    $(this).closest('#wpi-export-success').parent().slideUp('fast');
                });
            }
        };
        WPInv_Quote_Export.init();

    });

})(jQuery);
