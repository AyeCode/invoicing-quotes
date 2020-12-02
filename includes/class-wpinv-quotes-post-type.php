<?php

/**
 * Registers and manages the quote post type.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    wpinv-quotes Quotes
 * @subpackage wpinv-quotes Quotes - POST TYPES
 */

/**
 * Registers and manages the quote post type.
 *
 * @package    wpinv-quotes Quotes
 * @subpackage wpinv-quotes Quotes - POST TYPES
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class WPInv_Quotes_Post_Type {

    /**
     * Class constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {

        add_action( 'init', array( $this, 'register_post_type' ), 2 );
        add_action( 'init', array( $this, 'register_post_statuses' ), 5 );
        add_action( 'invoicing_quotes_after_register_post_types', array( $this, 'maybe_flush_rewrite_rules' ) );
        add_filter( 'manage_wpi_quote_posts_columns', array( $this, 'filter_quote_columns' ), 100 );
        add_filter( 'manage_wpi_quote_posts_custom_column', array( $this, 'display_quote_columns' ), 100, 2 );
        add_filter( 'manage_edit-wpi_quote_sortable_columns', '__return_empty_array', 100 );
		add_filter( 'post_row_actions', array( $this, 'filter_post_row_actions' ), 95, 2 );
		add_filter( 'bulk_actions-edit-wpi_quote', array( $this, 'remove_bulk_actions' ) );

    }

    /**
     * Registers the quote custom post type
     *
     * @since    1.0.0
     */
    public function register_post_type() {

        // Register the quote post type.
		register_post_type(
			'wpi_quote',
			apply_filters(
				'wpinv_register_post_type_quote',
				array(
					'labels'                 => array(
						'name'                  => __( 'Quotes', 'wpinv-quotes' ),
						'singular_name'         => __( 'Quote', 'wpinv-quotes' ),
						'all_items'             => __( 'Quotes', 'wpinv-quotes' ),
						'menu_name'             => _x( 'Quotes', 'Admin menu name', 'wpinv-quotes' ),
						'add_new'               => __( 'Add New', 'wpinv-quotes' ),
						'add_new_item'          => __( 'Add new quote', 'wpinv-quotes' ),
						'edit'                  => __( 'Edit', 'wpinv-quotes' ),
						'edit_item'             => __( 'Edit quote', 'wpinv-quotes' ),
						'new_item'              => __( 'New quote', 'wpinv-quotes' ),
						'view_item'             => __( 'View quote', 'wpinv-quotes' ),
						'view_items'            => __( 'View Quotes', 'wpinv-quotes' ),
						'search_items'          => __( 'Search Quotes', 'wpinv-quotes' ),
						'not_found'             => __( 'No quotes found', 'wpinv-quotes' ),
						'not_found_in_trash'    => __( 'No quotes found in trash', 'wpinv-quotes' ),
						'parent'                => __( 'Parent quotes', 'wpinv-quotes' ),
						'featured_image'        => __( 'Quote image', 'wpinv-quotes' ),
						'set_featured_image'    => __( 'Set quote image', 'wpinv-quotes' ),
						'remove_featured_image' => __( 'Remove quote image', 'wpinv-quotes' ),
						'use_featured_image'    => __( 'Use as quote image', 'wpinv-quotes' ),
						'insert_into_item'      => __( 'Insert into quote', 'wpinv-quotes' ),
						'uploaded_to_this_item' => __( 'Uploaded to this quote', 'wpinv-quotes' ),
						'filter_items_list'     => __( 'Filter quotes', 'wpinv-quotes' ),
						'items_list_navigation' => __( 'Quotes navigation', 'wpinv-quotes' ),
						'items_list'            => __( 'Quotes list', 'wpinv-quotes' ),
					),
					'description'           => __( 'This is where quotes are stored.', 'wpinv-quotes' ),
					'public'                => true,
					'has_archive'           => false,
					'publicly_queryable'    => true,
        			'exclude_from_search'   => true,
        			'show_ui'               => true,
					'show_in_menu'          => wpinv_current_user_can_manage_invoicing() ? 'wpinv' : false,
					'show_in_nav_menus'     => false,
					'supports'              => array( 'title', 'author', 'excerpt'  ),
					'rewrite'               => array(
						'slug'              => 'quote',
						'with_front'        => false,
					),
					'query_var'             => false,
					'capability_type'       => 'wpi_quote',
					'map_meta_cap'          => true,
					'show_in_admin_bar'     => true,
					'can_export'            => true,
					'hierarchical'          => false,
					'menu_position'         => null,
					'menu_icon'             => 'dashicons-media-spreadsheet',
				)
			)
        );

        do_action( 'invoicing_quotes_after_register_post_types' );
    }

    /**
     * Register the quote statuses.
     *
     * @since    1.0.0
     */
    function register_post_statuses() {


        $quote_statuses = apply_filters(
			'getpaid_register_quote_post_statuses',
			array(

				'wpi-quote-pending' => array(
					'label'                     => _x( 'Pending Confirmation', 'Quote status', 'wpinv-quotes' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of quotes */
        			'label_count'               => _n_noop( 'Pending Confirmation <span class="count">(%s)</span>', 'Pending Confirmation <span class="count">(%s)</span>', 'wpinv-quotes' )
                ),
                
                'wpi-quote-accepted' => array(
					'label'                     => _x( 'Accepted', 'Quote status', 'wpinv-quotes' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of quotes */
        			'label_count'               => _n_noop( 'Accepted <span class="count">(%s)</span>', 'Accepted <span class="count">(%s)</span>', 'wpinv-quotes' )
                ),
                
                'wpi-quote-declined' => array(
					'label'                     => _x( 'Declined', 'Quote status', 'wpinv-quotes' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of quotes */
        			'label_count'               => _n_noop( 'Declined <span class="count">(%s)</span>', 'Declined <span class="count">(%s)</span>', 'wpinv-quotes' )
				),

			)
		);

		foreach ( $quote_statuses as $quote_status => $args ) {
			register_post_status( $quote_status, $args );
        }

    }

    /**
	 * Flush rules to prevent 404.
	 *
	 */
	public function maybe_flush_rewrite_rules() {

		if ( ! get_option( 'wpinv_quotes_flushed_rewrite_rules' ) ) {
			update_option( 'wpinv_quotes_flushed_rewrite_rules', '1' );
			flush_rewrite_rules();
        }

    }

    /**
	 * Returns an array of quotes table columns.
	 */
	public function filter_quote_columns( $columns ) {

		$columns = array(
			'cb'                => $columns['cb'],
			'number'            => __( 'Quote', 'wpinv-quotes' ),
			'customer'          => __( 'Customer', 'wpinv-quotes' ),
			'invoice_date'      => __( 'Date', 'wpinv-quotes' ),
			'amount'            => __( 'Amount', 'wpinv-quotes' ),
			'recurring'         => __( 'Recurring', 'wpinv-quotes' ),
			'status'            => __( 'Status', 'wpinv-quotes' ),
		);

		return apply_filters( 'wpi_quote_table_columns', $columns );
    }

    /**
	 * Displays quotes table columns.
	 */
	public function display_quote_columns( $column_name, $post_id ) {

		$invoice = new WPInv_Invoice( $post_id );

		switch ( $column_name ) {

			case 'invoice_date' :
				$date_time = esc_attr( $invoice->get_created_date() );
				$date      = sanitize_text_field( getpaid_format_date_value( $date_time ) );
				echo "<span title='$date_time'>$date</span>";
				break;

			case 'amount' :

				$amount = $invoice->get_total();
				$formated_amount = wpinv_price( $amount, $invoice->get_currency() );

				if ( $invoice->is_refunded() ) {
					$refunded_amount = wpinv_price( 0, $invoice->get_currency() );
					echo "<del>$formated_amount</del>&nbsp;<ins>$refunded_amount</ins>";
				} else {

					$discount = $invoice->get_total_discount();

					if ( ! empty( $discount ) ) {
						$new_amount = wpinv_price( floatval( $amount + $discount ), $invoice->get_currency() );
						echo "<del>$new_amount</del>&nbsp;<ins>$formated_amount</ins>";
					} else {
						echo $formated_amount;
					}

				}

				break;

			case 'status' :
				$status       = sanitize_text_field( $invoice->get_status() );
				$status_label = sanitize_text_field( $invoice->get_status_nicename() );

				echo "<mark class='getpaid-invoice-status $status'><span>$status_label</span></mark>";

				// Invoice view status.
                if ( wpinv_is_invoice_viewed( $invoice->get_id() ) ) {
                    echo '&nbsp;&nbsp;<i class="fa fa-eye wpi-help-tip" title="'. esc_attr__( 'Viewed by Customer', 'invoicing' ).'"></i>';
                } else {
                    echo '&nbsp;&nbsp;<i class="fa fa-eye-slash wpi-help-tip" title="'. esc_attr__( 'Not Viewed by Customer', 'invoicing' ).'"></i>';
                }

				break;

			case 'recurring':

				if ( $invoice->is_recurring() ) {
					echo '<i class="fa fa-check" style="color:#43850a;"></i>';
				} else {
					echo '<i class="fa fa-times" style="color:#616161;"></i>';
				}
				break;

			case 'number' :

				$edit_link       = esc_url( get_edit_post_link( $invoice->get_id() ) );
				$invoice_number  = sanitize_text_field( $invoice->get_number() );
				$invoice_details = esc_attr__( 'View Quote Details', 'invoicing' );

				echo "<a href='$edit_link' title='$invoice_details'><strong>$invoice_number</strong></a>";

				break;

			case 'customer' :
	
				$customer_name = $invoice->get_user_full_name();
	
				if ( empty( $customer_name ) ) {
					$customer_name = $invoice->get_email();
				}
	
				if ( ! empty( $customer_name ) ) {
					$customer_details = esc_attr__( 'View Customer Details', 'invoicing' );
					$view_link        = esc_url( add_query_arg( 'user_id', $invoice->get_user_id(), admin_url( 'user-edit.php' ) ) );
					echo "<a href='$view_link' title='$customer_details'><span>$customer_name</span></a>";
				} else {
					echo '<div>&mdash;</div>';
				}

				break;

		}

    }

	/**
     * Remove bulk edit option from admin side quote listing
     *
     * @since    1.0.0
     * @param array $actions post actions
     * @return array $actions actions without edit option
     */
    public function remove_bulk_actions( $actions ) {

		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}
	 	return $actions;

	}

    /**
     * Remove bulk edit option from admin side quote listing
     *
     * @since    1.0.0
     * @param array $actions post actions
	 * @param WP_Post $post
     * @return array $actions actions without edit option
     */
    public function filter_post_row_actions( $actions, $post ) {

        if ( 'wpi_quote' == $post->post_type ) {

			$invoice = new WPInv_Invoice( $post );

			if ( ! $invoice->is_draft() ) {

				$actions['send'] =  sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'getpaid-admin-action' => 'send_quote',
									'invoice_id'           => $invoice->get_id()
								)
							),
							'getpaid-nonce',
							'getpaid-nonce'
						)
					),
					esc_html( __( 'Send to Customer', 'wpinv_quotes' ) )
				);

				if ( $invoice->has_status( 'wpi-quote-pending' ) ) {

					$actions['convert'] =  sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'getpaid-admin-action' => 'convert_quote_to_invoice',
										'invoice_id'           => $invoice->get_id()
									)
								),
								'getpaid-nonce',
								'getpaid-nonce'
							)
						),
						esc_html( __( 'Convert to Invoice', 'wpinv_quotes' ) )
					);

				}
				

			}

        }

        return $actions;
    }

}
