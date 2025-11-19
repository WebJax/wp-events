<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_Schema {
    public static function print_json_ld() {
        if ( ! is_singular( 'event' ) ) return;
        global $post;
        if ( ! $post ) return;

        $data = self::build_event_schema( $post->ID );
        if ( ! $data ) return;

        echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

    public static function build_event_schema( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'event' ) return null;

        $start = get_post_meta( $post_id, 'event_start', true );
        $end   = get_post_meta( $post_id, 'event_end', true );
        $price = get_post_meta( $post_id, 'event_price', true );
        $cur   = get_post_meta( $post_id, 'event_currency', true );

        $venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
        $org_ids  = (array) get_post_meta( $post_id, 'event_organizer', true );

        $image = get_the_post_thumbnail_url( $post_id, 'full' );

        $location = null;
        if ( $venue_id ) {
            $address = get_post_meta( $venue_id, 'venue_address', true );
            $city = get_post_meta( $venue_id, 'venue_city', true );
            $postal = get_post_meta( $venue_id, 'venue_postal_code', true );
            $country = get_post_meta( $venue_id, 'venue_country', true );

            $location = [
                '@type' => 'Place',
                'name' => get_the_title( $venue_id ),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $address,
                    'addressLocality' => $city,
                    'postalCode' => $postal,
                    'addressCountry' => $country
                ],
                'telephone' => get_post_meta( $venue_id, 'venue_phone', true ),
                'url' => get_post_meta( $venue_id, 'venue_website', true ),
            ];
        }

        $organizers = [];
        foreach ( $org_ids as $oid ) {
            $oid = (int) $oid;
            if ( ! $oid ) continue;
            $organizers[] = [
                '@type' => 'Organization',
                'name' => get_the_title( $oid ),
                'url' => get_post_meta( $oid, 'organizer_website', true ),
                'telephone' => get_post_meta( $oid, 'organizer_phone', true ),
            ];
        }

        $offers = null;
        if ( $price !== '' && $cur ) {
            $offers = [
                '@type' => 'Offer',
                'price' => (float) $price,
                'priceCurrency' => strtoupper( $cur ),
            ];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => get_the_title( $post_id ),
            'description' => wp_strip_all_tags( has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( $post->post_content, 40 ) ),
            'image' => $image ?: '',
            'startDate' => $start,
            'eventAttendanceMode' => 'OfflineEventAttendanceMode',
            'eventStatus' => 'EventScheduled',
            'location' => $location,
        ];
        if ( $end ) $data['endDate'] = $end;
        if ( ! empty( $organizers ) ) {
            $data['organizer'] = count( $organizers ) === 1 ? $organizers[0] : $organizers;
        }
        if ( $offers ) $data['offers'] = $offers;

        return $data;
    }
}
