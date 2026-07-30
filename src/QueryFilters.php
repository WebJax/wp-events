<?php
/**
 * Front-end event query filters.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Apply timeframe/sort/category filters on event archives.
 */
class QueryFilters {

	/**
	 * Hook into pre_get_posts.
	 */
	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'apply' ) );
	}

	/**
	 * Apply event filters to WP_Query.
	 *
	 * @param \WP_Query $query The WordPress query object.
	 */
	public static function apply( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! is_post_type_archive( 'event' ) && ! is_tax( 'event_category' ) && ! is_tax( 'event_tag' ) ) {
			return;
		}

		$timeframe = isset( $_GET['timeframe'] ) ? sanitize_text_field( wp_unslash( $_GET['timeframe'] ) ) : 'all';
		$sort_raw  = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'asc';
		$sort      = in_array( strtoupper( $sort_raw ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $sort_raw ) : 'ASC';
		$category  = isset( $_GET['event_category'] ) ? sanitize_title( wp_unslash( $_GET['event_category'] ) ) : '';

		$query->set( 'meta_key', 'event_start' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', $sort );

		$meta_query = array();
		$now        = current_time( 'mysql' );

		switch ( $timeframe ) {
			case 'upcoming':
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'DATETIME',
				);
				break;

			case 'past':
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => $now,
					'compare' => '<',
					'type'    => 'DATETIME',
				);
				break;

			case 'today':
				$today_start  = current_time( 'Y-m-d' ) . ' 00:00:00';
				$today_end    = current_time( 'Y-m-d' ) . ' 23:59:59';
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array( $today_start, $today_end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				);
				break;

			case 'this-week':
				$week_start   = date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ) ) . ' 00:00:00';
				$week_end     = date( 'Y-m-d', strtotime( 'sunday this week', current_time( 'timestamp' ) ) ) . ' 23:59:59';
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array( $week_start, $week_end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				);
				break;

			case 'this-month':
				$month_start  = date( 'Y-m-01', current_time( 'timestamp' ) ) . ' 00:00:00';
				$month_end    = date( 'Y-m-t', current_time( 'timestamp' ) ) . ' 23:59:59';
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array( $month_start, $month_end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME',
				);
				break;
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		if ( ! is_tax( 'event_category' ) && ! empty( $category ) ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'event_category',
						'field'    => 'slug',
						'terms'    => $category,
					),
				)
			);
		}
	}
}
