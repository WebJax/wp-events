<?php
/**
 * Custom post type registration.
 *
 * @package WPEvents
 */

namespace WPEvents;

use WPEvents\Admin\Settings;

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
					'all_items'     => __( 'All Events', 'wp-events' ),
					'add_new'       => __( 'Add New', 'wp-events' ),
					'add_new_item'  => __( 'Add New Event', 'wp-events' ),
					'edit_item'     => __( 'Edit Event', 'wp-events' ),
					'new_item'      => __( 'New Event', 'wp-events' ),
					'view_item'     => __( 'View Event', 'wp-events' ),
					'view_items'    => __( 'View Events', 'wp-events' ),
					'search_items'  => __( 'Search Events', 'wp-events' ),
				),
				'public'          => true,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'     => Settings::get_archive_slug(),
				'rewrite'         => array(
					'slug'       => Settings::get_single_slug(),
					'with_front' => false,
				),
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
