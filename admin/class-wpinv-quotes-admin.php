<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/admin
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
                new Wpinv_Quotes_Admin_Metaboxes();
                new Wpinv_Quotes_Admin_Pages();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpinv_Quotes_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpinv_Quotes_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpinv-quotes-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpinv_Quotes_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpinv_Quotes_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpinv-quotes-admin.js', array( 'jquery' ), $this->version, false );

	}
        
        function resend_invoice_metabox_text($text){
            $text = array(
                'message'       => esc_attr__( 'This will send a copy of the quote to the user&#8217;s email address.', 'invoicing' ),
                'button_text'   =>  __( 'Resend Quote', 'invoicing' ),
            );
            return $text;
        }
        
        function invoice_detail_metabox_titles($title, $invoice){
            if($invoice->post_type == 'wpi_quote'){
                $title['status'] = __( 'Quote Status:', 'invoicing' );
                $title['number'] = __( 'Quote Number:', 'invoicing' );
            }                
            return $title;
        }
        
        function wpinv_quote_statuses($statuses, $invoice){
            if($invoice->post_type == 'wpi_quote'){
                    $statuses = array(
                        'pending'       => __( 'Waiting approval', 'invoicing' ),
                        'cancelled'     => __( 'Cancelled', 'invoicing' ),
                        'accepted'       => __( 'Accepted', 'invoicing' )
                    );
            }
            return $statuses;
        }
        
        function wpinv_after_quote_accepted( $post_id, $post, $update = false ) {
            // unhook this function so it doesn't loop infinitely
            remove_action( 'save_post', 'wpinv_after_quote_accepted' );

            // $post_id and $post are required
            if ( empty( $post_id ) || empty( $post ) ) {
                return;
            }

            // Dont' save meta boxes for revisions or autosaves
            if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
                return;
            }
            
            if($post->post_status == 'accepted' and $post->post_type == 'wpi_quote'){
                wp_update_post(array( 'ID'=>$post_id, 'post_status'=> 'pending', 'post_type' => 'wpi_invoice' )); //Change post type to invoice.
                
                $invoice = new WPInv_Invoice( $post_id );
                
                $invoice->add_note( __('Quote approved as an invoice.', 'invoicing') );
                
                wpinv_user_invoice_notification( $post_id ); //Send email for new invoice.
            }
            
            // re-hook this function
            add_action( 'save_post', 'wpinv_after_quote_accepted' );
        }
}

class Wpinv_Quotes_Admin_Metaboxes{
    function __construct(){
        add_action( 'add_meta_boxes', array($this, 'wpinv_add_meta_boxes'), 30, 2 );
    }
    
    
        function wpinv_add_meta_boxes( $post_type, $post ) {
            global $wpi_mb_invoice;
            if ( $post_type == 'wpi_quote' && !empty( $post->ID ) ) {
                $wpi_mb_invoice = wpinv_get_quote( $post->ID );
            }
            
            if ( !empty( $wpi_mb_invoice ) && !$wpi_mb_invoice->has_status( array( 'draft', 'auto-draft' ) ) ) {
                add_meta_box( 'wpinv-mb-resend-invoice', __( 'Resend Quote', 'invoicing' ), 'WPInv_Meta_Box_Details::resend_invoice', 'wpi_quote', 'side', 'high' );
            }

            if ( !empty( $wpi_mb_invoice ) && $wpi_mb_invoice->is_recurring() && $wpi_mb_invoice->is_parent() ) {
                add_meta_box( 'wpinv-mb-subscriptions', __( 'Subscriptions', 'invoicing' ), 'WPInv_Meta_Box_Details::subscriptions', 'wpi_quote', 'side', 'high' );
            }
            
            if ( wpinv_is_subscription_payment( $wpi_mb_invoice ) ) {
                add_meta_box( 'wpinv-mb-renewals', __( 'Renewal Payment', 'invoicing' ), 'WPInv_Meta_Box_Details::renewals', 'wpi_quote', 'side', 'high' );
            }
            
            add_meta_box( 'wpinv-details', __( 'Quote Details', 'invoicing' ), 'WPInv_Meta_Box_Details::output', 'wpi_quote', 'side', 'default' );
            add_meta_box( 'wpinv-payment-meta', __( 'Payment Meta', 'invoicing' ), 'WPInv_Meta_Box_Details::payment_meta', 'wpi_quote', 'side', 'default' );

            add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_quote', 'normal', 'high' );
            add_meta_box( 'wpinv-items', __( 'Quote Items', 'invoicing' ), 'WPInv_Meta_Box_Items::output', 'wpi_quote', 'normal', 'high' );
            add_meta_box( 'wpinv-notes', __( 'Quote Notes', 'invoicing' ), 'WPInv_Meta_Box_Notes::output', 'wpi_quote', 'normal', 'high' );
        }
}

class Wpinv_Quotes_Admin_Pages{
    public function __construct() {
        add_filter( 'post_row_actions', array($this, 'wpinv_post_row_actions'), 9999, 2 );
    }
    
    function wpinv_post_row_actions( $actions, $post ) {
            $post_type = !empty( $post->post_type ) ? $post->post_type : '';

            if ( $post_type == 'wpi_quote' ) {
                $actions = array();
            }
            return $actions;
        }
}