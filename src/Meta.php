<?php
/**
 * Post meta registration.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Registers event, organizer, and venue post meta.
 */
class Meta {

	/**
	 * Register all post meta.
	 */
	public static function register() {
		register_post_meta(
			'event',
			'event_start',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_iso8601' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'event_end',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_iso8601' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'event_price',
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_price' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'event_currency',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_currency' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);

		register_post_meta(
			'event',
			'event_organizer',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_ids_array' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'event_venue',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);

		register_post_meta(
			'event',
			'recurrence_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_recurrence_type' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'recurrence_interval',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'recurrence_end',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_date' ),
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'is_occurrence',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => array( __CLASS__, 'can_edit_event' ),
			)
		);
		register_post_meta(
			'event',
			'occurrence_of',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => array( __CLASS__, 'can_edit_event' ),
			)
		);

		register_post_meta(
			'organizer',
			'organizer_website',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		// PII: keep out of public REST responses.
		register_post_meta(
			'organizer',
			'organizer_phone',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_phone' ),
			)
		);
		register_post_meta(
			'organizer',
			'organizer_email',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_email',
			)
		);

		register_post_meta(
			'venue',
			'venue_address',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		// PII: keep out of public REST responses.
		register_post_meta(
			'venue',
			'venue_phone',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_phone' ),
			)
		);
		register_post_meta(
			'venue',
			'venue_email',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_email',
			)
		);
		register_post_meta(
			'venue',
			'venue_website',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_post_meta(
			'venue',
			'venue_facebook',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_post_meta(
			'venue',
			'venue_instagram',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		register_post_meta(
			'venue',
			'venue_other_social',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'   => 'string',
							'format' => 'uri',
						),
					),
				),
				'sanitize_callback' => array( Sanitizer::class, 'sanitize_urls_array' ),
			)
		);
	}

	/**
	 * Auth callback: user can edit the post.
	 *
	 * @param bool   $allowed  Whether the user can add the meta.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @param string $cap      Capability being checked.
	 * @param array  $caps     User capabilities.
	 * @return bool
	 */
	public static function can_edit_event( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
		return current_user_can( 'edit_post', $post_id );
	}
}
