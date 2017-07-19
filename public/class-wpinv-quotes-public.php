<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpinv_Quotes
 * @subpackage Wpinv_Quotes/public
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Wpinv_Quotes_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpinv-quotes-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpinv-quotes-public.js', array( 'jquery' ), $this->version, false );

	}
        
        function quote_statuses($invoice_statuses){
            if(is_singular('wpi_quote')){
                wpi_quote_statuses();
            }
            return $invoice_statuses;
        }
        
        public function quote_left_buttons($invoice){
            if ($invoice->post_type == 'wpi_quote' and $invoice->post_status != 'cancelled'){
                remove_query_arg('wpi_action');
                add_query_arg( array( 'wpi_action' => 'process_quote' ));
                ?>
                    <button class="btn btn-success btn-sm accept-quote" title="<?php esc_attr_e( 'Accept This Quotation', 'invoicing' ); ?>" onclick="showAlert('accept')" ><?php _e( 'Accept Quotation', 'invoicing' ); ?></button> &nbsp;
                    <button class="btn btn-danger btn-sm decline-quote" title="<?php esc_attr_e( 'Decline This Quotation', 'invoicing' ); ?>" onclick="showAlert('decline')" ><?php _e( 'Decline Quotation', 'invoicing' ); ?></button>
                    <p id="accept-alert" class="alert alert-success"><?php _e('An invoice will be generated on acceptance. ') ?> <a class="btn btn-success btn-xs accept-quote" title="<?php esc_attr_e( 'Accept This Quotation', 'invoicing' ); ?>" href="<?php echo esc_url( wpinv_get_checkout_uri()); ?>?wpi_action=quote_action&action=accept&invoice_key=<?php echo $invoice->get_key() ?>&qid=<?php echo $invoice->ID ?>"><?php _e( 'Continue', 'invoicing' ); ?></a></p>
                    <p id="decline-alert" class="alert alert-danger"><?php _e('You are going to reject this quote. ') ?> <a class="btn btn-danger btn-xs decline-quote" title="<?php esc_attr_e( 'Decline This Quotation', 'invoicing' ); ?>" href="<?php echo esc_url( wpinv_get_checkout_uri() ); ?>?wpi_action=quote_action&action=decline&qid=<?php echo $invoice->ID ?>"><?php _e( 'Continue', 'invoicing' ); ?></a>
                    <script>
                        function showAlert(action) {
                            var x = document.getElementById('accept-alert');
                            var y = document.getElementById('decline-alert');
                            if(action == 'accept'){
                                y.style.display = 'none';
                                x.style.display = 'block';
                            } else{
                                x.style.display = 'none';
                                y.style.display = 'block';
                            }
                        } 
                    </script>
                <?php
            }
        }
        
        public function quote_right_buttons($invoice){
            if($invoice->post_type == 'wpi_quote'){
                $user_id = (int)$invoice->get_user_id();
                $current_user_id = (int)get_current_user_id();

                if ( $user_id > 0 && $user_id == $current_user_id ) {
                ?>
                    <a class="btn btn-primary btn-sm" onclick="window.print();" href="javascript:void(0)"><?php _e( 'Print Quote', 'invoicing' ); ?></a> &nbsp;
                    <a class="btn btn-warning btn-sm" href="<?php echo esc_url( wpinv_get_history_page_uri() ); ?>"><?php _e( 'History', 'invoicing' ); ?></a>
                <?php } 
            }
        }


        public function quote_actions($request){
            if($request['action'] ==  'accept') {
                $status = 'accepted';

            }
            elseif($request['action'] ==  'decline') {
                $status = 'cancelled';
            }
            $invoice = wp_update_post(array(
                    'ID' => $request['qid'],
                    'post_status' => $status,
            ));
            
            wp_redirect(get_post_permalink($request['qid']));
            exit();
        }

}
