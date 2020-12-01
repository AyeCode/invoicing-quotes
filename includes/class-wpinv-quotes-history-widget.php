<?php
/**
 * Contains the quote history widget class.
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Quotes history widget.
 */
class WPInv_Quotes_History_Widget extends WP_Super_Duper {

    /**
     * Register the widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => 'wpinv-quotes',
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['invoicing','history','quotes']",
            'class_name'     => __CLASS__,
            'base_id'       => 'wpinv_quote_history',
            'name'          => __('GetPaid > Quotes','wpinv-quotes'),
            'widget_ops'    => array(
                'classname'   => 'wpinv-quotes bsui',
                'description' => esc_html__('Displays quote history.','wpinv-quotes'),
            ),
            'arguments'     => array(
                'title'  => array(
                    'title'       => __( 'Widget title', 'wpinv-quotes' ),
                    'desc'        => __( 'Enter widget title.', 'wpinv-quotes' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'default'     => '',
                    'advanced'    => false
                ),
            )

        );


        parent::__construct( $options );
    }

	/**
	 * The Super block output function.
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @param string $content
	 *
	 * @return mixed|string|bool
	 */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {
        return getpaid_invoice_history( get_current_user_id(), 'wpi_quote' );
    }

}
