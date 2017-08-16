<?php
/**
 * Quote Shortcodes
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes/shortcodes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 * @link       https://wpgeodirectory.com
 */

class Wpinv_Quote_Shortcodes {

    /**
     * Init shortcodes.
     */
    public static function init() {
        $shortcodes = array(
            'wpinv_quote_history'  => __CLASS__ . '::wpinv_quote_history',
        );

        foreach ( $shortcodes as $shortcode => $function ) {
            add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
        }
    }

    /**
     * Shortcode Wrapper.
     *
     * @param string[] $function
     * @param array $atts (default: array())
     * @return string
     */
    public static function wpinv_shortcode_wrapper(
        $function,
        $atts    = array(),
        $wrapper = array(
            'class'  => 'wpinv-quotes',
            'before' => null,
            'after'  => null,
        )
    ) {
        ob_start();

        echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
        call_user_func( $function, $atts );
        echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

        return ob_get_clean();
    }

    /**
     * Quote History page shortcode.
     *
     * @param mixed $atts
     * @return string
     */
    public static function wpinv_quote_history( $atts ) {
        return self::wpinv_shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
    }

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
        do_action( 'wpinv_before_user_quote_history' );
        wpinv_get_template('wpinv-quote-history.php', $atts, 'wpinv-quote/', WP_PLUGIN_DIR . '/wpinv-quote/templates/');
        do_action( 'wpinv_after_user_quote_history' );
	}
}
