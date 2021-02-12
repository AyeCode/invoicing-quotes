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
	 * Admin class.
	 *
	 * @var WPInv_Quotes_Admin
	 */
	public $admin;

	/**
	 * Admin class.
	 *
	 * @var WPInv_Quotes_Settings
	 */
	public $settings;

	/**
	 * Post types manager class.
	 *
	 * @var WPInv_Quotes_Post_Type
	 */
	public $post_types;

	/**
	 * Class constructor.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->include_files();
		$this->init_hooks();

		$this->post_types = new WPInv_Quotes_Post_Type();
		$this->admin    = new WPInv_Quotes_Admin();
		$this->settings   = new WPInv_Quotes_Settings();
	}

	/**
	 * Inits hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected function init_hooks() {

		add_filter( 'wpinv_statuses', array( $this, 'filter_invoice_statuses' ), 10, 2 );
		add_filter( 'wpinv_post_name_prefix', array( $this, 'filter_post_name_prefix' ), 10, 2 );
		add_filter( 'getpaid_invoice_type_prefix', array( $this, 'filter_quote_number_prefix' ), 10, 2 );
		add_filter( 'getpaid_invoice_type_postfix', array( $this, 'filter_quote_number_postfix' ), 10, 2 );
		add_filter( 'getpaid_widget_classes', array( $this, 'register_widget' ) );
		add_action( 'wpinv_invoice_display_left_actions', array( $this, 'invoice_header_left' ) );
		add_action( 'getpaid_unauthenticated_action_accept_quote', array( $this, 'user_accept_quote' ) );
		add_action( 'getpaid_unauthenticated_action_decline_quote', array( $this, 'user_decline_quote' ) );
		add_action( 'getpaid_unauthenticated_action_remove_quote_item', array( $this, 'user_remove_quote_item' ) );
		add_action( 'getpaid-invoice-page-line-item-actions', array( $this, 'filter_invoice_line_item_actions' ), 10, 3 );
		add_action( 'getpaid_rest_api_loaded', array( $this, 'init_api' ) );
		add_action( 'template_redirect', array( $this, 'quote_to_invoice_redirect' ), 100 );
		add_filter( 'getpaid_email_type_is_admin_email', array( $this, 'filter_admin_emails' ), 10, 2 );
		add_filter( 'getpaid_notification_email_invoice_triggers', array( $this, 'filter_email_triggers' ) );
		add_filter( 'getpaid_invoice_email_merge_tags', array( $this, 'filter_email_merge_tags' ), 10, 2 );
		add_action( 'getpaid_invoice_init_email_type_hook', array( $this, 'init_quote_email_type_hook' ), 10, 2 );
		add_action( 'getpaid_template_default_template_path', array( $this, 'maybe_filter_default_template_path' ), 10, 2 );
		add_action( 'getpaid_invoice_meta_data', array( $this, 'filter_invoice_meta' ), 10, 2 );
		add_filter( 'wpinv_user_invoices_columns', array( $this, 'filter_user_invoice_columns' ), 10, 2 );
		add_filter( 'wpinv_user_invoices_actions', array( $this, 'filter_user_invoice_actions' ), 10, 2 );
		add_filter( 'getpaid_invoice_status_wpi-quote-accepted', array( $this, 'handle_quote_accepted' ) );
		add_filter( 'getpaid_user_content_tabs', array( $this, 'register_quotes_tab' ) );

	}

	/**
	 * Filters invoice statuses.
	 *
	 * @since    1.0.0
	 */
	public function filter_invoice_statuses( $statuses, $invoice_type ) {

		if ( 'wpi_quote' == $invoice_type || 'quote' == $invoice_type ) {

			return apply_filters(
				'wpinv_quote_statuses',
				array(
					'wpi-quote-pending'  => __( 'Pending Confirmation', 'wpinv-quotes' ),
					'wpi-quote-accepted' => __( 'Accepted', 'wpinv-quotes' ),
					'wpi-quote-declined' => __( 'Declined', 'wpinv-quotes' ),
				)
			);

		}

		return $statuses;
	}

	/**
	 * Filters the post name prefix.
	 *
	 * @since    1.0.0
	 */
	public function filter_post_name_prefix( $prefix, $post_type ) {

		if ( 'wpi_quote' == $post_type ) {
			return 'quote-';
		}

		return $prefix;
	}

	/**
	 * Filters the quote number prefix.
	 *
	 * @since    1.0.0
	 */
	public function filter_quote_number_prefix( $prefix, $post_type ) {

		if ( 'wpi_quote' == $post_type ) {
			$prefix = wpinv_get_option( 'quote_number_prefix', '' );
			return empty( $prefix ) ? 'QUOTE-' : $prefix;
		}

		return $prefix;
	}

	/**
	 * Filters the quote number postfix.
	 *
	 * @since    1.0.0
	 */
	public function filter_quote_number_postfix( $postfix, $post_type ) {

		if ( 'wpi_quote' == $post_type ) {
		   return wpinv_get_option( 'quote_number_postfix', '' );
		}

		return $postfix;
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

		require_once WPINV_QUOTES_PATH . 'admin/class-wpinv-quotes-admin.php';
		require_once WPINV_QUOTES_PATH . 'includes/general-functions.php';

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
				'<a href="%s" class="btn btn-primary m-1 d-inline-block btn-sm" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( getpaid_get_authenticated_action_url( 'accept_quote', $invoice->get_view_url() ) ),
				esc_attr__( 'Are you sure you want to accept this Quote?', 'wpinv-quotes' ),
				__( 'Accept', 'wpinv-quotes' )
			);

			printf(
				'<a href="%s" class="btn btn-danger m-1 d-inline-block btn-sm" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( getpaid_get_authenticated_action_url( 'decline_quote', $invoice->get_view_url() ) ),
				esc_attr__( 'Are you sure you want to decline this Quote?', 'wpinv-quotes' ),
				__( 'Decline', 'wpinv-quotes' )
			);

		}

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

		// Accept the quote.
		$quote = new WPInv_Invoice( $invoice );
		new Wpinv_Quotes_Converter( $quote, 'accept' );

		wp_safe_redirect( esc_url( remove_query_arg( array( 'getpaid-action', 'getpaid-nonce' ) ) ) );
		exit;
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

		// Decline the quote.
		$quote = new WPInv_Invoice( $invoice );
		new Wpinv_Quotes_Converter( $quote, 'decline' );

		wp_safe_redirect( esc_url( remove_query_arg( array( 'getpaid-action', 'getpaid-nonce' ) ) ) );
		exit;
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

		if ( ! $invoice->has_status( 'wpi-quote-pending' ) ) {
			return array();
		}

		$url                = add_query_arg( 'item', $item->get_id(), $invoice->get_view_url() );
		$url                = esc_url( getpaid_get_authenticated_action_url( 'remove_quote_item', $url ) );
		$actions['quote']   = "<a href='$url' class='text-decoration-none text-danger'>" . __( 'Remove Item', 'wpinv-quotes' ) . '</a>';

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

			wpinv_set_error( 'removed_item', __( 'You have successfully removed the item.', 'wpinv-quotes' ), 'info' );

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

	/**
	 * Redirects accepted quotes to invoices.
	 *
	 * @since 1.0.0
	 */
	public function quote_to_invoice_redirect() {

		$current_invoice = getpaid_get_current_invoice_id();
		if ( ! empty( $current_invoice ) && is_404() && get_query_var( 'post_type' ) == 'wpi_quote' ) {

			$invoice = new WPInv_Invoice( $current_invoice );
			wp_redirect( $invoice->get_view_url(), 302 );
			exit;

		}

	}

	/**
	 * Filters admin emails.
	 *
	 * @since 1.0.0
	 */
	public function filter_admin_emails( $is_admin_email, $email_type ) {

		if ( in_array( $email_type, array( 'user_quote_accepted', 'user_quote_declined' ), true ) ) {
			return true;
		}

		return $is_admin_email;

	}

	/**
	 * Filters email triggers.
	 *
	 * @since 1.0.0
	 */
	public function filter_email_triggers( $triggers ) {

		$triggers['getpaid_new_invoice']                       = array_merge( $triggers['getpaid_new_invoice'], array( 'user_quote' ) );
		$triggers['getpaid_invoice_status_wpi-quote-accepted'] = array( 'user_quote_accepted' );
		$triggers['getpaid_invoice_status_wpi-quote-declined'] = array( 'user_quote_declined' );

		return $triggers;

	}

	/**
	 * Filters email merge tags.
	 *
	 * @since 1.0.0
	 */
	public function filter_email_merge_tags( $email_tags, $invoice ) {

		foreach ( $email_tags as $tag => $value ) {

			if ( false !== stripos( $tag, 'invoice' ) ) {
				$new_tag = str_replace( 'invoice', 'quote', $tag );
				$email_tags[ $new_tag ] = $value;
			}

		}

		$email_tags['{valid_until}']          = getpaid_format_date_value( get_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until', true ) );
		$email_tags['{quote_decline_reason}'] = sanitize_text_field( get_post_meta( $invoice->get_id(), '_wpinv_quote_decline_reason', true ) );

		return $email_tags;

	}

	/**
	 * Inits quote email type hooks.
	 *
	 * @since 1.0.0
	 */
	public function init_quote_email_type_hook( $email_type, $hook ) {

		if ( in_array( $email_type, array( 'user_quote_accepted', 'user_quote_declined', 'user_quote' ), true ) ) {

			$email_type = "send_{$email_type}_email";
			add_action( $hook, array( $this, $email_type ), 100, 2 );

		}

	}

	/**
	 * Sends the quote accepted email to the site admin.
	 *
	 * @since 1.0.0
	 */
	public function send_user_quote_accepted_email( $quote ) {

		$email     = new GetPaid_Notification_Email( 'user_quote_accepted', $quote );
		$recipient = $recipient = wpinv_get_admin_email();
		$sender    = getpaid()->get( 'invoice_emails' );
		return $sender->send_email( $quote, $email, 'user_quote_accepted', $recipient );

	}

	/**
	 * Sends the quote declined email to the site admin.
	 *
	 * @since 1.0.0
	 */
	public function send_user_quote_declined_email( $quote ) {

		$email     = new GetPaid_Notification_Email( 'user_quote_declined', $quote );
		$recipient = wpinv_get_admin_email();
		$sender    = getpaid()->get( 'invoice_emails' );
		return $sender->send_email( $quote, $email, 'user_quote_declined', $recipient );

	}

	/**
	 * Sends the new quote email to the client.
	 *
	 * @since 1.0.0
	 */
	public function send_user_quote_email( $quote ) {

		if ( $quote->is_quote() ) {
			$email     = new GetPaid_Notification_Email( 'user_quote', $quote );
			$recipient = $quote->get_email();
			$sender    = getpaid()->get( 'invoice_emails' );
			return $sender->send_email( $quote, $email, 'user_quote', $recipient );
		}

	}

	/**
	 * Filters the default template paths.
	 *
	 * @since 1.0.0
	 */
	public function maybe_filter_default_template_path( $default_path, $template_name ) {

		$our_emails = array(
			'emails/wpinv-email-user_quote_accepted.php',
			'emails/wpinv-email-user_quote_declined.php',
			'emails/wpinv-email-user_quote.php'
		);

		if ( in_array( $template_name, $our_emails, true ) ) {
			return WPINV_QUOTES_PATH . 'templates';
		}

		return $default_path;
	}

	/**
	 * Filters invoice meta to display the Valid Until Date.
	 *
	 * @param array $meta
	 * @param WPInv_Invoice $invoice
	 */
	public function filter_invoice_meta( $meta, $invoice ) {

		if ( $invoice->is_quote() ) {

			$first_array  = array_slice( $meta, 0, -1, true );
			$second_array = array_slice( $meta, -1, 1, true );
			$valid_untill = array(

				'valid-until' => array(
					'label'   => __( 'Valid Until', 'wpinv-quotes' ),
					'value'   => getpaid_format_date_value( get_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until', true) ),
				)

			);

			$meta = array_merge( $first_array, $valid_untill, $second_array );
		}

		return $meta;
	}

	/**
	 * Filters the user invoices table columns.
	 *
	 * @param array $columns
	 * @param string $post_type
	 */
	public function filter_user_invoice_columns( $columns, $post_type ) {

		if ( 'wpi_quote' != $post_type ) {
			return $columns;
		}

		if ( isset( $columns['payment-date'] ) ) {
			unset( $columns['payment-date'] );
		}

		return $columns;
	}

	/**
	 * Filters the user invoices table actions.
	 *
	 * @param array $actions
	 * @param WPInv_Invoice $invoice
	 * @param string $post_type
	 */
	public function filter_user_invoice_actions( $actions, $invoice ) {

		if ( ! $invoice->is_quote() ) {
			return $actions;
		}

		if ( isset( $actions['pay'] ) ) {
			unset( $actions['pay'] );
		}

		if ( $invoice->has_status( 'wpi-quote-pending' ) ) {

			$actions['accept'] = array(
				'url'   => getpaid_get_authenticated_action_url( 'accept_quote', $invoice->get_view_url() ),
				'name'  => __( 'Accept', 'wpinv-quotes' ),
				'class' => 'btn-primary',
				'attrs' => sprintf(
					'onclick="return confirm(\'%s\')"',
					esc_attr__( 'Are you sure you want to accept this Quote?', 'wpinv-quotes' )
				)
			);

			$actions['decline'] = array(
				'url'   => getpaid_get_authenticated_action_url( 'decline_quote', $invoice->get_view_url() ),
				'name'  => __( 'Decline', 'wpinv-quotes' ),
				'class' => 'btn-danger',
				'attrs' => sprintf(
					'onclick="return confirm(\'%s\')"',
					esc_attr__( 'Are you sure you want to decline this Quote?', 'wpinv-quotes' )
				)
			);

		}

		return $actions;
	}

	/**
     * Registers the quotes tab.
     *
     * @param array $tabs
     */
    public function register_quotes_tab( $tabs ) {

        return array_merge(
            array(
                'gp-quotes'     => array(
                    'label'     => __( 'Quotes', 'wpinv-quotes' ),
                    'content'   => '[wpinv_quote_history]',
                    'icon'      => 'fas fa-quote-right',
                )
			),
			$tabs
        );

	}

	/**
	 * Fired whenever a quote is accepted.
	 *
	 * @param WPInv_Invoice $quote
	 */
	public function handle_quote_accepted( $quote ) {
		new Wpinv_Quotes_Accepted_Action( $quote );
	}

}
