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
	 * Current site-local time as ISO 8601 (DATE_ATOM).
	 *
	 * @return string
	 */
	public static function now_iso() {
		return wp_date( DATE_ATOM, time(), wp_timezone() );
	}

	/**
	 * ISO 8601 timestamp for a local datetime string.
	 *
	 * @param string $local_datetime Local datetime, e.g. "2026-08-16 00:00:00".
	 * @return string
	 */
	public static function local_to_iso( $local_datetime ) {
		try {
			$dt = new \DateTimeImmutable( $local_datetime, wp_timezone() );
			return $dt->format( DATE_ATOM );
		} catch ( \Exception $e ) {
			unset( $e );
			return self::now_iso();
		}
	}

	/**
	 * Merge listing constraints onto a WP_Query / get_posts args array.
	 *
	 * Hides recurrence parents that already have a same-day occurrence so the
	 * series is not listed twice.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public static function constrain_event_listing_args( $args ) {
		$hidden = Recurrence::get_hidden_series_parent_ids();
		if ( empty( $hidden ) ) {
			return $args;
		}

		$existing = array();
		if ( isset( $args['post__not_in'] ) ) {
			$existing = array_map( 'absint', (array) $args['post__not_in'] );
		}
		$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $hidden ) ) );

		return $args;
	}

	/**
	 * Upcoming-from-now meta clause using CHAR comparison on ISO 8601 values.
	 *
	 * @return array
	 */
	public static function upcoming_start_clause() {
		return array(
			'key'     => 'event_start',
			'value'   => self::now_iso(),
			'compare' => '>=',
			'type'    => 'CHAR',
		);
	}

	/**
	 * Apply event filters to WP_Query.
	 *
	 * @param \WP_Query $query The WordPress query object.
	 */
	public static function apply( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
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

		$hidden = Recurrence::get_hidden_series_parent_ids();
		if ( ! empty( $hidden ) ) {
			$not_in = $query->get( 'post__not_in' );
			if ( ! is_array( $not_in ) ) {
				$not_in = array();
			}
			$query->set( 'post__not_in', array_values( array_unique( array_merge( $not_in, $hidden ) ) ) );
		}

		$meta_query = array();
		$now        = self::now_iso();

		switch ( $timeframe ) {
			case 'upcoming':
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'CHAR',
				);
				break;

			case 'past':
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => $now,
					'compare' => '<',
					'type'    => 'CHAR',
				);
				break;

			case 'today':
				$today        = wp_date( 'Y-m-d', time(), wp_timezone() );
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array(
						self::local_to_iso( $today . ' 00:00:00' ),
						self::local_to_iso( $today . ' 23:59:59' ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				);
				break;

			case 'this-week':
				$week_start   = wp_date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' ) ), wp_timezone() );
				$week_end     = wp_date( 'Y-m-d', strtotime( 'sunday this week', current_time( 'timestamp' ) ), wp_timezone() );
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array(
						self::local_to_iso( $week_start . ' 00:00:00' ),
						self::local_to_iso( $week_end . ' 23:59:59' ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				);
				break;

			case 'this-month':
				$month_start  = wp_date( 'Y-m-01', time(), wp_timezone() );
				$month_end    = wp_date( 'Y-m-t', time(), wp_timezone() );
				$meta_query[] = array(
					'key'     => 'event_start',
					'value'   => array(
						self::local_to_iso( $month_start . ' 00:00:00' ),
						self::local_to_iso( $month_end . ' 23:59:59' ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
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
