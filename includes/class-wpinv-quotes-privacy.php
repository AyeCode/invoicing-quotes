<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Privacy Class.
 */
class Wpinv_Quotes_Privacy extends WPInv_Abstract_Privacy {

    /**
     * Init - hook into events.
     */
    public function __construct() {
        parent::__construct( __( 'Quotes', 'wpinv-quotes' ) );

        // Include supporting classes.
        include_once 'class-wpinv-quotes-privacy-exporters.php';

        // This hook registers Quotes data exporters.
        $this->add_exporter( 'wpinv-customer-quotes', __( 'Customer Quotes', 'wpinv-quotes' ), array( 'WPInv_Quotes_Privacy_Exporters', 'customer_quotes_data_exporter' ) );
    }

    /**
     * Add privacy policy content for the privacy policy page.
     *
     * @since 3.4.0
     */
    public function get_privacy_message() {

        $content = '
			<div contenteditable="false">' .
            '<p class="wp-policy-help">' .
            __( 'Quotes uses the following privacy.', 'wpinv-quotes' ) .
            '</p>' .
            '</div>';

        return apply_filters( 'wpinv_quotes_privacy_policy_content', $content );
    }

}

new Wpinv_Quotes_Privacy();
