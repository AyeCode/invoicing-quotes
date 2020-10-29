<?php
/**
 * Contains the main plugin class.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing
 * @subpackage Quotes
 */

/**
 * The main plugin class.
 *
 * @since      1.0.0
 * @package    Invoicing
 * @subpackage Quotes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes {

    /**
     * Class constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->include_files();
        $this->init_hooks();

    }

    /**
     * Inits hooks.
     *
     * @since    1.0.0
     * @access   protected
     */
    protected function init_hooks() {

        add_filter( 'getpaid_widget_classes', array( $this, 'register_widget' ) );
        add_action( 'wpinv_invoice_display_left_actions', array( $this, 'invoice_header_left' ) );
        add_action( 'wpinv_invoice_display_right_actions', array( $this, 'invoice_header_right' ) );
        add_action( 'getpaid_authenticated_action_accept_quote', array( $this, 'user_accept_quote' ) );
        add_action( 'getpaid_authenticated_action_decline_quote', array( $this, 'user_decline_quote' ) );
        add_action( 'getpaid_authenticated_action_remove_quote_item', array( $this, 'user_remove_quote_item' ) );
        add_action( 'getpaid-invoice-page-line-item-actions', array( $this, 'filter_invoice_line_item_actions' ), 10, 3 );
        add_action( 'getpaid_rest_api_loaded', array( $this, 'init_api' ) );
        add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

    }

    /**
     * Registers the invoice history class.
     *
     * @since    1.0.0
     * @param array $classes
     */
    public function register_widget( $classes ) {

        return array_merge(
            $classes,
            array(
                'WPInv_Quotes_History_Widget'
            )
        );

    }

    /**
     * Loads plugin files.
     *
     * @since    1.0.0
     * @access   protected
     */
    protected function include_files() {

        require_once WPINV_QUOTES_PATH . 'includes/admin/class-wpinv-quotes-admin.php';
        require_once WPINV_QUOTES_PATH . 'includes/general-functions.php';

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Wpinv_Quotes_Admin();

        add_action( 'wpinv_settings_tab_bottom_emails_user_quote', 'wpinv_settings_tab_bottom_emails', 10, 2 );
        add_action( 'wpinv_settings_tab_bottom_emails_user_quote_accepted', 'wpinv_settings_tab_bottom_emails', 10, 2 );
        add_action( 'wpinv_settings_tab_bottom_emails_user_quote_declined', 'wpinv_settings_tab_bottom_emails', 10, 2 );

        if ( is_admin() && get_option( 'activated_quotes' ) == 'wpinv-quotes' ) { // update wpinv_settings on activation
            $this->loader->add_action('admin_init', $plugin_admin, 'wpinv_quote_update_settings', 99);
        }

    }

    /**
     * Add custom quote status in queries
     *
     * @since    1.0.0
     */
    public function pre_get_posts( $wp_query ) {

        if ( !empty( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'wpi_quote' && is_user_logged_in() && is_single() && $wp_query->is_main_query() ) {
            $wp_query->query_vars['post_status'] = array_keys( Wpinv_Quotes_Shared::wpinv_get_quote_statuses() );
        }

        return $wp_query;
    }

    /**
     * Displays the invoice header left.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $invoice
     */
    public function invoice_header_left( $invoice ) {

        if ( ! $invoice->is_quote() ) {
            return;
        }

        if ( $invoice->has_status( 'wpi-quote-pending' ) ) {

            printf(
				'&nbsp;&nbsp;<a href="%s" class="btn btn-primary btn-sm" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( getpaid_get_authenticated_action_url( 'accept_quote', $invoice->get_view_url() ) ),
				esc_attr__( 'Are you sure you want to accept this Quote?', 'wpinv-quotes' ),
				__( 'Accept Quote', 'wpinv-quotes' )
            );

            printf(
				'&nbsp;&nbsp;<a href="%s" class="btn btn-primary btn-sm" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( getpaid_get_authenticated_action_url( 'decline_quote', $invoice->get_view_url() ) ),
				esc_attr__( 'Are you sure you want to decline this Quote?', 'wpinv-quotes' ),
				__( 'Decline Quote', 'wpinv-quotes' )
            );

        }

    }

    /**
     * Displays the invoice header right.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $invoice
     */
    public function invoice_header_right( $invoice ) {
        wpinv_get_template( 'header-right-actions.php', compact( 'invoice' ), 'invoicing-quotes', WPINV_QUOTES_PATH . 'templates' );
    }

    /**
     * Fired when a user accepts a quote.
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function user_accept_quote() {

        $invoice = getpaid_get_current_invoice_id();

        if ( empty( $invoice ) || ! wpinv_user_can_view_invoice( $invoice ) ) {
            wpinv_set_error( 'invalid_quote', __( 'You do not have permission to accept this quote.', 'wpinv-quotes' ) );
            return;
        }

        $msg = wpinv_get_option( 'accepted_quote_message' );
        $msg = empty( $msg ) ? __( 'You have accepted this quote.', 'wpinv-quotes' ) : $msg;
        wpinv_set_error( 'accepted', $msg, 'info' );

        // ACCEPT THE QUOTE HERE.
    }

    /**
     * Fired when a user declines a quote.
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function user_decline_quote() {

        $invoice = getpaid_get_current_invoice_id();

        if ( empty( $invoice ) || ! wpinv_user_can_view_invoice( $invoice ) ) {
            wpinv_set_error( 'invalid_quote', __( 'You do not have permission to decline this quote.', 'wpinv-quotes' ) );
            return;
        }

        $msg = wpinv_get_option( 'declined_quote_message' );
        $msg = empty( $msg ) ? __( 'You have declined this quote.', 'wpinv-quotes' ) : $msg;
        wpinv_set_error( 'declined', $msg, 'info' );

        // DECLINE THE QUOTE HERE.
    }

    /**
     * Filters the invoice line items actions.
     *
     * @param array actions
     * @param WPInv_Item $item
     * @param WPInv_Invoice $invoice
     */
    public function filter_invoice_line_item_actions( $actions, $item, $invoice ) {

        if ( ! $invoice->is_quote() ) {
            return $actions;
        }

        $url                = add_query_arg( 'item', $item->get_id(), $invoice->get_view_url() );
        $url                = esc_url( getpaid_get_authenticated_action_url( 'remove_quote_item', $url ) );
        $actions['license'] = "<a href='$url' class='text-decoration-none text-danger'>" . __( 'View License', 'getpaid-license-manager' ) . '</a>';

        return $actions;

    }

    /**
     * Fired when a user removes a quote item
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function user_remove_quote_item( $data ) {

        $invoice = getpaid_get_current_invoice_id();

        if ( empty( $invoice ) || ! wpinv_user_can_view_invoice( $invoice ) ) {
            wpinv_set_error( 'invalid_quote', __( 'You do not have permission to remove items from this quote.', 'wpinv-quotes' ) );
            return;
        }

        $quote = new WPInv_Invoice( $invoice );

        if ( $quote->is_quote() ) {

            $quote->remove_item( (int) $data['item'] );
            $quote->recalculate_total();
            $quote->save();

            wpinv_set_error( 'declined', __( 'You have successfully removed the item.', 'wpinv-quotes' ), 'info' );

            wp_redirect( esc_url( $quote->get_view_url() ) );
            exit;

        }

    }

    /**
     * Loads the REST api.
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     * @param       WPInv_API $api
     */
    public function init_api( $api ) {
        $api->quotes = new WPInv_REST_Quotes_Controller();
    }

}
