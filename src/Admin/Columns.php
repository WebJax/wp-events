<?php
/**
 * Admin list columns for events.
 *
 * @package WPEvents
 */

namespace WPEvents\Admin;

/**
 * Event admin columns and sorting.
 */
class Columns {

	/**
	 * Register column hooks.
	 */
	public static function register() {
		add_filter( 'manage_event_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_event_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_meta' ) );
	}

	/**
	 * Add custom columns after title.
	 *
	 * @param array $cols Existing columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['event_start']     = __( 'Date', 'wp-events' );
				$new['event_venue']     = __( 'Venue', 'wp-events' );
				$new['event_organizer'] = __( 'Organizer', 'wp-events' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'event_start' === $column ) {
			$start = get_post_meta( $post_id, 'event_start', true );
			if ( $start ) {
				$time_format = get_option( 'time_format' );
				$date_format = get_option( 'date_format' );
				echo esc_html( wp_date( "$date_format $time_format", strtotime( $start ) ) );
			}
		}
		if ( 'event_venue' === $column ) {
			$venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
			if ( $venue_id ) {
				echo '<a href="' . esc_url( get_edit_post_link( $venue_id ) ) . '">' . esc_html( get_the_title( $venue_id ) ) . '</a>';
			}
		}
		if ( 'event_organizer' === $column ) {
			$org_ids = (array) get_post_meta( $post_id, 'event_organizer', true );
			$names   = array_filter( array_map( 'get_the_title', array_map( 'intval', $org_ids ) ) );
			if ( $names ) {
				echo esc_html( implode( ', ', $names ) );
			}
		}
	}

	/**
	 * Mark sortable columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function sortable_columns( $cols ) {
		$cols['event_start'] = 'event_start';
		return $cols;
	}

	/**
	 * Sort by event_start meta.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function sort_by_meta( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->get( 'post_type' ) !== 'event' ) {
			return;
		}
		if ( $query->get( 'orderby' ) === 'event_start' ) {
			$query->set( 'meta_key', 'event_start' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
