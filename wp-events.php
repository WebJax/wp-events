<?php
/**
 * Plugin Name: WP Events
 * Description: Custom Events plugin with Organizer and Venue relations, recurrence, JSON-LD, shortcodes, and import helpers.
 * Version: 0.1.0
 * Author: WebJax
 * License: GPL-2.0-or-later
 * Text Domain: wp-events
 * Domain Path: /languages
 *
 * @package WPEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPEVENTS_VERSION', '0.1.0' );
define( 'WPEVENTS_PLUGIN_FILE', __FILE__ );
define( 'WPEVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPEVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'plugins_loaded',
	function() {
		load_plugin_textdomain( 'wp-events', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
);

// Includes
require_once WPEVENTS_PLUGIN_DIR . 'includes/template-functions.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-cpt.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-schema.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-shortcodes.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-admin.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-recurrence.php';
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-filters.php';
// Load clean blocks class instead
require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-blocks-clean.php';
if ( file_exists( WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-import-tribe.php' ) ) {
	require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-import-tribe.php';
}

// New features
if ( file_exists( WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-ical.php' ) ) {
	require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-ical.php';
}
if ( file_exists( WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-woocommerce.php' ) ) {
	require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-woocommerce.php';
}
if ( file_exists( WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-organizer-capabilities.php' ) ) {
	require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-organizer-capabilities.php';
}
if ( file_exists( WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-additional-features.php' ) ) {
	require_once WPEVENTS_PLUGIN_DIR . 'includes/class-wpevents-additional-features.php';
}

add_action( 'init', function() {
	WPEvents_CPT::register();
	
	// Add theme support for post thumbnails if not already enabled
	if ( ! current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails', array( 'event', 'venue', 'organizer' ) );
	} else {
		// Just add support for our post types
		add_post_type_support( 'event', 'thumbnail' );
		add_post_type_support( 'venue', 'thumbnail' );
		add_post_type_support( 'organizer', 'thumbnail' );
	}
});

add_action( 'init', function() {
	// Register shortcodes after CPTs exist
	WPEvents_Shortcodes::register();
});

add_action( 'admin_init', function() {
	WPEvents_Admin::register_columns();
});

add_action( 'save_post_event', function( $post_id, $post, $update ) {
	WPEvents_Recurrence::maybe_generate_recurrences( $post_id, $post, $update );
}, 10, 3 );

add_action( 'wp_head', function() {
	WPEvents_Schema::print_json_ld();
}, 99 );

add_action( 'plugins_loaded', function() {
	WPEvents_Blocks_Clean::init();
	
	// Initialize new features if classes exist
	if ( class_exists( 'WPEvents_iCal' ) ) {
		WPEvents_iCal::init();
	}
	if ( class_exists( 'WPEvents_WooCommerce' ) ) {
		WPEvents_WooCommerce::init();
	}
	if ( class_exists( 'WPEvents_Organizer_Capabilities' ) ) {
		WPEvents_Organizer_Capabilities::init();
	}
	if ( class_exists( 'WPEvents_Additional_Features' ) ) {
		WPEvents_Additional_Features::init();
	}
});

register_activation_hook( __FILE__, function() {
	// Register CPTs before flushing
	WPEvents_CPT::register();
	
	// Add organizer role if class exists
	if ( class_exists( 'WPEvents_Organizer_Capabilities' ) ) {
		WPEvents_Organizer_Capabilities::add_organizer_role();
	}
	
	flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function() {
	flush_rewrite_rules();
});
