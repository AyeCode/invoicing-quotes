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
        
        function admin_enqueue_scripts($localize){
            global $post;
            
            wp_enqueue_script( 'wpinv-admin-script' );
            $localize = array();
            $localize['save_quote'] = __( 'Save Quote', 'invoicing' );
            if(isset($post->ID) and $post->post_type == 'wpi_quote') wp_localize_script( 'wpinv-admin-script', 'WPInv_Admin_Quote', $localize );
        }
        
        /*
         * This function corrects the url of email icons.
         */
        function quotes_custom_column_values($value, $post_id, $column_name){
            global $post; $wpi_invoice;
            if(!isset($wpi_invoice)) $wpi_invoice = new WPInv_Invoice( $post->ID );
            if(get_post_type($post->ID) == 'wpi_quote' and $column_name == 'wpi_actions'){
                $value = '';
                if ( !empty( $post->post_name ) ) {
                    $value .= '<a title="' . esc_attr__( 'Print invoice', 'invoicing' ) . '" href="' . esc_url( get_permalink( $post->ID ) ) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-print"><i style="" class="fa fa-print"></i></span></a>';
                }
                
                if ( $email = $wpi_invoice->get_email() ) {
                    $value .= '<a title="' . esc_attr__( 'Send invoice to customer', 'invoicing' ) . '" href="' . esc_url( add_query_arg( array( 'wpi_action' => 'send_quote', 'invoice_id' => $post->ID ) ) ) . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-email-alt"></span></a>';
                }
            }
            return $value;
        }
        
        function wpinv_quote_mail_settings($emails){
            $user_quote = array(
                'email_user_quote_header' => array(
                    'id'   => 'email_user_quote_header',
                    'name' => '<h3>' . __( 'Customer Quote', 'invoicing' ) . '</h3>',
                    'desc' => __( 'Quote emails can be sent to customers containing their quote information.', 'invoicing' ),
                    'type' => 'header',
                ),
                'email_user_quote_active' => array(
                    'id'   => 'email_user_quote_active',
                    'name' => __( 'Enable/Disable', 'invoicing' ),
                    'desc' => __( 'Enable this email notification', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => 1
                ),
                'email_user_quote_subject' => array(
                    'id'   => 'email_user_quote_subject',
                    'name' => __( 'Subject', 'invoicing' ),
                    'desc' => __( 'Enter the subject line for the quote receipt email.', 'invoicing' ),
                    'type' => 'text',
                    'std'  => __( '[{site_title}] Your quote from {invoice_date}', 'invoicing' ),
                    'size' => 'large'
                ),
                'email_user_quote_heading' => array(
                    'id'   => 'email_user_quote_heading',
                    'name' => __( 'Email Heading', 'invoicing' ),
                    'desc' => __( 'Enter the the main heading contained within the email notification for the quote receipt email.', 'invoicing' ),
                    'type' => 'text',
                    'std'  => __( 'Your quote {invoice_number} details', 'invoicing' ),
                    'size' => 'large'
                ),
                'email_user_quote_admin_bcc' => array(
                    'id'   => 'email_user_quote_admin_bcc',
                    'name' => __( 'Enable Admin BCC', 'invoicing' ),
                    'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => 1
                ),
            );
                    
            $emails['user_quote'] = $user_quote;
            
            return $emails;
        }
        
        function resend_quote_metabox_text($text){
            $text = array(
                'message'       => esc_attr__( 'This will send a copy of the quote to the user&#8217;s email address.', 'invoicing' ),
                'button_text'   =>  __( 'Resend Quote', 'invoicing' ),
            );
            return $text;
        }
        
        function resend_quote_email_actions($email_actions, $post_id){
            $email_actions['email_url'] = add_query_arg( array( 'wpi_action' => 'send_quote', 'invoice_id' => $post_id ) );
            return $email_actions;
        }
        
        function invoice_detail_metabox_titles($title, $invoice){
            if($invoice->post_type == 'wpi_quote'){
                $title['status'] = __( 'Quote Status:', 'invoicing' );
                $title['number'] = __( 'Quote Number:', 'invoicing' );
            }                
            return $title;
        }
        
        function quote_statuses($invoice_statuses){
            global $post;
            
            if(isset($post) and $post->post_type == 'wpi_quote'){
                $invoice_statuses = wpi_quote_statuses();
            }
            return $invoice_statuses;
        }
        
        function wpinv_metabox_mail_notice(){
            return __('This will send a copy of the quote to the user&#8217;s email address.', 'invoicing');
        }
        
        function wpinv_send_quote_after_save( $post_id ) {
            // If this is just a revision, don't send the email.
            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            if ( !current_user_can( 'manage_options' ) || get_post_type( $post_id ) != 'wpi_quote'  ) {
                return;
            }

            if ( !empty( $_POST['wpi_save_send'] ) ) {
                $this->wpinv_user_quote_notification( $post_id );
            }
        }
        
        function wpinv_user_quote_notification( $invoice_id ) {
            global $wpinv_email_search, $wpinv_email_replace;
            
            $email_type = 'user_quote'; // alias of user_invoice.

            $invoice = wpinv_get_invoice( $invoice_id );
            if ( empty( $invoice ) ) {
                return false;
            }

            $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
            if ( !is_email( $recipient ) ) {
                return false;
            }

            $search                     = array();
            $search['invoice_number']   = '{invoice_number}';
            $search['invoice_date']     = '{invoice_date}';
            $search['name']             = '{name}';

            $replace                    = array();
            $replace['invoice_number']  = $invoice->get_number();
            $replace['invoice_date']    = $invoice->get_invoice_date();
            $replace['name']            = $invoice->get_user_full_name();

            $wpinv_email_search     = $search;
            $wpinv_email_replace    = $replace;
                        
            $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
            $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
            $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
            $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );
            
            $args = array(
                        'invoice'       => $invoice,
                        'email_type'    => $email_type,
                        'email_heading' => $email_heading,
                        'sent_to_admin' => false,
                        'plain_text'    => false,
                    );
            $content = wpinv_get_template_html( 
                        'emails/wpinv-email-user_quote.php', 
                        $args,
                        'wpinv-quotes/',
                        WP_PLUGIN_DIR.'/wpinv-quotes/templates/'
                    );

            $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

            if ( $sent ) {
                $note = __( 'Quote has been emailed to the user.', 'invoicing' );
            } else {
                $note = __( 'Fail to send quote to the user!', 'invoicing' );
            }
            $invoice->add_note( $note ); // Add private note.

            if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
                $recipient  = wpinv_get_admin_email();
                $subject    .= ' - ADMIN BCC COPY';
                wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
            }

            return $sent;
        }
        
        function wpinv_get_template($located, $template_name, $args, $template_path){
            $invoice = $args['invoice'];
            if($invoice->post_type == 'wpi_quote') $located = WP_PLUGIN_DIR.'/wpinv-quotes/templates/emails/wpinv-email-user_quote.php';
            return $located;
        }
        
        function wpinv_send_customer_quote( $data = array() ) {
            $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

            if ( empty( $invoice_id ) ) {
                return;
            }

            if ( !current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have permission to send invoice notification', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
            }

            $sent = $this->wpinv_user_quote_notification( $invoice_id );

            $status = $sent ? 'email_sent' : 'email_fail';

            $redirect = add_query_arg( array( 'wpinv-message' => $status, 'wpi_action' => false, 'invoice_id' => false ) );
            wp_redirect( $redirect );
            exit;
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
                
                $invoice->add_note( __('Quote accepted and converted to invoice.', 'invoicing') );
                
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
