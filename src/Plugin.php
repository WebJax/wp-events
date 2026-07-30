<?php
/**
 * Plugin bootstrap.
 *
 * @package WPEvents
 */

namespace WPEvents;

use WPEvents\Admin\Ajax;
use WPEvents\Admin\Columns;
use WPEvents\Admin\MetaBoxes;
use WPEvents\Import\Tribe;
use WPEvents\Import\TribeCLI;

/**
 * Main plugin orchestrator — named callbacks only (no closures for hooks).
 */
class Plugin {

	/**
	 * Wire all hooks.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_admin_columns' ) );
		add_action( 'save_post_event', array( __CLASS__, 'maybe_generate_recurrences' ), 10, 3 );
		add_action( 'wp_head', array( __CLASS__, 'print_json_ld' ), 99 );
		add_action( 'plugins_loaded', array( __CLASS__, 'init_features' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'wp-events', false, dirname( plugin_basename( WPEVENTS_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Register CPTs, taxonomies, meta, meta boxes, and AJAX.
	 */
	public static function register_post_types() {
		Taxonomies::register();
		PostTypes::register();
		Meta::register();
		MetaBoxes::register();
		Ajax::register();
		self::ensure_thumbnails();
	}

	/**
	 * Register shortcodes.
	 */
	public static function register_shortcodes() {
		Shortcodes::register();
	}

	/**
	 * Register admin list columns.
	 */
	public static function register_admin_columns() {
		Columns::register();
	}

	/**
	 * Generate recurrence occurrences on event save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public static function maybe_generate_recurrences( $post_id, $post, $update ) {
		Recurrence::maybe_generate_recurrences( $post_id, $post, $update );
	}

	/**
	 * Print JSON-LD in head.
	 */
	public static function print_json_ld() {
		Schema::print_json_ld();
	}

	/**
	 * Initialize feature modules.
	 */
	public static function init_features() {
		Blocks::init();
		TemplateLoader::init();
		ICal::init();
		WooCommerce::init();
		OrganizerCapabilities::init();
		AdditionalFeatures::init();
		QueryFilters::init();
		Tribe::register();

		if ( defined( 'WP_CLI' ) && class_exists( 'WP_CLI' ) ) {
			TribeCLI::register();
		}
	}

	/**
	 * Ensure thumbnail support for plugin post types.
	 */
	public static function ensure_thumbnails() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails', array( 'event', 'venue', 'organizer' ) );
		} else {
			add_post_type_support( 'event', 'thumbnail' );
			add_post_type_support( 'venue', 'thumbnail' );
			add_post_type_support( 'organizer', 'thumbnail' );
		}
	}

	/**
	 * Activation callback.
	 */
	public static function activate() {
		Taxonomies::register();
		PostTypes::register();
		OrganizerCapabilities::add_organizer_role();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
