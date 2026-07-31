<?php
/**
 * Automatic cleanup of finished events.
 *
 * @package WPEvents
 */

namespace WPEvents;

use WPEvents\Admin\Settings;

/**
 * Daily cron that trashes ended events after a configured delay.
 */
class Cleanup {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'wpevents_auto_trash_events';

	/**
	 * Max posts to trash per cron run.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 50;

	/**
	 * Wire cron hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
	}

	/**
	 * Schedule the daily event if missing.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Schedule on plugin activation.
	 *
	 * @return void
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Clear scheduled event on deactivation.
	 *
	 * @return void
	 */
	public static function clear_schedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Trash published events past the retention window.
	 *
	 * @return void
	 */
	public static function run() {
		$settings = Settings::get();

		if ( empty( $settings['auto_trash_enabled'] ) ) {
			return;
		}

		$days = absint( $settings['auto_trash_days'] );
		if ( $days < 1 ) {
			$days = 30;
		}

		// Meta datetimes are stored in site-local time.
		$cutoff_ts    = current_time( 'timestamp' ) - ( $days * DAY_IN_SECONDS );
		$cutoff_local = wp_date( 'Y-m-d H:i:s', $cutoff_ts );

		// Broad candidate query; effective end date is verified in PHP.
		$query = new \WP_Query(
			array(
				'post_type'              => 'event',
				'post_status'            => 'publish',
				'posts_per_page'         => self::BATCH_SIZE,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => 'event_end',
						'value'   => $cutoff_local,
						'compare' => '<=',
						'type'    => 'CHAR',
					),
					array(
						'key'     => 'event_start',
						'value'   => $cutoff_local,
						'compare' => '<=',
						'type'    => 'CHAR',
					),
				),
			)
		);

		if ( empty( $query->posts ) ) {
			return;
		}

		$trashed = 0;

		foreach ( $query->posts as $post_id ) {
			if ( $trashed >= self::BATCH_SIZE ) {
				break;
			}

			$end   = get_post_meta( $post_id, 'event_end', true );
			$start = get_post_meta( $post_id, 'event_start', true );
			$ref   = ! empty( $end ) ? $end : $start;

			if ( empty( $ref ) ) {
				continue;
			}

			$ref_ts = strtotime( $ref );
			if ( ! $ref_ts || $ref_ts > $cutoff_ts ) {
				continue;
			}

			wp_trash_post( (int) $post_id );
			++$trashed;
		}
	}
}
