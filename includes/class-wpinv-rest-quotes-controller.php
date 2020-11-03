<?php
/**
 * REST quotes controller.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API quotes controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Quotes_Controller extends GetPaid_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_quote';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.13
	 * @var string
	 */
	protected $rest_base = 'quotes';

	/** Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class = 'WPInv_Invoice';
	
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
	 * Retrieves the query params for the quotes collection.
	 *
	 * @since 1.0.7
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = array_merge(

			parent::get_collection_params(),

			array(


				'customers' => array(
					'description'       => __( 'Limit result set to quotes for specific user ids.', 'wpinv-quotes' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'default'           => array(),
					'sanitize_callback' => 'wp_parse_id_list',
				),

				'exclude_customers'  	=> array(
					'description' 		=> __( 'Exclude quotes to specific users.', 'wpinv-quotes' ),
					'type'        		=> 'array',
					'items'       		=> array(
						'type'          => 'integer',
					),
					'default'     		=> array(),
					'sanitize_callback' => 'wp_parse_id_list',
				),

				'parent'  	            => array(
					'description'       => __( 'Limit result set to those of particular parent IDs.', 'wpinv-quotes' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'sanitize_callback' => 'wp_parse_id_list',
					'default'           => array(),
				),

				'parent_exclude'  	    => array(
					'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'wpinv-quotes' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'sanitize_callback' => 'wp_parse_id_list',
					'default'           => array(),
				),

			)

		);

		// Filter collection parameters for the quotes controller.
		return apply_filters( 'getpaid_rest_quotes_collection_params', $params, $this );
	}

	/**
	 * Determine the allowed query_vars for a get_items() response and
	 * prepare for WP_Query.
	 *
	 * @param array           $prepared_args Prepared arguments.
	 * @param WP_REST_Request $request Request object.
	 * @return array          $query_args
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {

		$query_args = parent::prepare_items_query( $prepared_args );

		// Retrieve quotes for specific customers.
		if ( ! empty( $request['customers'] ) ) {
			$query_args['author__in'] = $request['customers'];
		}

		// Skip quotes for specific customers.
		if ( ! empty( $request['exclude_customers'] ) ) {
			$query_args['author__not_in'] = $request['exclude_customers'];
		}

		return apply_filters( 'getpaid_rest_quotes_prepare_items_query', $query_args, $request, $this );

	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.7
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return array( 'wpi-quote-pending', 'wpi-quote-accepted', 'wpi-quote-declined' );
	}

	/**
	 * Saves a single quote.
	 *
	 * @param WPInv_Invoice $invoice Invoice to save.
	 * @return WP_Error|WPInv_Invoice
	 */
	protected function save_object( $invoice ) {
		$invoice->recalculate_total();
		return parent::save_object( $invoice );
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

		$quote = $this->get_object( $request['id'] );

		// Ensure the post exists.
		if ( is_wp_error( $quote ) ) {
			return $quote;
		}

		new Wpinv_Quotes_Converter( $quote, 'accept' );

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

		$quote = $this->get_object( $request['id'] );

		// Ensure the post exists.
		if ( is_wp_error( $quote ) ) {
			return $quote;
		}

		if ( ! empty( $request['reason'] ) ) {
			update_post_meta( $quote->get_id(), '_wpinv_quote_decline_reason', wp_kses_post( $request['reason'] ) );
		}

		new Wpinv_Quotes_Converter( $quote, 'decline' );

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
			'title'      => 'wpi_quote',
			'type'       => 'object',

			// Base properties for every quote.
			'properties' => wpinv_get_data( 'invoice-schema' ),
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
				'href' => rest_url( trailingslashit( $base ) . $quote->get_id() ),
			),
			'accept'   => array(
				'href' => rest_url( trailingslashit( $base ) . $quote->get_id() . '/accept' ),
			),
			'decline'  => array(
				'href' => rest_url( trailingslashit( $base ) . $quote->get_id() . '/decline' ),
			),
			'user'     => array(
				'href'       => rest_url( 'wp/v2/users/' . $quote->get_user_id() ),
				'embeddable' => true,
			),
			'collection' => array(
				'href'   => rest_url( $base ),
			),
		);

		$links['user'] = array(
			'href'       => rest_url( 'wp/v2/users/' . $quote->get_user_id() ),
			'embeddable' => true,
		);

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

}
