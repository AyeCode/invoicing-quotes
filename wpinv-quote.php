<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpgeodirectory.com
 * @since             1.0.0
 * @package           Wpinv_Quotes
 *
 * @wordpress-plugin
 * Plugin Name:       Invoicing - Quotes
 * Plugin URI:        https://wpgeodirectory.com/
 * Description:       Create quotes for customers, if accepted it will convert to an invoice that can be paid.
 * Version:           1.0.0
 * Author:            GeoDirectory Team
 * Author URI:        https://wpgeodirectory.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpinv-quotes
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WPINV_QUOTES_VERSION', '1.0.0');
define('WPINV_QUOTES_PATH', plugin_dir_path(__FILE__));
define('WPINV_QUOTES_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpinv-quotes-activator.php
 */
function activate_wpinv_quotes($network_wide = false)
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpinv-quotes-activator.php';
    Wpinv_Quotes_Activator::activate($network_wide);
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpinv-quotes-deactivator.php
 */
function deactivate_wpinv_quotes()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpinv-quotes-deactivator.php';
    Wpinv_Quotes_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wpinv_quotes');
register_deactivation_hook(__FILE__, 'deactivate_wpinv_quotes');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wpinv-quotes.php';

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpinv-quotes-deactivator.php
 */
function wpinv_invoice_plugin_notice()
{
    echo '<div class="error"><p>Quote Plugin requires the <a href="https://wordpress.org/plugins/invoicing/" target="_blank">invoicing</a> plugin to be installed and active.</p></div>';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpinv_quotes()
{
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !class_exists( 'WPInv_Plugin' ) ) {
        /**
         * The plugin activation class that will install and activate Invoicing if missing.
         */
        require plugin_dir_path(__FILE__) . 'includes/class-tgm-plugin-activation.php';
        add_action( 'tgmpa_register', 'wpinv_quotes_register_required_plugins' );

        /**
         * Require the Invoicing plugin to be installed.
         */
        function wpinv_quotes_register_required_plugins() {

            /*
             * Array of plugin arrays. Required keys are name and slug.
             * If the source is NOT from the .org repo, then source is also required.
             */
            $plugins = array(
                array(
                    'name'      => 'Invoicing',
                    'slug'      => 'invoicing',
                    'required'  => true,
                    'force_activation'   => true
                )
            );

            /*
             * Array of configuration settings. Amend each line as needed.
             *
             * TGMPA will start providing localized text strings soon. If you already have translations of our standard
             * strings available, please help us make TGMPA even better by giving us access to these translations or by
             * sending in a pull-request with .po file(s) with the translations.
             *
             * Only uncomment the strings in the config array if you want to customize the strings.
             */
            $config = array(
                'id'           => 'wpinv-quotes',                 // Unique ID for hashing notices for multiple instances of TGMPA.
                'parent_slug'  => 'plugins.php',            // Parent menu slug.
                'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
                'has_notices'  => true,                    // Show admin notices or not.
                'dismissable'  => false,                    // If false, a user cannot dismiss the nag message.
                'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
                'is_automatic' => true,                   // Automatically activate plugins after installation or not.
                'message'      => '',                      // Message to output right before the plugins table.

            );

            tgmpa( $plugins, $config );
        }

        add_action( 'admin_notices', 'wpinv_invoice_plugin_notice' ) ;
        return;
    }
    $plugin = new Wpinv_Quotes();
    $plugin->run();

}

add_action('plugins_loaded', 'run_wpinv_quotes'); // wait until 'plugins_loaded' hook fires, for WP Multisite compatibility