<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_Shortcodes {
    public static function register() {
        add_shortcode( 'events_list', [ __CLASS__, 'events_list' ] );
        add_shortcode( 'event', [ __CLASS__, 'event_single' ] );
    }

    public static function events_list( $atts ) {
        $atts = shortcode_atts( [
            'limit' => 5,
        ], $atts, 'events_list' );

        $q = new WP_Query( [
            'post_type' => 'event',
            'posts_per_page' => (int) $atts['limit'],
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => 'event_start',
            'meta_type' => 'DATETIME',
            'meta_query' => [
                [
                    'key' => 'event_start',
                    'value' => wp_date( DATE_ATOM, time(), wp_timezone() ),
                    'compare' => '>=',
                    'type' => 'CHAR',
                ]
            ],
        ] );

        if ( ! $q->have_posts() ) return '<div class="wp-events-list empty">' . esc_html__( 'No upcoming events', 'wp-events' ) . '</div>';

        ob_start();
        echo '<ul class="wp-events-list">';
        while ( $q->have_posts() ) { $q->the_post();
            $start = get_post_meta( get_the_ID(), 'event_start', true );
            $venue_id = (int) get_post_meta( get_the_ID(), 'event_venue', true );
            echo '<li class="wp-event-item">';
            echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
            if ( $start ) {
                echo ' <time datetime="' . esc_attr( $start ) . '">' . esc_html( wp_date( 'j. M Y H:i', strtotime( $start ) ) ) . '</time>';
            }
            if ( $venue_id ) {
                echo ' <span class="venue">' . esc_html( get_the_title( $venue_id ) ) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function event_single( $atts ) {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'event' );
        $post_id = absint( $atts['id'] );
        if ( ! $post_id ) return '';
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'event' ) return '';

        $start = get_post_meta( $post_id, 'event_start', true );
        $end = get_post_meta( $post_id, 'event_end', true );
        $venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
        $organizers = (array) get_post_meta( $post_id, 'event_organizer', true );

        ob_start();
        echo '<div class="wp-event-single">';
        echo '<h3>' . esc_html( get_the_title( $post_id ) ) . '</h3>';
        if ( $start ) {
            echo '<div class="dates">';
            echo '<time datetime="' . esc_attr( $start ) . '">' . esc_html( wp_date( 'j. M Y H:i', strtotime( $start ) ) ) . '</time>';
            if ( $end ) echo ' - <time datetime="' . esc_attr( $end ) . '">' . esc_html( wp_date( 'j. M Y H:i', strtotime( $end ) ) ) . '</time>';
            echo '</div>';
        }
        if ( $venue_id ) {
            echo '<div class="venue"><strong>' . esc_html__( 'Venue', 'wp-events' ) . ':</strong> ' . esc_html( get_the_title( $venue_id ) ) . '</div>';
        }
        if ( ! empty( $organizers ) ) {
            $names = array_map( function( $oid ) { return get_the_title( (int) $oid ); }, $organizers );
            $names = array_filter( $names );
            if ( $names ) echo '<div class="organizers"><strong>' . esc_html__( 'Organizer(s)', 'wp-events' ) . ':</strong> ' . esc_html( implode( ', ', $names ) ) . '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}
