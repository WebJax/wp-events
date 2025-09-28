<?php
/**
 * Template for displaying Event Tag taxonomy pages
 * 
 * This file can be overridden by copying it to yourtheme/wp-events/taxonomy-event_tag.php
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="wp-events-taxonomy-page">
    <div class="container">
        <header class="taxonomy-header">
            <h1 class="taxonomy-title">
                <?php 
                $term = get_queried_object();
                printf( esc_html__( 'Events tagget med: %s', 'wp-events' ), '<span>' . single_term_title( '', false ) . '</span>' );
                ?>
            </h1>
            
            <?php if ( $term && ! empty( $term->description ) ) : ?>
                <div class="taxonomy-description">
                    <?php echo wp_kses_post( wpautop( $term->description ) ); ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="wp-events-archive">
            <?php if ( have_posts() ) : ?>
                <div class="events-grid">
                    <?php while ( have_posts() ) : the_post(); 
                        $event_id = get_the_ID();
                        $start_date = get_post_meta( $event_id, 'event_start', true );
                        $end_date = get_post_meta( $event_id, 'event_end', true );
                        $venue_id = get_post_meta( $event_id, 'event_venue', true );
                        $venue_name = $venue_id ? get_the_title( $venue_id ) : '';
                        $venue_address = $venue_id ? get_post_meta( $venue_id, 'venue_address', true ) : '';
                        $venue_city = $venue_id ? get_post_meta( $venue_id, 'venue_city', true ) : '';
                        
                        // Format dates
                        $formatted_start = '';
                        $formatted_end = '';
                        if ( $start_date ) {
                            $start_timestamp = strtotime( $start_date );
                            if ( $start_timestamp ) {
                                $formatted_start = wp_date( 'j. F Y \k\l. H:i', $start_timestamp );
                                
                                if ( $end_date ) {
                                    $end_timestamp = strtotime( $end_date );
                                    if ( $end_timestamp ) {
                                        // Check if start and end are on the same date
                                        $start_date_only = wp_date( 'Y-m-d', $start_timestamp );
                                        $end_date_only = wp_date( 'Y-m-d', $end_timestamp );
                                        
                                        if ( $start_date_only === $end_date_only ) {
                                            // Same day - only show end time
                                            $formatted_end = wp_date( 'H:i', $end_timestamp );
                                        } else {
                                            // Different days - show full end date and time
                                            $formatted_end = wp_date( 'j. F Y \k\l. H:i', $end_timestamp );
                                        }
                                    }
                                }
                            }
                        }
                    ?>
                        <article class="event-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="event-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail( 'medium' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-content">
                                <h2 class="event-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>
                                
                                <?php if ( $formatted_start ) : ?>
                                    <div class="event-date">
                                        <span class="icon-calendar"></span>
                                        <time datetime="<?php echo esc_attr( $start_date ); ?>">
                                            <?php echo esc_html( $formatted_start ); ?>
                                            <?php if ( $formatted_end && $formatted_end !== $formatted_start ) : ?>
                                                - <?php echo esc_html( $formatted_end ); ?>
                                            <?php endif; ?>
                                        </time>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $venue_name ) : ?>
                                    <div class="event-venue">
                                        <span class="icon-location"></span>
                                        <div class="venue-info">
                                            <strong><?php echo esc_html( $venue_name ); ?></strong>
                                            <?php if ( $venue_address || $venue_city ) : ?>
                                                <br>
                                                <span class="venue-address">
                                                    <?php 
                                                    $address_parts = array_filter( array( $venue_address, $venue_city ) );
                                                    echo esc_html( implode( ', ', $address_parts ) );
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( has_excerpt() ) : ?>
                                    <div class="event-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="<?php the_permalink(); ?>" class="event-read-more">
                                    <?php esc_html_e( 'Læs mere', 'wp-events' ); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php
                // Pagination
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => esc_html__( 'Forrige', 'wp-events' ),
                    'next_text' => esc_html__( 'Næste', 'wp-events' ),
                    'class'     => 'wp-events-pagination',
                ) );
                ?>

            <?php else : ?>
                <div class="no-events-found">
                    <h2><?php esc_html_e( 'Ingen events fundet', 'wp-events' ); ?></h2>
                    <p><?php esc_html_e( 'Der er ingen events med dette tag endnu.', 'wp-events' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>