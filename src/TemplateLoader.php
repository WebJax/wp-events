<?php
/**
 * Theme/plugin template loader.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Loads single/archive/taxonomy templates from theme or plugin.
 */
class TemplateLoader {

	/**
	 * Register template_include filter.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
	}

	/**
	 * Template loader - checks theme first, then plugin templates
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * Filter hook to choose which files to find before WP does its thing.
			 *
			 * @param array $search_files Array of template files to search for.
			 * @param string $default_file The default template filename.
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template ) {
				$template = WPEVENTS_PLUGIN_DIR . 'templates/' . $default_file;
			}

			// Enqueue frontend assets for our templates
			if ( strpos( $template, 'wp-events' ) !== false || strpos( $template, WPEVENTS_PLUGIN_DIR ) !== false ) {
				add_action( 'wp_enqueue_scripts', array( Blocks::class, 'enqueue_frontend_assets' ), 20 );
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 */
	private static function get_template_loader_default_file() {
		if ( is_single() && get_post_type() === 'event' ) {
			$default_file = 'single-event.php';
		} elseif ( is_single() && get_post_type() === 'venue' ) {
			$default_file = 'single-venue.php';
		} elseif ( is_single() && get_post_type() === 'organizer' ) {
			$default_file = 'single-organizer.php';
		} elseif ( is_post_type_archive( 'event' ) ) {
			// Check for view parameter to load alternative templates
			$view          = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
			$allowed_views = array( 'list', 'calendar', 'compact' );

			if ( $view && in_array( $view, $allowed_views, true ) ) {
				$default_file = 'archive-event-' . $view . '.php';
			} else {
				$default_file = 'archive-event.php';
			}
		} elseif ( is_tax( 'event_category' ) ) {
			$default_file = 'taxonomy-event_category.php';
		} elseif ( is_tax( 'event_tag' ) ) {
			$default_file = 'taxonomy-event_tag.php';
		} else {
			$default_file = '';
		}

		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates = array();
		$template  = str_replace( WPEVENTS_PLUGIN_DIR . 'templates/', '', $default_file );

		if ( is_tax( 'event_category' ) || is_tax( 'event_tag' ) ) {
			$object = get_queried_object();

			// Look for specific term template first (e.g., taxonomy-event_category-udstilling.php)
			$specific_template = str_replace( '.php', '-' . $object->slug . '.php', $template );
			$templates[]       = 'wp-events/' . $specific_template;

			// Then general taxonomy template (e.g., taxonomy-event_category.php)
			$templates[] = 'wp-events/' . $template;
		} else {
			$templates[] = 'wp-events/' . $template;
		}

		// Add theme root fallback
		$templates[] = $template;

		return array_unique( $templates );
	}

}
