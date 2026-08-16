<?php
/**
 * JSON-LD schema for events.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Schema.org Event markup.
 */
class Schema {
	public static function print_json_ld() {
		if ( ! is_singular( 'event' ) ) {
			return;
		}
		global $post;
		if ( ! $post ) {
			return;
		}

		$data = self::build_event_schema( $post->ID );
		if ( ! $data ) {
			return;
		}

		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		echo '<script type="application/ld+json">' . wp_json_encode( $data, $flags ) . '</script>' . "\n";
	}

	public static function build_event_schema( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'event' !== $post->post_type ) {
			return null;
		}

		$start = get_post_meta( $post_id, 'event_start', true );
		$end   = get_post_meta( $post_id, 'event_end', true );

		$venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
		$org_ids  = (array) get_post_meta( $post_id, 'event_organizer', true );

		$image = get_the_post_thumbnail_url( $post_id, 'full' );

		$location = null;
		if ( $venue_id ) {
			$address = array_filter(
				array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => get_post_meta( $venue_id, 'venue_address', true ),
					'addressLocality' => get_post_meta( $venue_id, 'venue_city', true ),
					'postalCode'      => get_post_meta( $venue_id, 'venue_postal_code', true ),
					'addressCountry'  => get_post_meta( $venue_id, 'venue_country', true ),
				)
			);

			$location = array_filter(
				array(
					'@type'     => 'Place',
					'name'      => get_the_title( $venue_id ),
					'address'   => $address,
					'telephone' => get_post_meta( $venue_id, 'venue_phone', true ),
					'url'       => get_post_meta( $venue_id, 'venue_website', true ),
				)
			);
		}

		$organizers = array();
		foreach ( $org_ids as $oid ) {
			$oid = (int) $oid;
			if ( ! $oid ) {
				continue;
			}
			$organizer = array_filter(
				array(
					'@type'     => 'Organization',
					'name'      => get_the_title( $oid ),
					'url'       => get_post_meta( $oid, 'organizer_website', true ),
					'telephone' => get_post_meta( $oid, 'organizer_phone', true ),
				)
			);
			if ( ! empty( $organizer['name'] ) ) {
				$organizers[] = $organizer;
			}
		}

		$offers = self::build_offers( $post_id );

		$data = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'Event',
			'name'                => get_the_title( $post_id ),
			'url'                 => get_permalink( $post_id ),
			'description'         => wp_strip_all_tags( has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( $post->post_content, 40 ) ),
			'startDate'           => $start,
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'eventStatus'         => self::map_event_status( get_post_meta( $post_id, 'event_status', true ) ),
		);
		if ( $image ) {
			$data['image'] = $image;
		}
		if ( $end ) {
			$data['endDate'] = $end;
		}
		if ( $location ) {
			$data['location'] = $location;
		}
		if ( ! empty( $organizers ) ) {
			$data['organizer'] = count( $organizers ) === 1 ? $organizers[0] : $organizers;
		}
		if ( $offers ) {
			$data['offers'] = $offers;
		}

		return $data;
	}

	/**
	 * Build Offer markup from WooCommerce tickets or event price.
	 *
	 * @param int $post_id Event ID.
	 * @return array|null
	 */
	protected static function build_offers( $post_id ) {
		$url        = get_permalink( $post_id );
		$valid_from = get_the_date( DATE_ATOM, $post_id );
		$status     = get_post_meta( $post_id, 'event_status', true );
		$sold_out   = ( 'sold_out' === $status );

		$wc_offers = array();
		if ( class_exists( 'WooCommerce' ) && '1' === get_post_meta( $post_id, 'enable_tickets', true ) ) {
			$remaining   = WooCommerce::get_remaining_capacity( $post_id );
			$event_full  = ( null !== $remaining && $remaining <= 0 );
			$product_ids = WooCommerce::get_ticket_product_ids( $post_id );

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_post_meta( $post_id, 'event_currency', true );
				$price    = $product->get_price();
				if ( '' === $price || null === $price ) {
					continue;
				}

				$availability = 'https://schema.org/InStock';
				if ( $sold_out || $event_full || ! $product->is_in_stock() ) {
					$availability = 'https://schema.org/SoldOut';
				}

				$wc_offers[] = array(
					'@type'         => 'Offer',
					'name'          => $product->get_name(),
					'url'           => $url,
					'price'         => (float) $price,
					'priceCurrency' => strtoupper( (string) $currency ),
					'availability'  => $availability,
					'validFrom'     => $valid_from,
				);
			}
		}

		if ( ! empty( $wc_offers ) ) {
			return count( $wc_offers ) === 1 ? $wc_offers[0] : $wc_offers;
		}

		$price = get_post_meta( $post_id, 'event_price', true );
		$cur   = get_post_meta( $post_id, 'event_currency', true );
		if ( '' === $price || ! $cur ) {
			return null;
		}

		return array(
			'@type'         => 'Offer',
			'url'           => $url,
			'price'         => (float) $price,
			'priceCurrency' => strtoupper( $cur ),
			'availability'  => $sold_out ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
			'validFrom'     => $valid_from,
		);
	}

	/**
	 * Map event_status meta to Schema.org EventStatusType URL.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function map_event_status( $status ) {
		$map = array(
			'scheduled'   => 'https://schema.org/EventScheduled',
			'cancelled'   => 'https://schema.org/EventCancelled',
			'postponed'   => 'https://schema.org/EventPostponed',
			'rescheduled' => 'https://schema.org/EventRescheduled',
			'sold_out'    => 'https://schema.org/EventScheduled',
			'completed'   => 'https://schema.org/EventScheduled',
		);
		$status = sanitize_key( (string) $status );
		if ( ! $status || ! isset( $map[ $status ] ) ) {
			return 'https://schema.org/EventScheduled';
		}
		return $map[ $status ];
	}
}
