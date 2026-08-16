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

	/**
	 * Cap purchases by WooCommerce stock and remaining shared event capacity.
	 *
	 * @return int Max quantity, or -1 when unlimited.
	 */
	public function get_max_purchase_quantity() {
		$parent   = parent::get_max_purchase_quantity();
		$event_id = WooCommerce::get_event_id_for_product( $this->get_id() );
		if ( ! $event_id ) {
			return $parent;
		}

		$remaining = WooCommerce::get_remaining_capacity( $event_id );
		if ( null === $remaining ) {
			return $parent;
		}

		if ( $parent < 0 ) {
			return $remaining;
		}

		return min( (int) $parent, $remaining );
	}
}
