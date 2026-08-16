<?php
/**
 * iCalendar STATUS mapping tests.
 *
 * @package WPEvents
 */

namespace WPEvents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPEvents\ICal;

/**
 * @covers \WPEvents\ICal::map_event_status_to_ical
 */
class ICalStatusTest extends TestCase {

	public function test_cancelled_maps_to_cancelled() {
		$this->assertSame( 'CANCELLED', ICal::map_event_status_to_ical( 'cancelled' ) );
	}

	public function test_postponed_maps_to_tentative() {
		$this->assertSame( 'TENTATIVE', ICal::map_event_status_to_ical( 'postponed' ) );
	}

	public function test_other_statuses_are_confirmed() {
		$this->assertSame( 'CONFIRMED', ICal::map_event_status_to_ical( 'scheduled' ) );
		$this->assertSame( 'CONFIRMED', ICal::map_event_status_to_ical( 'sold_out' ) );
		$this->assertSame( 'CONFIRMED', ICal::map_event_status_to_ical( 'completed' ) );
		$this->assertSame( 'CONFIRMED', ICal::map_event_status_to_ical( '' ) );
	}
}
