<?php
/**
 * WooCommerce event ticket product type.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Simple-product behaviour with a dedicated "Event Ticket" type.
 *
 * Selecting this type in WooCommerce must remain purchasable; it is a
 * labelled simple product, not a stub.
 */
class EventTicketProduct extends \WC_Product_Simple {

	/**
	 * Product type slug.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'event_ticket';
	}
}
