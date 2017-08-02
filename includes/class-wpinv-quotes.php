<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wpinv_Quotes_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name = 'wpinv-quotes';

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version = WPINV_QUOTES_VERSION;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Wpinv_Quotes_Loader. Orchestrates the hooks of the plugin.
     * - Wpinv_Quotes_i18n. Defines internationalization functionality.
     * - Wpinv_Quotes_Admin. Defines all hooks for the admin area.
     * - Wpinv_Quotes_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpinv-quotes-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpinv-quotes-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wpinv-quotes-admin.php';
        require_once(WPINV_QUOTES_PATH . 'includes/class-wpinv-quotes-meta-boxes.php');
        require_once(WPINV_QUOTES_PATH . 'includes/class-wpinv-quotes-shared.php');

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wpinv-quotes-public.php';

        $this->loader = new Wpinv_Quotes_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Wpinv_Quotes_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Wpinv_Quotes_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());
        $plugin_i18n->load_plugin_textdomain();

    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
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

        $plugin_admin = new Wpinv_Quotes_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_admin, 'wpinv_quote_new_cpt', 1);
        $this->loader->add_action('init', $plugin_admin, 'wpinv_quote_register_post_status', 10);
        $this->loader->add_filter('manage_wpi_quote_posts_columns', $plugin_admin, 'wpinv_quote_columns', 10, 3);
        $this->loader->add_filter('bulk_actions-edit-wpi_quote', $plugin_admin, 'wpinv_quote_bulk_actions', 10, 3);
        $this->loader->add_filter('manage_wpi_quote_posts_custom_column', $plugin_admin, 'wpinv_quote_posts_custom_column', 10, 3);
        $this->loader->add_filter('manage_edit-wpi_quote_sortable_columns', $plugin_admin, 'wpinv_quote_sortable_columns', 10, 3);
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'wpinv_quoute_add_meta_boxes', 30, 2);
        $this->loader->add_filter('wpinv_resend_invoice_metabox_text', $plugin_admin, 'wpinv_quote_resend_quote_metabox_text');
        $this->loader->add_filter('wpinv_resend_invoice_email_actions', $plugin_admin, 'wpinv_quote_resend_quote_email_actions');
        $this->loader->add_filter('wpinv_details_metabox_titles', $plugin_admin, 'wpinv_quote_detail_metabox_titles', 10, 2);
        $this->loader->add_filter('wpinv_metabox_mail_notice', $plugin_admin, 'wpinv_quote_metabox_mail_notice', 10, 2);
        $this->loader->add_filter('post_row_actions', $plugin_admin, 'wpinv_quote_post_row_actions', 9999, 2);
        $this->loader->add_action('save_post_wpi_quote', $plugin_admin, 'wpinv_send_quote_after_save', 100, 1);
        $this->loader->add_action('wpinv_should_update_invoice_status', $plugin_admin, 'wpinv_quote_should_update_quote_status', 100, 4);
        $this->loader->add_action('wpinv_update_status', $plugin_admin, 'wpinv_quote_record_status_change', 100, 3);
        $this->loader->add_filter('wpinv_send_quote', $plugin_admin, 'wpinv_send_customer_quote', 10, 1);
        $this->loader->add_filter( 'wpinv_convert_quote_to_invoice', $plugin_admin, 'wpinv_convert_quote_to_invoice' );
        $this->loader->add_filter( 'admin_notices', $plugin_admin, 'wpinv_quote_admin_notices' );
        $this->loader->add_filter('wpinv_admin_js_localize', $plugin_admin, 'wpinv_quote_admin_js_localize', 10, 1);

        $this->loader->add_filter('wpinv_settings_tabs', $plugin_admin, 'wpinv_quote_settings_tabs', 10, 1);
        $this->loader->add_filter('wpinv_settings_sections', $plugin_admin, 'wpinv_quote_settings_sections', 10, 1);
        $this->loader->add_filter('wpinv_registered_settings', $plugin_admin, 'wpinv_quote_registered_settings', 10, 1);
        $this->loader->add_filter('wpinv_format_invoice_number', $plugin_admin, 'wpinv_format_quote_number', 10, 3);

        $this->loader->add_filter('wpinv_email_before_note_details', $plugin_admin, 'wpinv_quote_email_before_note_details', 10, 4);
        $this->loader->add_filter('wpinv_get_emails', $plugin_admin, 'wpinv_quote_mail_settings');
        $this->loader->add_filter('wpinv_email_recipient', $plugin_admin, 'wpinv_quote_email_recipient', 10, 4);
        $this->loader->add_filter('wpinv_email_before_quote_details', $plugin_admin, 'wpinv_email_before_quote_details', 10, 2);
        $this->loader->add_filter('wpinv_email_details_title', $plugin_admin, 'wpinv_quote_email_details_title', 10, 2);
        $this->loader->add_filter('wpinv_email_details_number', $plugin_admin, 'wpinv_quote_email_details_number', 10, 2);
        $this->loader->add_filter('wpinv_email_details_date', $plugin_admin, 'wpinv_quote_email_details_date', 10, 2);
        $this->loader->add_filter('wpinv_email_details_status', $plugin_admin, 'wpinv_quote_email_details_status', 10, 2);
        $this->loader->add_filter('wpinv_status_pending_to_wpi-quote-sent', $plugin_admin, 'wpinv_user_quote_notification', 10, 1);
        $this->loader->add_filter('wpinv_status_wpi-quote-sent_to_wpi-quote-cancelled', $plugin_admin, 'wpinv_user_quote_cancelled_notification', 10, 1);

        $this->loader->add_filter('wpinv_quote_action', $plugin_admin, 'wpinv_front_quote_actions', 10, 3);

    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Wpinv_Quotes_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wpinv_invoice_display_left_actions', $plugin_public, 'wpinv_quote_display_left_actions');
        $this->loader->add_action( 'wpinv_invoice_display_right_actions', $plugin_public, 'wpinv_quote_display_right_actions', 10, 1 );
        $this->loader->add_action( 'wpinv_invoice_print_head', $plugin_public, 'wpinv_quote_print_head_styles' );
        $this->loader->add_action( 'wpinv_before_user_invoices_template', $plugin_public, 'wpinv_quote_before_user_invoices_template', 10, 1 );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Wpinv_Quotes_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }
}