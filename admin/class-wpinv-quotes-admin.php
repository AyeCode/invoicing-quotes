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

        add_action( 'admin_init', array( $this, 'maybe_create_initial_pages' ), 100, 2 );
        add_action( 'admin_init', array( $this, 'activation_redirect' ), 110, 2 );
        add_action( 'getpaid_authenticated_admin_action_send_quote', array( $this, 'admin_send_quote' ) );
        add_action( 'getpaid_authenticated_admin_action_convert_quote_to_invoice', array( $this, 'admin_convert_quote_to_invoice' ) );

        $this->metaboxes = new WPInv_Quotes_Metaboxes();
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
                [wpinv_quote_history]
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
     * Maybe redirect users to our admin settings page.
     */
    public function activation_redirect() {

        // Bail if we already redirected.
        if ( get_option( 'wpinv_quotes_activation_redirect' ) || wp_doing_ajax() ) {
            return;
        }

        // Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
        }

        // Prevent further redirects for this site.
        update_option( 'wpinv_quotes_activation_redirect', 1 );

		wp_safe_redirect( admin_url( 'admin.php?page=wpinv-settings&tab=quote' ) );
		exit;
    }

    /**
     * Sends a quote.
     * 
     * @param array $args
     */
    public function admin_send_quote( $args ) {

        // Bail if do not have an invoice.
        if ( empty( $args[ 'invoice_id' ] ) ) {
            return;
        }

        wpinv_user_quote_notification( (int) $args[ 'invoice_id' ] );

		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce', 'invoice_id' ) ) );
		exit;
    }

    /**
     * Convets a quote to an invoice.
     * 
     * @param array $args
     */
    public function admin_convert_quote_to_invoice( $args ) {

        // Bail if do not have an invoice.
        if ( empty( $args[ 'invoice_id' ] ) ) {
            return;
        }

        $quote = new WPInv_Invoice( $args[ 'invoice_id' ] );

        if ( $quote->is_quote() ) {
            $handler = new Wpinv_Quotes_Accepted_Action( false );
            $handler->convert( $quote );
        }

        getpaid_admin()->show_info( __( 'Quote successfully converted to an invoice.', 'wpinv-quotes' ) );
		wp_safe_redirect( remove_query_arg( array( 'getpaid-admin-action', 'getpaid-nonce', 'invoice_id' ) ) );
		exit;
    }

}
