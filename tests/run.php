<?php
/**
 * Minimal test runner used when PHPUnit is not installed.
 *
 * @package WPEvents
 */

require_once __DIR__ . '/bootstrap.php';

$failures = 0;
$passed   = 0;

/**
 * @param bool   $condition Assertion.
 * @param string $message   Failure message.
 * @return void
 */
function wpevents_assert( $condition, $message ) {
	global $failures, $passed;
	if ( $condition ) {
		++$passed;
		echo "  ok  {$message}\n";
		return;
	}

	++$failures;
	echo "  FAIL {$message}\n";
}

echo "Sanitizer\n";
wpevents_assert( array( 1, 2, 3 ) === \WPEvents\Sanitizer::sanitize_ids_array( array( '1', 2, '3', 0 ) ), 'sanitize_ids_array filters ints' );
wpevents_assert( array( 4, 5 ) === \WPEvents\Sanitizer::sanitize_ids_array( '[4,5]' ), 'sanitize_ids_array accepts JSON' );
wpevents_assert( array() === \WPEvents\Sanitizer::sanitize_ids_array( 'not-an-array' ), 'sanitize_ids_array rejects junk' );
wpevents_assert( 'DKK' === \WPEvents\Sanitizer::sanitize_currency( 'dkk-extra' ), 'sanitize_currency keeps 3 letters' );
wpevents_assert( 'weekly' === \WPEvents\Sanitizer::sanitize_recurrence_type( 'weekly' ), 'sanitize_recurrence_type allowlist' );
wpevents_assert( '' === \WPEvents\Sanitizer::sanitize_recurrence_type( 'hourly' ), 'sanitize_recurrence_type rejects unknown' );
wpevents_assert( '1' === \WPEvents\Sanitizer::sanitize_flag( true ), 'sanitize_flag true' );
wpevents_assert( '0' === \WPEvents\Sanitizer::sanitize_flag( 'no' ), 'sanitize_flag falsey' );

echo "Event status\n";
wpevents_assert( 'cancelled' === \WPEvents\AdditionalFeatures::sanitize_event_status( 'cancelled' ), 'sanitize_event_status allowlist' );
wpevents_assert( 'scheduled' === \WPEvents\AdditionalFeatures::sanitize_event_status( 'exploded' ), 'sanitize_event_status fallback' );

echo "iCal STATUS\n";
wpevents_assert( 'CANCELLED' === \WPEvents\ICal::map_event_status_to_ical( 'cancelled' ), 'cancelled maps to CANCELLED' );
wpevents_assert( 'TENTATIVE' === \WPEvents\ICal::map_event_status_to_ical( 'postponed' ), 'postponed maps to TENTATIVE' );
wpevents_assert( 'CONFIRMED' === \WPEvents\ICal::map_event_status_to_ical( 'scheduled' ), 'scheduled maps to CONFIRMED' );
wpevents_assert( 'CONFIRMED' === \WPEvents\ICal::map_event_status_to_ical( 'sold_out' ), 'sold_out maps to CONFIRMED' );
wpevents_assert( 'CONFIRMED' === \WPEvents\ICal::map_event_status_to_ical( '' ), 'empty maps to CONFIRMED' );

echo "\n{$passed} passed, {$failures} failed\n";
exit( $failures > 0 ? 1 : 0 );
