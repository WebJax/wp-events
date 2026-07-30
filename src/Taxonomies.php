<?php
/**
 * Taxonomy registration.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Registers event, venue, and organizer taxonomies.
 */
class Taxonomies {

	/**
	 * Register taxonomies.
	 */
	public static function register() {
		register_taxonomy(
			'event_category',
			'event',
			array(
				'label'             => __( 'Event Categories', 'wp-events' ),
				'labels'            => array(
					'name'          => __( 'Event Categories', 'wp-events' ),
					'singular_name' => __( 'Event Category', 'wp-events' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'event-category' ),
			)
		);

		register_taxonomy(
			'event_tag',
			'event',
			array(
				'label'             => __( 'Event Tags', 'wp-events' ),
				'labels'            => array(
					'name'          => __( 'Event Tags', 'wp-events' ),
					'singular_name' => __( 'Event Tag', 'wp-events' ),
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'event-tag' ),
			)
		);

		register_taxonomy(
			'venue_category',
			'venue',
			array(
				'label'             => __( 'Venue Categories', 'wp-events' ),
				'labels'            => array(
					'name'          => __( 'Venue Categories', 'wp-events' ),
					'singular_name' => __( 'Venue Category', 'wp-events' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'venue-category' ),
			)
		);

		register_taxonomy(
			'organizer_category',
			'organizer',
			array(
				'label'             => __( 'Organizer Categories', 'wp-events' ),
				'labels'            => array(
					'name'          => __( 'Organizer Categories', 'wp-events' ),
					'singular_name' => __( 'Organizer Category', 'wp-events' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'organizer-category' ),
			)
		);
	}
}
