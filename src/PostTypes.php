<?php
/**
 * Custom post type registration.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Registers event, organizer, and venue post types.
 */
class PostTypes {

	/**
	 * Register CPTs.
	 */
	public static function register() {
		register_post_type(
			'event',
			array(
				'label'           => __( 'Events', 'wp-events' ),
				'labels'          => array(
					'name'          => __( 'Events', 'wp-events' ),
					'singular_name' => __( 'Event', 'wp-events' ),
					'menu_name'     => __( 'WP Events', 'wp-events' ),
				),
				'public'          => true,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => 'events' ),
				'menu_icon'       => 'dashicons-calendar-alt',
				'taxonomies'      => array( 'event_category', 'event_tag' ),
				'capability_type' => 'event',
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'organizer',
			array(
				'label'        => __( 'Organizers', 'wp-events' ),
				'labels'       => array(
					'name'          => __( 'Organizers', 'wp-events' ),
					'singular_name' => __( 'Organizer', 'wp-events' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title' ),
				'rewrite'      => array( 'slug' => 'organizers' ),
				'show_in_menu' => 'edit.php?post_type=event',
				'taxonomies'   => array( 'organizer_category' ),
			)
		);

		register_post_type(
			'venue',
			array(
				'label'        => __( 'Venues', 'wp-events' ),
				'labels'       => array(
					'name'          => __( 'Venues', 'wp-events' ),
					'singular_name' => __( 'Venue', 'wp-events' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array( 'title', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'venues' ),
				'show_in_menu' => 'edit.php?post_type=event',
				'taxonomies'   => array( 'venue_category' ),
			)
		);
	}
}
