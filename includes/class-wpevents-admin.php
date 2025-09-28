<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_Admin {
    public static function register_columns() {
        add_filter( 'manage_event_posts_columns', [ __CLASS__, 'columns' ] );
        add_action( 'manage_event_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
        add_filter( 'manage_edit-event_sortable_columns', [ __CLASS__, 'sortable_columns' ] );
        add_action( 'pre_get_posts', [ __CLASS__, 'sort_by_meta' ] );
    }

    public static function columns( $cols ) {
        $new = [];
        foreach ( $cols as $key => $label ) {
            $new[$key] = $label;
            if ( $key === 'title' ) {
                $new['event_start'] = __( 'Date', 'wp-events' );
                $new['event_venue'] = __( 'Venue', 'wp-events' );
                $new['event_organizer'] = __( 'Organizer', 'wp-events' );
            }
        }
        return $new;
    }

    public static function column_content( $column, $post_id ) {
        if ( $column === 'event_start' ) {
            $start = get_post_meta( $post_id, 'event_start', true );
            if ( $start ) echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $start ) ) );
        }
        if ( $column === 'event_venue' ) {
            $venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
            if ( $venue_id ) echo '<a href="' . esc_url( get_edit_post_link( $venue_id ) ) . '">' . esc_html( get_the_title( $venue_id ) ) . '</a>';
        }
        if ( $column === 'event_organizer' ) {
            $org_ids = (array) get_post_meta( $post_id, 'event_organizer', true );
            $names = array_filter( array_map( 'get_the_title', array_map( 'intval', $org_ids ) ) );
            if ( $names ) echo esc_html( implode( ', ', $names ) );
        }
    }

    public static function sortable_columns( $cols ) {
        $cols['event_start'] = 'event_start';
        return $cols;
    }

    public static function sort_by_meta( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( $query->get( 'post_type' ) !== 'event' ) return;
        if ( $query->get( 'orderby' ) === 'event_start' ) {
            $query->set( 'meta_key', 'event_start' );
            $query->set( 'orderby', 'meta_value' );
        }
    }
}
