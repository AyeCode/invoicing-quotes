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
class Wpinv_Quotes {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wpinv_Quotes_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'wpinv-quotes';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
                add_action( 'init', array($this, 'wpinv_register_post_types') );
                //add_filter('wpinv_get_emails', array($this, 'wpinv_get_emails'));

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
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpinv-quotes-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpinv-quotes-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpinv-quotes-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wpinv-quotes-public.php';

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
	private function set_locale() {

		$plugin_i18n = new Wpinv_Quotes_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wpinv_Quotes_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
                $this->loader->add_filter('resend_invoice_metabox_text', $plugin_admin, 'resend_invoice_metabox_text');
                $this->loader->add_filter('wpinv_details_metabox_titles', $plugin_admin, 'invoice_detail_metabox_titles', 10, 2);
                $this->loader->add_filter('wpinv_invoice_statuses', $plugin_admin, 'wpinv_quote_statuses', 10, 2);
                $this->loader->add_filter('wpinv_metabox_mail_notice', $plugin_admin, 'wpinv_metabox_mail_notice', 10, 2);
                $this->loader->add_action('save_post', $plugin_admin, 'wpinv_after_quote_accepted', 10, 3);
                add_filter( 'manage_wpi_quote_posts_columns', 'wpinv_columns');
                add_filter( 'bulk_actions-edit-wpi_quote', 'wpinv_bulk_actions');
                add_filter( 'manage_edit-wpi_quote_sortable_columns', 'wpinv_sortable_columns' );
                add_action( 'manage_wpi_quote_posts_custom_column', 'wpinv_posts_custom_column');
                $this->loader->add_action( 'save_post_wpi_quote', $plugin_admin, 'wpinv_send_quote_after_save', 100, 1 );
                $this->loader->add_filter( 'wpinv_get_template', $plugin_admin, 'wpinv_get_template', 10, 4 );
	}   

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wpinv_Quotes_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
                $this->loader->add_action( 'wpinv_invoice_display_left_actions', $plugin_public, 'quote_left_buttons' );
                $this->loader->add_action( 'wpinv_invoice_display_right_actions', $plugin_public, 'quote_right_buttons' );
                $this->loader->add_action( 'wpinv_quote_action', $plugin_public, 'quote_actions');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wpinv_Quotes_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
        
        function wpinv_register_post_types() {
            $labels = array(
                'name'               => _x( 'Quotes', 'post type general name', 'invoicing' ),
                'singular_name'      => _x( 'Quote', 'post type singular name', 'invoicing' ),
                'menu_name'          => _x( 'Quotes', 'admin menu', 'invoicing' ),
                'name_admin_bar'     => _x( 'Quote', 'add new on admin bar', 'invoicing' ),
                'add_new'            => _x( 'Add New', 'book', 'invoicing' ),
                'add_new_item'       => __( 'Add New Quote', 'invoicing' ),
                'new_item'           => __( 'New Quote', 'invoicing' ),
                'edit_item'          => __( 'Edit Quote', 'invoicing' ),
                'view_item'          => __( 'View Quote', 'invoicing' ),
                'all_items'          => __( 'Quotes', 'invoicing' ),
                'search_items'       => __( 'Search Quotes', 'invoicing' ),
                'parent_item_colon'  => __( 'Parent Quotes:', 'invoicing' ),
                'not_found'          => __( 'No invoices found.', 'invoicing' ),
                'not_found_in_trash' => __( 'No invoices found in trash.', 'invoicing' )
            );
            $labels = apply_filters( 'wpinv_labels', $labels );

            $menu_icon = WPINV_PLUGIN_URL . '/assets/images/favicon.ico';

            $args = array(
                'labels'             => $labels,
                'description'        => __( 'This is where invoices are stored.', 'invoicing' ),
                'public'             => true,
                'can_export'         => true,
                '_builtin'           => false,
                'publicly_queryable' => true,
                'exclude_from_search'=> true,
                'show_ui'            => true,
                'show_in_menu'       => 'wpinv',
                'query_var'          => false,
                'rewrite'            => true,
                'capability_type'    => 'post',
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title', 'author' ),
                'menu_icon'          => $menu_icon,
            );

            register_post_type( 'wpi_quote', $args );
        }
        
        function wpinv_get_emails($emails){
            $emails['user_invoice'] = array(
                'email_user_invoice_subject' => array(
                    'id'   => 'email_user_invoice_subject',
                    'name' => __( 'Subject', 'invoicing' ),
                    'desc' => __( 'Enter the subject line for the quote receipt email.', 'invoicing' ),
                    'type' => 'text',
                    'std'  => __( '[{site_title}] Your Quote from {invoice_date}', 'invoicing' ),
                    'size' => 'large'
                ),
            );
            
            return $emails;
        }
}

function wpinv_get_quote( $invoice_id = 0, $cart = false ) {
    if ( $cart && empty( $invoice_id ) ) {
        $invoice_id = (int)wpinv_get_invoice_cart_id();
    }

    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice;
}