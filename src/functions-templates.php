<?php
/**
 * Template helper functions.
 *
 * @package WPEvents
 */

/**
 * Load a WP Events template part with fallback to plugin directory.
 *
 * First tries the theme's wp-events folder, then falls back to plugin templates.
 *
 * @param string      $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template (optional).
 * @return void
 */
function wpevents_get_template_part( $slug, $name = null ) {
	$templates = array();

	if ( $name ) {
		$templates[] = "wp-events/{$slug}-{$name}.php";
	}
	$templates[] = "wp-events/{$slug}.php";

	$located = locate_template( $templates, false, false );

	if ( ! $located ) {
		if ( $name && file_exists( WPEVENTS_PLUGIN_DIR . "templates/{$slug}-{$name}.php" ) ) {
			$located = WPEVENTS_PLUGIN_DIR . "templates/{$slug}-{$name}.php";
		} elseif ( file_exists( WPEVENTS_PLUGIN_DIR . "templates/{$slug}.php" ) ) {
			$located = WPEVENTS_PLUGIN_DIR . "templates/{$slug}.php";
		}
	}

	if ( $located ) {
		load_template( $located, false );
	}
}

/**
 * Return the HTML status badge for an event.
 *
 * @param int $event_id Event post ID. Defaults to the current post.
 * @return string Escaped HTML or empty string.
 */
function wpevents_get_status_badge( $event_id = 0 ) {
	if ( ! $event_id ) {
		$event_id = get_the_ID();
	}

	return \WPEvents\AdditionalFeatures::get_status_badge_html( $event_id );
}

/**
 * Print the HTML status badge for an event.
 *
 * @param int $event_id Event post ID. Defaults to the current post.
 * @return void
 */
function wpevents_the_status_badge( $event_id = 0 ) {
	echo wpevents_get_status_badge( $event_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in getter.
}
