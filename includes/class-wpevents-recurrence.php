<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_Recurrence {
    public static function maybe_generate_recurrences( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) return;
        if ( $post->post_type !== 'event' ) return;
        if ( get_post_meta( $post_id, 'is_occurrence', true ) ) return; // skip occurrences

        $type = get_post_meta( $post_id, 'recurrence_type', true );
        if ( ! $type ) return;

        $interval = max( 1, (int) get_post_meta( $post_id, 'recurrence_interval', true ) );
        $end_date = get_post_meta( $post_id, 'recurrence_end', true );
        $start = get_post_meta( $post_id, 'event_start', true );
        $end = get_post_meta( $post_id, 'event_end', true );
        if ( ! $start || ! $end_date ) return;

        $start_ts = strtotime( $start );
        $end_ts = $end ? strtotime( $end ) : null;
        $until_ts = strtotime( $end_date . ' 23:59:59' );
        if ( ! $start_ts || ! $until_ts ) return;

        // Avoid duplicates: delete existing occurrences
        $existing = get_children( [
            'post_type' => 'event',
            'post_parent' => $post_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ] );
        foreach ( $existing as $cid ) {
            wp_delete_post( $cid, true );
        }

        $cursor = $start_ts;
        $count = 0; $max = 200; // safety
        while ( $cursor <= $until_ts && $count < $max ) {
            if ( $cursor !== $start_ts ) {
                $new_start = wp_date( DATE_ATOM, $cursor, wp_timezone() );
                $new_end = $end_ts ? wp_date( DATE_ATOM, $cursor + ( $end_ts - $start_ts ), wp_timezone() ) : '';

                $child_id = wp_insert_post( [
                    'post_type' => 'event',
                    'post_status' => 'publish',
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'post_parent' => $post_id,
                ] );
                if ( $child_id && ! is_wp_error( $child_id ) ) {
                    // Copy time information
                    update_post_meta( $child_id, 'event_start', $new_start );
                    if ( $new_end ) update_post_meta( $child_id, 'event_end', $new_end );
                    update_post_meta( $child_id, 'is_occurrence', true );
                    update_post_meta( $child_id, 'occurrence_of', $post_id );
                    
                    // Copy taxonomies (categories and tags)
                    $categories = wp_get_object_terms( $post_id, 'event_category', array( 'fields' => 'ids' ) );
                    if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                        wp_set_object_terms( $child_id, $categories, 'event_category' );
                    }
                    
                    $tags = wp_get_object_terms( $post_id, 'event_tag', array( 'fields' => 'ids' ) );
                    if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
                        wp_set_object_terms( $child_id, $tags, 'event_tag' );
                    }
                    
                    // Copy featured image
                    $thumbnail_id = get_post_thumbnail_id( $post_id );
                    if ( $thumbnail_id ) {
                        set_post_thumbnail( $child_id, $thumbnail_id );
                    }
                    
                    // Copy venue and organizer relations
                    update_post_meta( $child_id, 'event_venue', (int) get_post_meta( $post_id, 'event_venue', true ) );
                    update_post_meta( $child_id, 'event_organizer', (array) get_post_meta( $post_id, 'event_organizer', true ) );
                    
                    // Copy price information
                    $price = get_post_meta( $post_id, 'event_price', true );
                    if ( ! empty( $price ) ) {
                        update_post_meta( $child_id, 'event_price', $price );
                    }
                    $currency = get_post_meta( $post_id, 'event_currency', true );
                    if ( ! empty( $currency ) ) {
                        update_post_meta( $child_id, 'event_currency', $currency );
                    }
                }
            }

            $cursor = self::advance( $cursor, $type, $interval );
            $count++;
        }
    }

    protected static function advance( $timestamp, $type, $interval ) {
        switch ( $type ) {
            case 'daily':   return strtotime( "+$interval day", $timestamp );
            case 'weekly':  return strtotime( "+$interval week", $timestamp );
            case 'monthly': return strtotime( "+$interval month", $timestamp );
            case 'yearly':  return strtotime( "+$interval year", $timestamp );
            case 'custom':  return strtotime( "+$interval day", $timestamp ); // simple default
            default: return $timestamp;
        }
    }
}
