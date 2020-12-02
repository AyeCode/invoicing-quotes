<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpinvoicing.com
 * @since             1.0.0
 * @package           Invoicing
 *
 * @wordpress-plugin
 * Plugin Name:       GetPaid > Quotes
 * Plugin URI:        https://wpinvoicing.com/
 * Description:       Create quotes for customers, if accepted it will convert to an invoice that can be paid.
 * Version:           1.0.7
 * Author:            AyeCode Ltd
 * Author URI:        https://wpinvoicing.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpinv-quotes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define( 'WPINV_QUOTES_VERSION', '1.0.7' );
define( 'WPINV_QUOTES_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPINV_QUOTES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Displays a compatibility notice for users not who have not yet installed
 * GetPaid.
 *
 * @since 1.0.0
 * @return void
 */
function wpinv_quotes_check_getpaid(){

    if ( is_admin() && ! did_action( 'getpaid_init' ) ) {
        ?>
            <div class="notice notice-error">
                <p><?php _e( '"GetPaid > Quotes" requires that you install the latest version of GetPaid|Invoicing first.', 'wpinv-quotes' ); ?></p>
            </div>
        <?php
    }

}
add_action( 'admin_notices', 'wpinv_quotes_check_getpaid' );


/**
 * Adds our path to the list of autoload locations.
 *
 * @since 1.0.0
 * @return void
 */
function wpinv_quotes_autoload_locations( $locations ) {
    $locations[] = plugin_dir_path( __FILE__ ) . 'includes';
    $locations[] = plugin_dir_path( __FILE__ ) . 'admin';
    return $locations;
}
add_filter( 'getpaid_autoload_locations', 'wpinv_quotes_autoload_locations' );


/**
 * Inits the plugin.
 *
 * @since 1.0.0
 * @return void
 */
function wpinv_quotes_init() {
    global $wpinv_quotes;

    // Init the URL discounts manager
    $wpinv_quotes = new WPInv_Quotes();

}
add_action( 'getpaid_actions', 'wpinv_quotes_init' );


/**
 * Load our textdomain.
 *
 * @since 1.0.0
 * @return void
 */
function wpinv_quotes_load_plugin_textdomain() {

	load_plugin_textdomain(
		'wpinv-quotes',
		false,
		'invoicing-quotes/languages/'
	);

}
add_action( 'plugins_loaded', 'wpinv_quotes_load_plugin_textdomain' );
