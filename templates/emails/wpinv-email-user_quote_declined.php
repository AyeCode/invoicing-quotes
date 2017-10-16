<?php
// don't load directly
if (!defined('ABSPATH'))
    die('-1');

do_action('wpinv_email_header', $email_heading, $quote, $email_type, $sent_to_admin);

if(isset($message_body) && !empty($message_body)) {
    echo wpautop(wptexturize($message_body));
}

do_action('wpinv_email_before_quote_details', $quote, $email_type, $sent_to_admin);

do_action('wpinv_email_invoice_details', $quote, $email_type, $sent_to_admin);

do_action('wpinv_email_invoice_items', $quote, $email_type, $sent_to_admin);

do_action('wpinv_email_billing_details', $quote, $email_type, $sent_to_admin);

do_action('wpinv_email_after_quote_details', $quote, $email_type, $sent_to_admin);

do_action('wpinv_email_footer', $quote, $email_type, $sent_to_admin);