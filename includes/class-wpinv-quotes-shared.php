<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

/**
 * Calls the class.
 */
function wpinv_quote_call_shared_class() {
    new Wpinv_Quotes_Shared();

}
add_action( 'wpinv_quotes_loaded', 'wpinv_quote_call_shared_class', 2 );

class Wpinv_Quotes_Shared
{
    public function __construct() {

        add_action( 'wpinv_statuses', array( $this, 'wpinv_quote_statuses' ), 99 );

    }

    /**
     * Add statuses to the dropdown in quote details metabox
     *
     * @since    1.0.0
     */
    public function wpinv_quote_statuses($quote_statuses)
    {
        global $post;
        if ($post->post_type == 'wpi_quote' && !empty($post->ID)) {
            $quote_statuses = array(
                'pending' => __('Pending', 'invoicing'),
                'wpi-quote-sent' => __('Sent', 'invoicing'),
                'publish' => __('Accepted', 'invoicing'),
                'wpi-quote-cancelled' => __('Cancelled', 'invoicing'),
                'wpi-quote-declined' => __('Declined', 'invoicing'),
            );
            $quote_statuses = apply_filters('wpinv_quote_statuses', $quote_statuses);
        }
        return $quote_statuses;
    }
}