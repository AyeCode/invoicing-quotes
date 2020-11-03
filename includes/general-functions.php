<?php

/**
 * Function to send customer quote email notification to customer
 *
 * @since  1.0.0
 * @param int $quote_id ID of post/quote
 * @return bool $sent email sent or not
 */
function wpinv_user_quote_notification( $quote_id ) {

    $quote = new WPInv_Invoice( $quote_id );

    if ( ! $quote->is_quote() ) {
        return;
    }

    $GLOBALS['wpinv_quotes']->send_user_quote_email( $quote );

}
