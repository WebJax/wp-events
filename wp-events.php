<?php
/**
 * Plugin Name: WP Events
 * Description: Custom Events plugin with Organizer and Venue relations, recurrence, JSON-LD, shortcodes, and import helpers.
 * Version: 0.9.0
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

define( 'WPEVENTS_VERSION', '0.9.0' );
define( 'WPEVENTS_PLUGIN_FILE', __FILE__ );
define( 'WPEVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPEVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPEVENTS_PLUGIN_DIR . 'vendor/autoload.php';

\WPEvents\Plugin::init();

register_activation_hook( __FILE__, array( \WPEvents\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WPEvents\Plugin::class, 'deactivate' ) );
