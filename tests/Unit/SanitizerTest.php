<?php
/**
 * Sanitizer unit tests.
 *
 * @package WPEvents
 */

namespace WPEvents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPEvents\AdditionalFeatures;
use WPEvents\Sanitizer;

/**
 * @covers \WPEvents\Sanitizer
 */
class SanitizerTest extends TestCase {

	public function test_sanitize_ids_array_filters_integers() {
		$this->assertSame( array( 1, 2, 3 ), Sanitizer::sanitize_ids_array( array( '1', 2, '3', 0 ) ) );
	}

	public function test_sanitize_ids_array_accepts_json() {
		$this->assertSame( array( 4, 5 ), Sanitizer::sanitize_ids_array( '[4,5]' ) );
	}

	public function test_sanitize_currency() {
		$this->assertSame( 'DKK', Sanitizer::sanitize_currency( 'dkk-extra' ) );
	}

	public function test_sanitize_recurrence_type() {
		$this->assertSame( 'weekly', Sanitizer::sanitize_recurrence_type( 'weekly' ) );
		$this->assertSame( '', Sanitizer::sanitize_recurrence_type( 'hourly' ) );
	}

	public function test_sanitize_event_status() {
		$this->assertSame( 'cancelled', AdditionalFeatures::sanitize_event_status( 'cancelled' ) );
		$this->assertSame( 'scheduled', AdditionalFeatures::sanitize_event_status( 'exploded' ) );
	}
}
