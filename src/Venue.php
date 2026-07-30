<?php
/**
 * Venue helpers.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Venue utility methods.
 */
class Venue {

	/**
	 * Build Google Maps directions URL for a venue.
	 *
	 * @param int $venue_id Venue post ID.
	 * @return string|false
	 */
	public static function get_directions_url( $venue_id ) {
		$custom_url = get_post_meta( $venue_id, 'venue_custom_maps_url', true );
		if ( ! empty( $custom_url ) ) {
			return $custom_url;
		}

		$address     = get_post_meta( $venue_id, 'venue_address', true );
		$city        = get_post_meta( $venue_id, 'venue_city', true );
		$postal_code = get_post_meta( $venue_id, 'venue_postal_code', true );
		$country     = get_post_meta( $venue_id, 'venue_country', true );

		$address_parts = array_filter(
			array(
				$address,
				$postal_code,
				$city,
				$country,
			)
		);

		if ( empty( $address_parts ) ) {
			return false;
		}

		$full_address    = implode( ', ', $address_parts );
		$encoded_address = urlencode( $full_address );

		return 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded_address;
	}
}
