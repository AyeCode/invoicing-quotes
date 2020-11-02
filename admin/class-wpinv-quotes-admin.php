<?php
/**
 * Contains the main admin class.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing
 * @subpackage Quotes
 */

/**
 * The main admin class.
 *
 * @since      1.0.0
 * @package    Invoicing
 * @subpackage Quotes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class WPInv_Quotes_Admin {

    /**
     * Metaboxes class.
     *
     * @var WPInv_Quotes_Metaboxes
     */
    public $metaboxes;

    /**
     * Class constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'maybe_create_initial_pages' ), 100, 2 );

        $this->metaboxes = new WPInv_Quotes_Metaboxes();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        $version = filemtime( plugin_dir_path( __FILE__ ) . 'admin.css' );
        wp_enqueue_style( 'wpinv-quotes', plugin_dir_url( __FILE__ ) . 'admin.css', array(), $version );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        global $pagenow, $post;

        $version = filemtime( plugin_dir_path( __FILE__ ) . 'js/wpinv-quotes-admin.js' );
        wp_enqueue_script( 'wpinv-quotes', plugin_dir_url(__FILE__) . 'js/wpinv-quotes-admin.js', array('jquery'), $version, false);

        $localize = array();
        if (isset($post->ID) && $post->post_type == 'wpi_quote' && ($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
            $localize['convert_quote'] = __('Are you sure you want to convert from Quote to Invoice?', 'wpinv-quotes');
            $localize['save_quote']    = __('Save Quote', 'wpinv-quotes');
        }
        wp_localize_script( 'wpinv-quotes', 'wpinv_quotes_admin', $localize);

    }

    /**
     * Creates the initial pages on activation.
     *
     * @since    1.0.0
     */
    public function maybe_create_initial_pages() {

        if ( get_option( 'wpinv_created_initial_quote_pages' ) != 1 ) {

            update_option( 'wpinv_created_initial_quote_pages', 1 );

            $content   = '
                <!-- wp:shortcode -->
                [getpaid_licenses]
                <!-- /wp:shortcode -->
            ';

            wpinv_create_page(
                esc_sql( _x( 'your-quotes', 'Page slug', 'wpinv-quotes' ) ),
                'quote_history_page',
                _x( 'Your Quotes', 'Page title', 'wpinv-quotes' ),
                $content
            );

        }

    }

    /**
     * process when quote accepted
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     */
    function process_quote_published($quote_id)
    {
        if (empty($quote_id)) return;

        do_action('wpinv_quote_before_process_published', $quote_id);

        $accepted_action = wpinv_get_option('accepted_quote_action');
        $gateway = wpinv_get_default_gateway();
        $new_invoice_id = 0;

        if ($accepted_action === 'convert' || $accepted_action === 'convert_send' || empty($accepted_action)) {

            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            $this->wpinv_user_quote_accepted_notification($quote_id);

            //convert quote to invoice
            set_post_type($quote_id, 'wpi_invoice');

            $number = wpinv_update_invoice_number($quote_id, true);
            if (empty($number)) {
                $number = wpinv_format_invoice_number($quote_id);
            }

            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-pending',
                'post_title' => $number,
                'post_name' => wpinv_generate_post_name( $quote_id ),
            ));

            //update meta data
            update_post_meta($quote_id, '_wpinv_number', $number);
            update_post_meta($quote_id, '_wpinv_gateway', $gateway);

            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(sprintf(__('Converted from Quote #%s to Invoice.', 'wpinv-quotes'), $quote_id), false, false, true);

            if ($accepted_action === 'convert_send') {
                global $wpinv_quote, $post;
                $prev_wpinv_quote = $wpinv_quote;
                $prev_post = $post;
                $wpinv_quote = $quote;
                $post = get_post($quote_id);
                wpinv_user_invoice_notification($quote_id);
                $wpinv_quote = $prev_wpinv_quote;
                $post = $prev_post;
            }

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');

        } elseif ($accepted_action === 'duplicate' || $accepted_action === 'duplicate_send') {
            //create new invoice from quote
            global $wpdb;

            $post = get_post($quote_id);

            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $post->post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post->post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => 'wpi-pending',
                'post_type' => 'wpi_invoice',
                'post_title' => $post->post_title,
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', 1),
            );

            $new_invoice_id = wp_insert_post($args);
            //update meta data
            $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$quote_id");
            if (count($post_meta_infos) != 0) {
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ($post_meta_infos as $meta_info) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes($meta_info->meta_value);
                    $sql_query_sel[] = "SELECT $new_invoice_id, '$meta_key', '$meta_value'";
                }
                $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                $wpdb->query($sql_query);
            }

            $number = wpinv_update_invoice_number($new_invoice_id, true);
            if (empty($number)) {
                $number = wpinv_format_invoice_number($new_invoice_id);
            }
            $post_name = wpinv_generate_post_name($new_invoice_id);

            $quote = wpinv_get_invoice($new_invoice_id);
            $quote->add_note(sprintf(__('Created Invoice from Quote #%s.', 'wpinv-quotes'), $quote_id), false, false, true);

            // Update post title and date
            wp_update_post(array(
                'ID' => $new_invoice_id,
                'post_title' => $number,
                'post_name' => $post_name,
            ));

            update_post_meta($new_invoice_id, '_wpinv_number', $number);
            update_post_meta($new_invoice_id, '_wpinv_gateway', $gateway);
            update_post_meta($new_invoice_id, '_wpinv_quote_reference_id', $quote_id);

            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            delete_post_meta($quote_id, '_wpinv_key');
            $quote = wpinv_get_invoice($quote_id);
            $quote->add_note(sprintf(__('Converted from Quote to Invoice #%s.', 'wpinv-quotes'), $new_invoice_id), false, false, true);

            if ($accepted_action === 'duplicate_send') {
                global $wpinv_quote, $post;
                $prev_wpinv_quote = $wpinv_quote;
                $prev_post = $post;
                $wpinv_quote = $quote;
                $post = get_post($quote_id);
                wpinv_user_invoice_notification($new_invoice_id);
                $wpinv_quote = $prev_wpinv_quote;
                $post = $prev_post;
            }

            $this->wpinv_user_quote_accepted_notification($quote_id);

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');

        } else {
            // make the quote as accepted
            wp_update_post(array(
                'ID' => $quote_id,
                'post_status' => 'wpi-quote-accepted',
            ));

            $this->wpinv_user_quote_accepted_notification($quote_id);

            do_action('wpinv_quote_status_update', $quote_id, 'Accepted');
        }

        do_action('wpinv_quote_after_process_published', $quote_id, $new_invoice_id);
    }

    /**
     * Notify when quote accepted
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     * @return bool $sent is mail sent or not
     */
    function wpinv_user_quote_accepted_notification($quote_id)
    {
        $email_type = 'user_quote_accepted';

        if (!wpinv_email_is_enabled($email_type)) {
            return false;
        }

        $quote = new WPInv_Invoice($quote_id);

        if (empty($quote)) {
            return false;
        }

        if (!("wpi_quote" === $quote->post_type)) {
            return false;
        }

        $recipient = wpinv_email_get_recipient($email_type, $quote_id, $quote);

        if (!is_email($recipient)) {
            return false;
        }

        $subject = wpinv_email_get_subject($email_type, $quote_id, $quote);
        $email_heading = wpinv_email_get_heading($email_type, $quote_id, $quote);
        $headers = wpinv_email_get_headers($email_type, $quote_id, $quote);
        $attachments = wpinv_email_get_attachments($email_type, $quote_id, $quote);
        $message_body   = wpinv_email_get_content( $email_type, $quote_id, $quote );

        $content = wpinv_get_template_html('emails/wpinv-email-' . $email_type . '.php', array(
            'quote' => $quote,
            'email_type' => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text' => false,
            'message_body'    => $message_body,
        ), 'invoicing-quotes/', WP_PLUGIN_DIR . '/invoicing-quotes/templates/');

        $sent = wpinv_mail_send($recipient, $subject, $content, $headers, $attachments);

        return $sent;
    }

    /**
     * process when quote declined
     *
     * @since    1.0.0
     * @param int $quote_id ID of post/quote
     */
    function process_quote_declined($quote_id = 0, $reason = '')
    {
        if (empty($quote_id)) return;
        do_action('wpinv_quote_before_process_declined', $quote_id, $reason);
        // make the quote as declined
        wp_update_post(array(
            'ID' => $quote_id,
            'post_status' => 'wpi-quote-declined',
        ));
        do_action('wpinv_quote_after_process_declined', $quote_id, $reason);
    }

}
