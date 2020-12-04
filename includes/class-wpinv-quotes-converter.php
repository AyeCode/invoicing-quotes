<?php
/**
 * Contains the quote conveter class.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing
 * @subpackage Quotes
 */

/**
 * The quote conveter class.
 *
 * @since      1.0.0
 * @package    Invoicing
 * @subpackage Quotes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Converter {

    /**
     * Class constructor.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     * @param string $action Either accept or decline
     */
    public function __construct( $quote, $action = 'accept' ) {

        if ( 'accept' === $action ) {
            $this->accept( $quote );
        } else {
            $this->decline( $quote );
        }

    }

    /**
     * Accepts a quote.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     */
    public function accept( $quote ) {

        if ( $quote->is_quote() ) {

            if ( $quote->has_status( 'wpi-quote-accepted' ) ) {
                $msg   = __( 'You have already accepted this quote.', 'wpinv-quotes' );
                return $this->show_notice( $msg, 'error' );
            }

            $msg   = wpinv_get_option( 'accepted_quote_message' );
            $msg   = empty( $msg ) ? __( 'You have accepted the quote.', 'wpinv-quotes' ) : $msg;

            $this->show_notice( $msg, 'success' );

            $quote->set_status( 'wpi-quote-accepted' );
            $quote->save();

        }

    }

    /**
     * Declines a quote.
     *
     * @since    1.0.0
     * @param WPInv_Invoice $quote
     */
    public function decline( $quote ) {

        if ( $quote->is_quote() ) {

            if ( $quote->has_status( 'wpi-quote-declined' ) ) {
                $msg   = __( 'You have already declined this quote.', 'wpinv-quotes' );
                return $this->show_notice( $msg, 'error' );
            }

            $msg   = wpinv_get_option( 'declined_quote_message' );
            $msg   = empty( $msg ) ? __( 'You have declined this quote.', 'wpinv-quotes' ) : $msg;

            $this->show_notice( $msg, 'info' );

            $quote->set_status( 'wpi-quote-declined' );
            $quote->save();

        }

    }

    /**
     * Displays a notice.
     *
     * @since    1.0.0
     * @param string $notice The notice to display
     * @param string $type Either error, info, notice or success
     */
    protected function show_notice( $notice, $type ) {

        $type = sanitize_key( $type );

        if ( is_admin() ) {
            $method = "show_$type";
            getpaid_admin()->$method( $notice );
        } else {
            wpinv_set_error( 'quote_action', $notice, $type );
        }

    }

}
