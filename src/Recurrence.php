<?php
/**
 * Recurrence generation.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Generate occurrence posts from recurrence rules.
 */
class Recurrence {

	/**
	 * Meta keys copied from parent onto each occurrence (excluding dates).
	 *
	 * @var string[]
	 */
	const COPIED_META_KEYS = array(
		'event_venue',
		'event_organizer',
		'event_price',
		'event_currency',
		'event_status',
		'enable_tickets',
		'ticket_product_ids',
		'ticket_product_id',
		'ticket_capacity',
		'enable_registration',
		'max_attendees',
		'registration_deadline',
		'require_approval',
		'assigned_organizer_users',
	);

	/**
	 * Hook listing/SEO helpers.
	 */
	public static function init() {
		add_filter( 'wp_robots', array( __CLASS__, 'filter_robots' ) );
	}

	/**
	 * Avoid indexing the series parent when a same-day occurrence is the public URL.
	 *
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public static function filter_robots( $robots ) {
		if ( ! is_singular( 'event' ) ) {
			return $robots;
		}

		$post_id = get_the_ID();
		if ( $post_id && in_array( (int) $post_id, self::get_hidden_series_parent_ids(), true ) ) {
			$robots['noindex'] = true;
		}

		return $robots;
	}

	/**
	 * Create or update occurrence posts when a parent event is saved.
	 *
	 * Existing occurrences are matched by date and updated in place so IDs,
	 * permalinks, orders, and registrations stay intact.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public static function maybe_generate_recurrences( $post_id, $post, $update ) {
		unset( $update );

		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( 'event' !== $post->post_type ) {
			return;
		}
		if ( get_post_meta( $post_id, 'is_occurrence', true ) ) {
			return;
		}

		$type = get_post_meta( $post_id, 'recurrence_type', true );
		if ( ! $type ) {
			return;
		}

		$interval = max( 1, (int) get_post_meta( $post_id, 'recurrence_interval', true ) );
		$end_date = get_post_meta( $post_id, 'recurrence_end', true );
		$start    = get_post_meta( $post_id, 'event_start', true );
		$end      = get_post_meta( $post_id, 'event_end', true );
		if ( ! $start || ! $end_date ) {
			return;
		}

		$start_ts = strtotime( $start );
		$end_ts   = $end ? strtotime( $end ) : null;
		$until_ts = strtotime( $end_date . ' 23:59:59' );
		if ( ! $start_ts || ! $until_ts ) {
			return;
		}

		$existing_by_date = self::get_existing_occurrences_by_date( $post_id );
		$keep_ids         = array();

		$cursor = $start_ts;
		$count  = 0;
		$max    = 200;
		while ( $cursor <= $until_ts && $count < $max ) {
			$date_key      = wp_date( 'Y-m-d', $cursor, wp_timezone() );
			$new_start     = wp_date( DATE_ATOM, $cursor, wp_timezone() );
			$new_end       = $end_ts ? wp_date( DATE_ATOM, $cursor + ( $end_ts - $start_ts ), wp_timezone() ) : '';
			$occurrence_id = isset( $existing_by_date[ $date_key ] ) ? (int) $existing_by_date[ $date_key ] : 0;

			$occurrence_id = self::upsert_occurrence( $post_id, $post, $occurrence_id, $cursor, $new_start, $new_end );
			if ( $occurrence_id > 0 ) {
				$keep_ids[] = $occurrence_id;
				unset( $existing_by_date[ $date_key ] );
			}

			$next = self::advance( $cursor, $type, $interval );
			if ( $next <= $cursor ) {
				break;
			}
			$cursor = $next;
			++$count;
		}

		foreach ( $existing_by_date as $stale_id ) {
			if ( $stale_id && ! in_array( (int) $stale_id, $keep_ids, true ) ) {
				wp_delete_post( (int) $stale_id, true );
			}
		}

		self::flush_hidden_parents_cache();
	}

	/**
	 * Map existing occurrence IDs by Y-m-d of event_start.
	 *
	 * @param int $parent_id Parent event ID.
	 * @return array<string,int>
	 */
	protected static function get_existing_occurrences_by_date( $parent_id ) {
		$query = new \WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'any',
				'meta_key'       => '_recurrence_parent',
				'meta_value'     => $parent_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$by_date = array();
		foreach ( $query->posts as $child_id ) {
			$child_start = get_post_meta( $child_id, 'event_start', true );
			$ts          = $child_start ? strtotime( $child_start ) : false;
			if ( ! $ts ) {
				$by_date[ 'id-' . $child_id ] = (int) $child_id;
				continue;
			}
			$key = wp_date( 'Y-m-d', $ts, wp_timezone() );
			if ( ! isset( $by_date[ $key ] ) ) {
				$by_date[ $key ] = (int) $child_id;
			}
		}

		return $by_date;
	}

	/**
	 * Parent event IDs that should be hidden from public listings.
	 *
	 * A series parent is hidden only when an occurrence exists on the same
	 * calendar day, so the first date is not listed twice.
	 *
	 * @return int[]
	 */
	public static function get_hidden_series_parent_ids() {
		$cached = wp_cache_get( 'hidden_series_parents', 'wpevents' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => 'event',
				'post_status'            => 'publish',
				'meta_key'               => '_recurrence_parent',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			)
		);

		$hidden = array();
		foreach ( $query->posts as $child_id ) {
			$parent_id = absint( get_post_meta( $child_id, '_recurrence_parent', true ) );
			if ( $parent_id <= 0 || isset( $hidden[ $parent_id ] ) ) {
				continue;
			}

			$child_start  = get_post_meta( $child_id, 'event_start', true );
			$parent_start = get_post_meta( $parent_id, 'event_start', true );
			$child_ts     = $child_start ? strtotime( $child_start ) : false;
			$parent_ts    = $parent_start ? strtotime( $parent_start ) : false;
			if ( ! $child_ts || ! $parent_ts ) {
				continue;
			}

			if ( wp_date( 'Y-m-d', $child_ts, wp_timezone() ) === wp_date( 'Y-m-d', $parent_ts, wp_timezone() ) ) {
				$hidden[ $parent_id ] = $parent_id;
			}
		}

		$hidden = array_values( $hidden );
		wp_cache_set( 'hidden_series_parents', $hidden, 'wpevents', HOUR_IN_SECONDS );

		return $hidden;
	}

	/**
	 * Drop the per-request listing cache after occurrences change.
	 */
	public static function flush_hidden_parents_cache() {
		wp_cache_delete( 'hidden_series_parents', 'wpevents' );
	}

	/**
	 * Create or update one occurrence post.
	 *
	 * @param int      $parent_id  Parent event ID.
	 * @param \WP_Post $parent     Parent post.
	 * @param int      $child_id   Existing child ID or 0.
	 * @param int      $timestamp  Occurrence start timestamp.
	 * @param string   $new_start  ISO start.
	 * @param string   $new_end    ISO end.
	 * @return int Occurrence post ID or 0.
	 */
	protected static function upsert_occurrence( $parent_id, $parent, $child_id, $timestamp, $new_start, $new_end ) {
		$occurrence_date = wp_date( 'j. F Y', $timestamp, wp_timezone() );
		$title_with_date = $parent->post_title . ' (' . $occurrence_date . ')';

		$postarr = array(
			'post_type'    => 'event',
			'post_status'  => 'publish',
			'post_title'   => $title_with_date,
			'post_content' => $parent->post_content,
			'post_excerpt' => $parent->post_excerpt,
		);

		if ( $child_id > 0 && get_post_type( $child_id ) === 'event' ) {
			$postarr['ID'] = $child_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$postarr['meta_input'] = array(
				'is_occurrence'      => true,
				'occurrence_of'      => $parent_id,
				'_recurrence_parent' => $parent_id,
			);
			$result = wp_insert_post( $postarr, true );
		}

		if ( ! $result || is_wp_error( $result ) ) {
			return 0;
		}

		$child_id = (int) $result;
		self::copy_occurrence_data( $parent_id, $child_id, $new_start, $new_end );
		return $child_id;
	}

	/**
	 * Copy parent fields onto an occurrence without touching registrations.
	 *
	 * @param int    $parent_id Parent event ID.
	 * @param int    $child_id  Occurrence ID.
	 * @param string $new_start ISO start.
	 * @param string $new_end   ISO end.
	 */
	protected static function copy_occurrence_data( $parent_id, $child_id, $new_start, $new_end ) {
		update_post_meta( $child_id, 'event_start', $new_start );
		if ( $new_end ) {
			update_post_meta( $child_id, 'event_end', $new_end );
		} else {
			delete_post_meta( $child_id, 'event_end' );
		}

		update_post_meta( $child_id, 'is_occurrence', true );
		update_post_meta( $child_id, 'occurrence_of', $parent_id );
		update_post_meta( $child_id, '_recurrence_parent', $parent_id );

		$categories = wp_get_object_terms( $parent_id, 'event_category', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $categories ) ) {
			wp_set_object_terms( $child_id, $categories, 'event_category' );
		}

		$tags = wp_get_object_terms( $parent_id, 'event_tag', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $tags ) ) {
			wp_set_object_terms( $child_id, $tags, 'event_tag' );
		}

		$thumbnail_id = get_post_thumbnail_id( $parent_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $child_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $child_id );
		}

		foreach ( self::COPIED_META_KEYS as $meta_key ) {
			$value          = get_post_meta( $parent_id, $meta_key, true );
			$is_empty_array = is_array( $value ) && empty( $value );
			if ( $value === false || $value === '' || $is_empty_array ) {
				delete_post_meta( $child_id, $meta_key );
				continue;
			}
			update_post_meta( $child_id, $meta_key, $value );
		}
	}

	/**
	 * Advance a timestamp by the recurrence rule.
	 *
	 * @param int    $timestamp Base timestamp.
	 * @param string $type      Recurrence type.
	 * @param int    $interval  Interval.
	 * @return int
	 */
	protected static function advance( $timestamp, $type, $interval ) {
		switch ( $type ) {
			case 'daily':
				return strtotime( "+$interval day", $timestamp );
			case 'weekly':
				return strtotime( "+$interval week", $timestamp );
			case 'monthly':
				return strtotime( "+$interval month", $timestamp );
			case 'yearly':
				return strtotime( "+$interval year", $timestamp );
			case 'custom':
				return strtotime( "+$interval day", $timestamp );
			default:
				return $timestamp;
		}
	}
}
