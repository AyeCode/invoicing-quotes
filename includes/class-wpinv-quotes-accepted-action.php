<?php
/**
 * Contains the quote accepted action handler class.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing
 * @subpackage Quotes
 */

/**
 * The quote accepted action handler class.
 *
 * @since      1.0.0
 * @package    Invoicing
 * @subpackage Quotes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Accepted_Action {

    /**
     * @var int
     */
    public $associated_invoice = 0;

    /**
     * Class constructor.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     */
    public function __construct( $quote ) {

        // Abort if the quote has already been converted to an invoice.
        if ( empty( $quote ) || ! $quote->is_quote() ) {
            return;
        }

        $action = wpinv_get_option( 'accepted_quote_action' );

        if ( empty( $action ) || 'convert' === $action || 'convert_send' === $action ) {
            $this->convert( $quote );
        }

        if ( 'duplicate' === $action || 'duplicate_send' === $action ) {
            $this->duplicate( $quote );
        }

    }

    /**
     * Converts a quote to an invoice.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     */
    public function convert( $quote ) {

        $old_number = $quote->get_number();
        $number     = preg_replace( '/[^0-9]/','' , $quote->get_number() );
        $number     = wpinv_format_invoice_number( $number, 'wpi_invoice' );
        $quote->set_post_type( 'wpi_invoice' );
        $quote->set_number( $quote->generate_number() );
        $quote->set_status( 'wpi-pending' );
        $quote->set_title(  $quote->get_number()  );
        $quote->set_path( sanitize_title(  $quote->get_type() . '-' . $quote->get_id()  ) );

        $quote->add_note(
            sprintf(
                __( 'Converted from Quote #%s to Invoice #%s.', 'wpinv-quotes' ),
                $old_number,
                $number
            ),
            false,
            false,
            true
        );

        $quote->save();
        $this->associated_invoice = $quote->get_id();

    }

    /**
     * Create an invoice and keep the quote.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     */
    public function duplicate( $quote ) {

        // Duplicate the parent invoice.
        $invoice = getpaid_duplicate_invoice( $quote );
        $invoice->set_parent_id( 0 );
        $invoice->set_post_type( 'wpi_invoice' );
        $invoice->set_status( 'wpi-pending' );
        $invoice->set_number( $invoice->generate_number() );
        $invoice->set_title( $invoice->get_number() );
        $invoice->save();
        $this->associated_invoice = $invoice->get_id();

    }

}
