<?php
/**
 * Define functions for meta boxes
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */

// MUST have WordPress.
if (!defined('WPINC')) {
    exit('Do NOT access this file directly: ' . basename(__FILE__));
}

class WPInv_Quote_Meta_Box
{
    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   public
     * @param    object $post The post object
     */
    public static function quote_to_invoice_output($post)
    {
        if ('wpi_quote' != $post->post_type)
            return;

        $action_url = add_query_arg(array('wpi_action' => 'convert_quote_to_invoice', 'quote_id' => $post->ID));
        $action_url = esc_url(wp_nonce_url($action_url, 'convert', 'wpinv_convert_quote'));

        do_action('wpinv_metabox_quote_to_invoice_before', $post);
        echo '<p><a id="wpinv_convert_quote" title="'. __("Convert Quote to Invoice", "wpinv-quotes") .'" class="button ui-tip" href="' . $action_url . '"><span class="dashicons dashicons-controls-repeat"></span>' . __("Convert to Invoice", "wpinv-quotes") . '</a></p>';
        do_action('wpinv_metabox_quote_to_invoice_after', $post);
    }

}