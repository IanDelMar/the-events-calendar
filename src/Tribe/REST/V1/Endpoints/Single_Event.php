<?php


class Tribe__Events__REST__V1__Endpoints__Single_Event
	extends Tribe__Events__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__GET_Endpoint_Interface,
	Tribe__REST__Endpoints__POST_Endpoint_Interface,
	Tribe__REST__Endpoints__DELETE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * @var Tribe__REST__Main
	 */
	protected $main;

	/**
	 * @var WP_REST_Request
	 */
	protected $serving;
	/**
	 * @var Tribe__Events__REST__Interfaces__Post_Repository
	 */
	protected $post_repository;

	/**
	 * @var Tribe__Events__REST__V1__Validator__Interface
	 */
	protected $validator;

	/**
	 * @var Tribe__Events__REST__V1__Endpoints__Linked_Post_Endpoint_Interface
	 */
	protected $venue_endpoint;

	/**
	 * @var Tribe__Events__REST__V1__Endpoints__Linked_Post_Endpoint_Interface
	 */
	protected $organizer_endpoint;

	/**
	 * Tribe__Events__REST__V1__Endpoints__Single_Event constructor.
	 *
	 * @param Tribe__REST__Messages_Interface                                    $messages
	 * @param Tribe__Events__REST__Interfaces__Post_Repository                   $post_repository
	 * @param Tribe__Events__REST__V1__Validator__Interface                      $validator
	 * @param Tribe__Events__REST__V1__Endpoints__Linked_Post_Endpoint_Interface $venue_endpoint
	 */
	public function __construct(
		Tribe__REST__Messages_Interface $messages,
		Tribe__Events__REST__Interfaces__Post_Repository $post_repository,
		Tribe__Events__REST__V1__Validator__Interface $validator,
		Tribe__Events__REST__V1__Endpoints__Linked_Post_Endpoint_Interface $venue_endpoint,
		Tribe__Events__REST__V1__Endpoints__Linked_Post_Endpoint_Interface $organizer_endpoint
	) {
		parent::__construct( $messages );
		$this->post_repository = $post_repository;
		$this->validator = $validator;
		$this->venue_endpoint = $venue_endpoint;
		$this->organizer_endpoint = $organizer_endpoint;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error An array containing the data on success or a WP_Error instance on failure.
	 */
	public function get( WP_REST_Request $request ) {
		$this->serving = $request;

		$event = get_post( $request['id'] );

		$cap = get_post_type_object( Tribe__Events__Main::POSTTYPE )->cap->read_post;
		if ( ! ( 'publish' === $event->post_status || current_user_can( $cap, $request['id'] ) ) ) {
			$message = $this->messages->get_message( 'event-not-accessible' );

			return new WP_Error( 'event-not-accessible', $message, array( 'status' => 403 ) );
		}

		$data = $this->post_repository->get_event_data( $request['id'] );

		return is_wp_error( $data ) ? $data : new WP_REST_Response( $data );
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of informations rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$GET_defaults = array( 'in' => 'query', 'default' => '', 'type' => 'string' );
		$POST_defaults = array( 'in' => 'body', 'default' => '', 'type' => 'string' );

		return array(
			'get'  => array(
				'parameters' => $this->swaggerize_args( $this->GET_args(), $GET_defaults ),
				'responses'  => array(
					'200' => array(
						'description' => __( 'Returns the data of the event with the specified post ID', 'the-event-calendar' ),
						'schema'      => array(
							'$ref' => '#/definitions/Event',
						),
					),
					'400' => array(
						'description' => __( 'The event post ID is missing.', 'the-events-calendar' ),
					),
					'403' => array(
						'description' => __( 'The event with the specified ID is not accessible.', 'the-events-calendar' ),
					),
					'404' => array(
						'description' => __( 'An event with the specified ID does not exist.', 'the-events-calendar' ),
					),
				),
			),
			'post' => array(
				'parameters' => $this->swaggerize_args( $this->POST_args(), $POST_defaults ),
				'responses'  => array(
					'201' => array(
						'description' => __( 'Returns the data of the created event', 'the-event-calendar' ),
						'schema'      => array(
							'$ref' => '#/definitions/Event',
						),
					),
					'400' => array(
						'description' => __( 'A required parameter is missing or an input parameter is in the wrong format', 'the-events-calendar' ),
					),
					'403' => array(
						'description' => __( 'The user is not authorized to create events', 'the-events-calendar' ),
					),
				),
			),
		);
	}

	/**
	 * Provides the content of the `args` array to register the endpoint support for GET requests.
	 *
	 * @return array
	 */
	public function GET_args() {
		return array(
			'id' => array(
				'in'                => 'path',
				'type'              => 'integer',
				'description'       => __( 'the event post ID', 'the-events-calendar' ),
				'required'          => true,
				'validate_callback' => array( $this->validator, 'is_event_id' ),
			),
		);
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @return array
	 */
	public function POST_args() {
		return array(
			// Post fields
			'author'             => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_user_id' ),
				'type'              => 'integer',
				'description'       => __( 'The event author ID', 'the-events-calendar' ),
			),
			'date'               => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_time' ),
				'type'              => 'string',
				'description'       => __( 'The event publication date', 'the-events-calendar' ),
			),
			'date_utc'           => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_time' ),
				'type'              => 'string',
				'description'       => __( 'The event publication date (UTC timezone)', 'the-events-calendar' ),
			),
			'title'              => array(
				'required'          => true,
				'validate_callback' => array( $this->validator, 'is_string' ),
				'type'              => 'string',
				'description'       => __( 'The event title', 'the-events-calendar' ),
			),
			'description'        => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_string' ),
				'type'              => 'string',
				'description'       => __( 'The event description', 'the-events-calendar' ),
			),
			'excerpt'            => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_string' ),
				'type'              => 'string',
				'description'       => __( 'The event excerpt', 'the-events-calendar' ),
			),
			'status'             => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_status' ),
				'type'              => 'string',
				'description'       => __( 'The event post status', 'the-events-calendar' ),
			),
			// Event meta fields
			'timezone'           => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_timezone' ),
				'type'              => 'string',
				'description'       => __( 'The event timezone', 'the-events-calendar' ),
			),
			'all_day'            => array(
				'required'    => false,
				'default'     => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the event lasts the whole day or not', 'the-events-calendar' ),
			),
			'start_date'         => array(
				'required'          => true,
				'validate_callback' => array( $this->validator, 'is_time' ),
				'type'              => 'string',
				'description'       => __( 'The event start date and time', 'the-events-calendar' ),
			),
			'end_date'           => array(
				'required'          => true,
				'validate_callback' => array( $this->validator, 'is_time' ),
				'type'              => 'string',
				'description'       => __( 'The event end date and time', 'the-events-calendar' ),
			),
			'image'              => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_image' ),
				'type'              => 'string',
				'description'       => __( 'The event featured image ID or URL', 'the-events-calendar' ),
			),
			'cost'               => array(
				'required'     => false,
				'swagger_type' => 'string',
				'description'  => __( 'The event cost', 'the-events-calendar' ),
			),
			'website'            => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_url' ),
				'swagger_type'      => 'string',
				'description'       => __( 'The event website', 'the-events-calendar' ),
			),
			// Event presentation data
			'show_map'           => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the event should show a map or not', 'the-events-calendar' ),
			),
			'show_map_link'      => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the event should show a map link or not', 'the-events-calendar' ),
			),
			'hide_from_listings' => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether events should be hidden in the calendar view or not', 'the-events-calendar' ),
			),
			'sticky'             => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the event should be sticky in the calendar view or not', 'the-events-calendar' ),
			),
			'featured'           => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether the event should be featured on the site or not', 'the-events-calendar' ),
			),
			// Linked Posts
			'venue'              => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_venue_id_or_entry' ),
				'swagger_type'      => 'array',
				'description'       => __( 'The event venue ID or data', 'the-events-calendar' ),
			),
			'organizer'          => array(
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_organizer_id_or_entry' ),
				'swagger_type'      => 'array',
				'description'       => __( 'The event organizer IDs or data', 'the-events-calendar' ),
			),
		);
	}

	/**
	 * Handles POST requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 * @param bool            $return_id Whether the created post ID should be returned or the full response object.
	 *
	 * @return WP_Error|WP_REST_Response|int An array containing the data on success or a WP_Error instance on failure.
	 */
	public function post( WP_REST_Request $request, $return_id = false ) {
		$this->serving = $request;

		$post_object = get_post_type_object( Tribe__Events__Main::POSTTYPE );
		$can_publish = current_user_can( $post_object->cap->publish_posts );
		$can_edit_others_posts = current_user_can( $post_object->cap->edit_others_posts );
		$events_cat = Tribe__Events__Main::TAXONOMY;

		$post_data = isset( $request['date'] ) ? Tribe__Date_Utils::reformat( $request['date'], 'Y-m-d H:i:s' ) : false;
		$post_date_gmt = isset( $request['date_utc'] ) ? Tribe__Timezones::localize_date( 'Y-m-d H:i:s', $request['date_utc'], 'UTC' ) : false;

		$postarr = array(
			// Post fields
			'post_author'           => $request['author'],
			'post_date'             => $post_data,
			'post_date_gmt'         => $post_date_gmt,
			'post_title'            => $request['title'],
			'post_content'          => $request['description'],
			'post_excerpt'          => $request['excerpt'],
			'post_status'           => $this->scale_back_post_status( $request['status'], Tribe__Events__Main::POSTTYPE ),
			// Event data
			'EventTimezone'         => $request['timezone'],
			'EventAllDay'           => tribe_is_truthy( $request['all_day'] ),
			'EventStartDate'        => Tribe__Date_Utils::reformat( $request['start_date'], 'Y-m-d' ),
			'EventStartTime'        => Tribe__Date_Utils::reformat( $request['start_date'], 'H:i:s' ),
			'EventEndDate'          => Tribe__Date_Utils::reformat( $request['end_date'], 'Y-m-d' ),
			'EventEndTime'          => Tribe__Date_Utils::reformat( $request['end_date'], 'H:i:s' ),
			'FeaturedImage'         => tribe_upload_image( $request['image'] ),
			'EventCost'             => $request['cost'],
			'EventCurrencyPosition' => tribe( 'cost-utils' )->parse_currency_position( $request['cost'] ),
			'EventCurrencySymbol'   => tribe( 'cost-utils' )->parse_currency_symbol( $request['cost'] ),
			'EventURL'              => filter_var( $request['website'], FILTER_SANITIZE_URL ),
			// Taxonomies
			'tax_input'             => array_filter( array(
				$events_cat => Tribe__Terms::translate_terms_to_ids( $request['categories'], $events_cat ),
				'post_tag'  => Tribe__Terms::translate_terms_to_ids( $request['tags'], 'post_tag' ),
			) ),
		);

		$venue = $this->venue_endpoint->insert( $request['venue'] );

		if ( is_wp_error( $venue ) ) {
			return $venue;
		}

		$postarr['venue'] = $venue;

		$organizer = $this->organizer_endpoint->insert( $request['organizer'] );

		if ( is_wp_error( $organizer ) ) {
			return $organizer;
		}

		$postarr['organizer'] = $organizer;

		if ( $can_publish && $can_edit_others_posts ) {
			$postarr = array_merge( $postarr, array(
				// Event presentation data
				'EventShowMap'          => tribe_is_truthy( $request['show_map'] ),
				'EventShowMapLink'      => tribe_is_truthy( $request['show_map_link'] ),
				'EventHideFromUpcoming' => tribe_is_truthy( $request['hide_from_listings'] ) ? 'yes' : false,
				'EventShowInCalendar'   => tribe_is_truthy( $request['sticky'] ),
				'feature_event'         => tribe_is_truthy( $request['featured'] ),
			) );
		}

		$id = Tribe__Events__API::createEvent( array_filter( $postarr ) );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( $return_id ) {
			return $id;
		}

		$data = $this->post_repository->get_event_data( $id );

		$response = new WP_REST_Response( $data );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * @return bool Whether the current user can post or not.
	 */
	public function can_post() {
		$cap = get_post_type_object( Tribe__Events__Main::POSTTYPE )->cap->edit_posts;

		return current_user_can( $cap );
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @return array
	 */
	public function DELETE_args() {
		return $this->GET_args();
	}

	/**
	 * Handles DELETE requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data of the trashed post on
	 *                                   success or a WP_Error instance on failure.
	 */
	public function delete( WP_REST_Request $request ) {
		$event_id = $request['id'];

		$event = get_post($event_id);

		if ( 'trash' === $event->post_status ) {
			$message = $this->messages->get_message( 'event-is-in-trash' );

			return new WP_Error( 'event-is-in-trash', $message, array( 'status' => 410 ) );
		}

		/**
		 * Filters the event delete operation.
		 *
		 * Returning a non `null` value here will override the default trashing operation.
		 *
		 * @param int|bool        $deleted Whether the event was successfully deleted or not.
		 * @param WP_REST_Request $request The original API request.
		 *
		 * @since TBD
		 */
		$deleted = apply_filters( 'tribe_events_rest_event_delete', null, $request );
		if ( null === $deleted ) {
			$deleted = wp_trash_post( $event_id );
		}

		if ( false === $deleted ) {
			$message = $this->messages->get_message( 'could-not-delete-event' );

			return new WP_Error( 'could-not-delete-event', $message, array( 'status' => 500 ) );
		}

		$data = $this->post_repository->get_event_data( $event_id );

		return is_wp_error( $data ) ? $data : new WP_REST_Response( $data );
	}

	/**
	 * @return bool Whether the current user can delete or not.
	 */
	public function can_delete() {
		$cap = get_post_type_object( Tribe__Events__Main::POSTTYPE )->cap->delete_posts;

		return current_user_can( $cap );
	}
}
