<?php

/**
 * Registers and manages the quote metaboxes.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    wpinv-quotes Quotes
 * @subpackage wpinv-quotes Quotes - METABOXES
 */

/**
 * Registers and manages the quote metaboxes.
 *
 * @package    wpinv-quotes Quotes
 * @subpackage wpinv-quotes Quotes - METABOXES
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class WPInv_Quotes_Metaboxes {

    /**
     * Class constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {

		// Register metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 7, 2 );

		// Remove metaboxes.
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 30 );

		// Extend the invoice details metabox.
		add_action( 'getpaid_metabox_after_due_date', array( $this, 'extend_invoice_details_metabox' ), 10 );
		add_action( 'wpinv_invoice_metabox_saved', array( $this, 'save_valid_until_date' ), 10 );
    }

	/**
     * Register quote metaboxes
     *
     * @since 1.0.0
     * @param string $post_type current post type
     * @param $post current post
     */
    public function add_meta_boxes( $post_type, $post ) {

		// Abort if this is not our post type.
		if ( $post_type != 'wpi_quote' ) {
			return;
		}

		$invoice = new WPInv_Invoice( $post );

		// Resend quote.
		if ( ! $invoice->is_draft() && $invoice->has_status( 'wpi-quote-pending' ) ) {
            add_meta_box( 'wpinv-mb-convert-quote', __( 'Quote Actions', 'wpinv-quotes' ), 'WPInv_Quotes_Metaboxes::output', 'wpi_quote', 'side', 'low' );
		}

	}

    /**
	 * Remove some metaboxes.
	 */
	public static function remove_meta_boxes() {
		remove_meta_box( 'wpseo_meta', 'wpi_quote', 'normal' );
		remove_meta_box( 'wpinv-mb-resend-invoice', 'wpi_quote', 'side' );
		remove_meta_box( 'wpinv-mb-subscriptions', 'wpi_quote', 'advanced' );
		remove_meta_box( 'wpinv-mb-subscription-invoices', 'wpi_quote', 'advanced' );
		remove_meta_box( 'wpinv-invoice-payment-form-details', 'wpi_quote', 'side' );
	}

	/**
     * Displays the Convert Quote metabox.
     *
     * @since    1.0.0
     * @access   public
     * @param    WP_Post $post The post object
     */
    public static function output( $post ) {

		$invoice     = new WPInv_Invoice( $post );

		$convert_url = esc_url(
            wp_nonce_url(
                add_query_arg(
                    array(
                        'getpaid-admin-action' => 'convert_quote_to_invoice',
                        'invoice_id'           => $invoice->get_id()
                    )
                ),
                'getpaid-nonce',
                'getpaid-nonce'
            )
		);

		$reminder_url = esc_url(
            wp_nonce_url(
                add_query_arg(
                    array(
                        'getpaid-admin-action' => 'send_quote_reminder',
                        'invoice_id'           => $invoice->get_id()
                    )
                ),
                'getpaid-nonce',
                'getpaid-nonce'
            )
		);

		?>
            <p class="wpi-meta-row wpi-convert-quote"><a href="<?php echo $convert_url; ?>" class="button button-secondary"><?php _e( 'Convert Quote to Invoice', 'wpinv-quotes' ); ?></a></p>
            <p class="wpi-meta-row wpi-send-reminder"><a href="<?php echo $reminder_url; ?>" class="button button-secondary"><?php esc_attr_e( 'Send Quote to Customer', 'wpinv-quotes' ); ?></a></p>
		<?php

		do_action( 'getpaid_after_quote_actions_metabox', $invoice );
	}

	/**
     * Extends the invoice details metabox.
     *
     * @since    1.0.0
     * @access   public
     * @param    WPInv_Invoice $invoice
     */
    public function extend_invoice_details_metabox( $invoice ) {

		// Due date.
		if ( $invoice->is_type( 'quote' ) ) {

			// Date created.
			echo aui()->input(
				array(
					'type'        => 'datepicker',
					'id'          => 'wpinv_quote_valid_until',
					'name'        => 'wpinv_quote_valid_until',
					'label'       => __( 'Valid Until:', 'wpinv-quotes' ) . getpaid_get_help_tip( __( 'The date until which this quote becomes invalid.', 'wpinv-quotes' ) ),
					'label_type'  => 'vertical',
					'placeholder' => 'YYYY-MM-DD 00:00',
					'class'       => 'form-control-sm',
					'value'       => get_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until', true ),
					'extra_attributes' => array(
						'data-enable-time' => 'true',
						'data-time_24hr'   => 'true',
						'data-allow-input' => 'true',
					),
				)
			);

		}

	}

	/**
     * Saves the valid until date.
     *
     * @since    1.0.0
     * @access   public
     * @param    WPInv_Invoice $invoice
     */
    public function save_valid_until_date( $invoice ) {

		if ( isset( $_POST['wpinv_quote_valid_until'] ) ) {

			$raw_date = wpinv_clean( $_POST['wpinv_quote_valid_until'] );
			$date     = date( 'Y-m-d H:i:s', strtotime( $raw_date ) );

			if ( empty( $raw_date ) || empty( $date ) ) {
				delete_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until' );
			} else {
				update_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until', $date );
			}

		}

	}

}
