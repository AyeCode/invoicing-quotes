<?php
/**
 * REST API Quotes controller
 *
 * Handles requests to the quotes endpoint.
 *
 * @package  Wpinv_Quotes
 * @since    1.0.6
 */

if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * REST API quotes controller class.
 *
 * @package Wpinv_Quotes
 */
class WPInv_REST_Quotes_Controller extends WPInv_REST_Invoice_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_quote';

    /**
	 * Constructor.
	 *
	 * @since 1.0.6
	 *
	 * @param string $namespace Api Namespace
	 */
	public function __construct( $namespace ) {
        
        // Set api namespace...
		$this->namespace = $namespace;

        // ... and the rest base
        $this->rest_base = 'quotes';
		
    }
	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.6
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/accept',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'accept_quote' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/decline',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'decline_quote' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'reason' => array(
							'type'        => 'string',
							'default'     => '',
							'description' => __( 'The reason for declining this quote.', 'wpinv-quotes' ),
						),
					),
				),
			)
		);

	}

	/**
	 * Checks if a quote can be read.
	 * 
	 * A quote can be read by site admins and owners of the quote
	 *
	 *
	 * @since 1.0.6
	 *
	 * @param WPInv_Invoice $quote WPInv_Invoice object.
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $quote ) {

		if ( get_current_user_id() === $quote->get_user_id() ) {
			return true;
		}
		return wpinv_current_user_can_manage_invoicing();
    }
	
	/**
	 * Marks a quote as accepted.
	 *
	 * @since 1.0.6
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function accept_quote( $request ) {

		$quote = $this->get_post( $request['id'] );

		// Ensure the post exists.
		if ( is_wp_error( $quote ) ) {
			return $quote;
		}

		$admin = new Wpinv_Quotes_Admin( 'wpinv-quotes', WPINV_QUOTES_VERSION );
		$admin->process_quote_published( $quote->ID );

		do_action( 'wpinv_rest_convert_quote_to_invoice', $quote->ID );

		return rest_ensure_response( true );

	}

	/**
	 * Marks a quote as declined.
	 *
	 * @since 1.0.6
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function decline_quote( $request ) {

		$quote = $this->get_post( $request['id'] );

		// Ensure the post exists.
		if ( is_wp_error( $quote ) ) {
			return $quote;
		}

		// Prepare variables.
		$old_status          = 'wpi-quote-pending';
		$new_status          = 'wpi-quote-declined';
		$old_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename( $old_status, $quote );
        $new_status_nicename = Wpinv_Quotes_Shared::wpinv_quote_status_nicename( $new_status, $quote );
		$reason              = ! empty( $request['reason'] ) ? wp_kses_post( $request['reason'] ) : '';

		if ( ! empty( $reason ) ) {
            $status_change = wp_sprintf( __( 'Quote was %s via API. Reason: %s', 'wpinv-quotes' ), $new_status_nicename, $reason );
        } else {
            $status_change = wp_sprintf( __( 'Quote status changed from %s to %s via API.', 'wpinv-quotes' ), $old_status_nicename, $new_status_nicename );
		}

		do_action( 'wpinv_rest_decline_quote_before_process', $quote->ID, $old_status, $new_status, $request );
		
		$quote->add_note( $status_change, false, false, true );// Add note

		// Process the request.
		update_post_meta( $quote->ID, '_wpinv_quote_decline_reason', $reason );
		$admin = new Wpinv_Quotes_Admin( 'wpinv-quotes', WPINV_QUOTES_VERSION );
		$admin->process_quote_declined( $quote->ID, $reason );

		do_action( 'wpinv_rest_after_decline_quote', $quote->ID, $request );

		return rest_ensure_response( true );

	}

	/**
	 * Retrieves the quote's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.6
	 *
	 * @return array Quote schema data.
	 */
	public function get_item_schema() {

		// Maybe retrieve the schema from cache.
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',

			// Base properties for every quote.
			'properties' 		  => array(

				'title'			  => array(
					'description' => __( 'The title for the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'user_id'		  => array(
					'description' => __( 'The user ID of the quote recipient.', 'wpinv-quotes' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),

				'email'		  	  => array(
					'description' => __( 'The email of the quote recipient.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),

				'ip'			  => array(
					'description' => __( 'The IP of the quote recipient.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'user_info'       => array(
					'description' => __( 'Information about the quote recipient.', 'wpinv-quotes' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(

						'first_name'      => array(
							'description' => __( 'The first name of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'last_name'       => array(
							'description' => __( 'The last name of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'company'         => array(
							'description' => __( 'The company of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'vat_number'      => array(
							'description' => __( 'The VAT number of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'vat_rate'        => array(
							'description' => __( 'The VAT rate applied on the quote.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'address'        => array(
							'description' => __( 'The address of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'city'            => array(
							'description' => __( 'The city of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'country'         => array(
							'description' => __( 'The country of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'state'           => array(
							'description' => __( 'The state of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'zip'             => array(
							'description' => __( 'The zip code of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'phone'             => array(
							'description' => __( 'The phone number of the quote recipient.', 'wpinv-quotes' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),

				'id'           => array(
					'description' => __( 'Unique identifier for the quote.', 'wpinv-quotes' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'number'		  => array(
					'description' => __( 'The quote number.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'total'	  		  => array(
					'description' => __( 'The total amount of the quote.', 'wpinv-quotes' ),
					'type'        => 'number',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'discount'		  => array(
					'description' => __( 'The discount applied to the quote.', 'wpinv-quotes' ),
					'type'        => 'number',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'discount_code'	  => array(
					'description' => __( 'The discount code applied to the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'tax'	  		  => array(
					'description' => __( 'The tax applied on the quote.', 'wpinv-quotes' ),
					'type'        => 'number',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'fees_total'	  => array(
					'description' => __( 'The total fees of the quote.', 'wpinv-quotes' ),
					'type'        => 'number',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'subtotal'	  	  => array(
					'description' => __( 'The sub-total for the quote.', 'wpinv-quotes' ),
					'type'        => 'number',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'currency'	  	  => array(
					'description' => __( 'The currency used on the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'cart_details'	  => array(
					'description' => __( 'The cart details for quote.', 'wpinv-quotes' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'	  => true,
				),

				'date'         => array(
					'description' => __( "The date the quote was created, in the site's timezone.", 'wpinv-quotes' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'valid_until'     => array(
					'description' => __( 'The date until which this quote is valid.', 'wpinv-quotes' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				
				'link'         => array(
					'description' => __( 'URL to the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'slug'       	  => array(
					'description' => __( 'An alphanumeric identifier for the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
					'readonly'    => true,
				),

				'status'       	  => array(
					'description' => __( 'A named status for the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'enum'        => $this->get_post_statuses(),
					'context'     => array( 'view', 'edit' ),
					'default'	  => 'wpi-quote-pending',
				),

				'status_nicename' => array(
					'description' => __( 'A human-readable status name for the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'post_type'       => array(
					'description' => __( 'The post type for the quote.', 'wpinv-quotes' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the quote schema for the REST API.
		 *
		 * Enables adding extra properties to quotes.
		 *
		 * @since 1.0.6
		 *
		 * @param array   $schema    The quote schema.
		 */
        $schema = apply_filters( "wpinv_rest_quote_schema", $schema );

		// Cache the quote schema.
		$this->schema = $schema;
		
		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 1.0.6
	 *
	 * @param WPInv_Invoice $quote Quote Object.
	 * @return array Links for the given quote.
	 */
	protected function prepare_links( $quote ) {

		// Prepare the base REST API endpoint for quotes.
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'     => array(
				'href' => rest_url( trailingslashit( $base ) . $quote->ID ),
			),
			'accept'   => array(
				'href' => rest_url( trailingslashit( $base ) . $quote->ID . '/accept' ),
			),
			'decline'  => array(
				'href' => rest_url( trailingslashit( $base ) . $quote->ID . '/decline' ),
			),
			'collection' => array(
				'href'   => rest_url( $base ),
			),
		);

		if ( ! empty( $quote->get_user_id() ) ) {
			$links['user'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $quote->user_id ),
				'embeddable' => true,
			);
		}

		/**
		 * Filters the returned quote links for the REST API.
		 *
		 * Enables adding extra links to quote API responses.
		 *
		 * @since 1.0.6
		 *
		 * @param array   $links    Rest links.
		 * @param WPInv_Invoice $quote Quote Object.
		 */
		return apply_filters( "wpinv_rest_quote_links", $links, $quote );

	}

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 1.0.6
	 *
	 * @param WPInv_Invoice   $quote    Quote object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $quote, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		/**
		 * Filters the available quote link relations for the REST API.
		 *
		 * Enables adding extra link relation for the current user and request to quote responses.
		 *
		 * @since 1.0.6
		 *
		 * @param array           $rels    Available link relations.
		 * @param WPInv_Invoice   $quote   Quote object.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( "wpinv_rest_quote_link_relations", $rels, $quote, $request );
	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.6
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return array_keys( Wpinv_Quotes_Shared::wpinv_get_quote_statuses() );
	}
    
}