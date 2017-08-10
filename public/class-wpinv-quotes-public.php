<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/public
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wpinv_Quotes_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wpinv_Quotes_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpinv-quotes-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wpinv_Quotes_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wpinv_Quotes_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wpinv-quotes-public.js', array('jquery'), $this->version, false);

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function wpinv_quote_print_head_styles()
    {
        wp_register_style('wpinv-quotes-single-style', plugin_dir_url(__FILE__) . 'css/wpinv-quotes-public.css', array(), $this->version, 'all');
        wp_print_styles('wpinv-quotes-single-style');
    }

    /**
     * Display accept and decline buttons in top left corner of receipt
     *
     * @since    1.0.0
     * @param object $quote quote object
     */
    public function wpinv_quote_display_left_actions($quote)
    {
        if ('wpi_quote' != $quote->post_type || empty($quote->ID)) {
            return;
        }

        $accept_msg = wpinv_get_option('accepted_quote_message');
        $decline_msg = wpinv_get_option('declined_quote_message');
        $user_id = (int)$quote->get_user_id();
        $current_user_id = (int)get_current_user_id();

        if ($user_id > 0 && $user_id != $current_user_id) {
            return;
        }
        if ($quote->post_status == 'wpi-quote-sent') {
            remove_query_arg('wpi_action');
            $quote_id = $quote->ID;
            ?>
            <button class="btn btn-success btn-sm accept-quote"
                    title="<?php esc_attr_e('Accept This Quotation', 'invoicing'); ?>"
                    onclick="showAlert('accept')"><?php _e('Accept Quotation', 'invoicing'); ?></button> &nbsp;
            <button class="btn btn-danger btn-sm decline-quote"
                    title="<?php esc_attr_e('Decline This Quotation', 'invoicing'); ?>"
                    onclick="showAlert('decline')"><?php _e('Decline Quotation', 'invoicing'); ?></button>
            <p id="accept-alert" class="alert alert-success"><?php _e('An invoice will be generated on acceptance. ') ?>
                <a class="btn btn-success btn-xs accept-quote"
                   title="<?php esc_attr_e('Accept This Quotation', 'invoicing'); ?>"
                   href="<?php echo Wpinv_Quotes_Shared::get_accept_quote_url($quote_id); ?>"><?php _e('Continue', 'invoicing'); ?></a>
            </p>
            <p id="decline-alert" class="alert alert-danger"><?php _e('You are going to reject this quote. ') ?> <a
                    class="btn btn-danger btn-xs decline-quote"
                    title="<?php esc_attr_e('Decline This Quotation', 'invoicing'); ?>"
                    href="<?php echo Wpinv_Quotes_Shared::get_decline_quote_url($quote_id); ?>"><?php _e('Continue', 'invoicing'); ?></a>
            <script>
                function showAlert(action) {
                    var x = document.getElementById('accept-alert');
                    var y = document.getElementById('decline-alert');
                    if (action == 'accept') {
                        y.style.display = 'none';
                        x.style.display = 'block';
                    } else {
                        x.style.display = 'none';
                        y.style.display = 'block';
                    }
                }
            </script>
            <?php
        } elseif ($quote->post_status == 'wpi-quote-accepted' && !empty($accept_msg)) { ?>
            <p class="btn-success btn-sm quote-front-msg"><?php _e($accept_msg, 'invoicing'); ?></p>
        <?php } elseif ($quote->post_status == 'wpi-quote-declined' && !empty($decline_msg)) { ?>
            <p class="btn-danger btn-sm quote-front-msg"><?php _e($decline_msg, 'invoicing'); ?></p>
            <?php
        }
    }

    /**
     * Display print quote and history buttons in top right corner of receipt
     *
     * @since    1.0.0
     * @param object $quote quote object
     */
    public function wpinv_quote_display_right_actions($quote)
    {
        if ('wpi_quote' == $quote->post_type) {
            $user_id = (int)$quote->get_user_id();
            $current_user_id = (int)get_current_user_id();

            if ($user_id > 0 && $user_id == $current_user_id) {
                ?>
                <a class="btn btn-primary btn-sm" onclick="window.print();"
                   href="javascript:void(0)"><?php _e('Print Quote', 'invoicing'); ?></a> &nbsp;
                <a class="btn btn-warning btn-sm"
                   href="<?php echo esc_url(wpinv_get_history_page_uri()); ?>"><?php _e('History', 'invoicing'); ?></a>
            <?php }
        }
    }

    /**
     * Template to display quotes in history
     *
     * @since    1.0.0
     */
    public function wpinv_quote_before_user_invoices_template()
    {
        wpinv_get_template('wpinv-quote-history.php', '', 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');
    }

}
