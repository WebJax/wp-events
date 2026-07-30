<?php
/**
 * WP-CLI commands for Tribe import.
 *
 * @package WPEvents
 */

namespace WPEvents\Import;

/**
 * CLI wrappers for Tribe import.
 */
class TribeCLI {

	/**
	 * Register WP-CLI commands when WP_CLI is available.
	 */
	public static function register() {
		if ( ! defined( 'WP_CLI' ) || ! class_exists( 'WP_CLI' ) ) {
			return;
		}
		/** @disregard P1009 Undefined type */
		\WP_CLI::add_command( 'wpevents import-tribe', array( __CLASS__, 'import_command' ) );
		/** @disregard P1009 Undefined type */
		\WP_CLI::add_command( 'wpevents reset-import', array( __CLASS__, 'reset_command' ) );
	}

	/**
	 * Import Tribe events.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public static function import_command( $args, $assoc_args ) {
		$batch = isset( $assoc_args['batch'] ) ? absint( $assoc_args['batch'] ) : 0;
		if ( ! post_type_exists( 'tribe_events' ) ) {
			/** @disregard P1009 Undefined type */
			\WP_CLI::error( 'CPT tribe_events not found. Is The Events Calendar active?' );
		}
		$res = Tribe::run_import( $batch );
		/** @disregard P1009 Undefined type */
		\WP_CLI::success( sprintf( 'Imported: %d, Skipped: %d', $res['imported'], $res['skipped'] ) );
	}

	/**
	 * Reset import markers.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public static function reset_command( $args, $assoc_args ) {
		if ( ! post_type_exists( 'tribe_events' ) ) {
			/** @disregard P1009 Undefined type */
			\WP_CLI::error( 'CPT tribe_events not found. Is The Events Calendar active?' );
		}

		$confirm = isset( $assoc_args['yes'] ) ? true : false;
		if ( ! $confirm ) {
			/** @disregard P1009 Undefined type */
			\WP_CLI::confirm( 'This will reset all import statistics. Previously imported events will NOT be deleted, but can be re-imported. Continue?' );
		}

		$result = Tribe::reset_import_stats();
		/** @disregard P1009 Undefined type */
		\WP_CLI::success( sprintf( 'Reset %d import markers. Events can now be re-imported.', $result ) );
	}
}
