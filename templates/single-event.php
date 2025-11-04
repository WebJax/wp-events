<?php
/**
 * Template for displaying single Event posts
 * 
 * This file can be overridden by copying it to yourtheme/wp-events/single-event.php
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="wp-events-single-page">
    <div class="container">
        <?php while ( have_posts() ) : the_post(); 
            $event_id = get_the_ID();
            $start_date = get_post_meta( $event_id, 'event_start', true );
            $end_date = get_post_meta( $event_id, 'event_end', true );
            $venue_id = get_post_meta( $event_id, 'event_venue', true );
            $organizer_ids = get_post_meta( $event_id, 'event_organizer', true );
            
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
            
            // Get event categories and tags
            $categories = get_the_terms( $event_id, 'event_category' );
            $tags = get_the_terms( $event_id, 'event_tag' );
        ?>
            <article class="single-event">
                <header class="event-header">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="event-featured-image">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="event-title"><?php the_title(); ?></h1>
                    
                    <div class="event-meta">
                        <?php if ( $formatted_start ) : ?>
                            <div class="event-date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <time datetime="<?php echo esc_attr( $start_date ); ?>">
                                    <?php echo esc_html( $formatted_start ); ?>
                                    <?php if ( $formatted_end && $formatted_end !== $formatted_start ) : ?>
                                        - <?php echo esc_html( $formatted_end ); ?>
                                    <?php endif; ?>
                                </time>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $venue_id ) : 
                            $venue = get_post( $venue_id );
                            if ( $venue ) : ?>
                                <div class="event-venue">
                                    <span class="dashicons dashicons-location"></span>
                                    <a href="<?php echo get_permalink( $venue_id ); ?>">
                                        <?php echo esc_html( $venue->post_title ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ( is_array( $organizer_ids ) && ! empty( $organizer_ids ) ) : ?>
                            <div class="event-organizers">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php 
                                $organizer_links = array();
                                foreach ( $organizer_ids as $organizer_id ) {
                                    $organizer = get_post( $organizer_id );
                                    if ( $organizer ) {
                                        $organizer_links[] = '<a href="' . get_permalink( $organizer_id ) . '">' . esc_html( $organizer->post_title ) . '</a>';
                                    }
                                }
                                echo implode( ', ', $organizer_links );
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ( $categories && ! is_wp_error( $categories ) ) || ( $tags && ! is_wp_error( $tags ) ) ) : ?>
                            <div class="event-taxonomy">
                                <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                                    <div class="event-categories">
                                        <strong><?php esc_html_e( 'Kategorier:', 'wp-events' ); ?></strong>
                                        <?php foreach ( $categories as $category ) : ?>
                                            <span class="event-category">
                                                <a href="<?php echo get_term_link( $category ); ?>">
                                                    <?php echo esc_html( $category->name ); ?>
                                                </a>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
                                    <div class="event-tags">
                                        <strong><?php esc_html_e( 'Tags:', 'wp-events' ); ?></strong>
                                        <?php foreach ( $tags as $tag ) : ?>
                                            <span class="event-tag">
                                                <a href="<?php echo get_term_link( $tag ); ?>">
                                                    #<?php echo esc_html( $tag->name ); ?>
                                                </a>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="event-content">
                    <?php the_content(); ?>
                </div>

                <?php if ( $venue_id ) : 
                    $venue = get_post( $venue_id );
                    if ( $venue ) :
                        $address = get_post_meta( $venue_id, 'venue_address', true );
                        $city = get_post_meta( $venue_id, 'venue_city', true );
                        $postal_code = get_post_meta( $venue_id, 'venue_postal_code', true );
                        $country = get_post_meta( $venue_id, 'venue_country', true );
                        $phone = get_post_meta( $venue_id, 'venue_phone', true );
                        $email = get_post_meta( $venue_id, 'venue_email', true );
                        ?>
                        <div class="event-venue-details">
                            <h3><?php esc_html_e( 'Venue Information', 'wp-events' ); ?></h3>
                            <div class="venue-info">
                                <h4><?php echo esc_html( $venue->post_title ); ?></h4>
                                
                                <?php if ( $address || $city || $postal_code || $country ) : ?>
                                    <div class="venue-address">
                                        <?php if ( $address ) : ?>
                                            <div><?php echo esc_html( $address ); ?></div>
                                        <?php endif; ?>
                                        <?php if ( $postal_code || $city ) : ?>
                                            <div><?php echo esc_html( trim( $postal_code . ' ' . $city ) ); ?></div>
                                        <?php endif; ?>
                                        <?php if ( $country ) : ?>
                                            <div><?php echo esc_html( $country ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $phone || $email ) : ?>
                                    <div class="venue-contact">
                                        <?php if ( $phone ) : ?>
                                            <div><strong><?php esc_html_e( 'Telefon:', 'wp-events' ); ?></strong> <?php echo esc_html( $phone ); ?></div>
                                        <?php endif; ?>
                                        <?php if ( $email ) : ?>
                                            <div><strong><?php esc_html_e( 'Email:', 'wp-events' ); ?></strong> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <footer class="event-footer">
                    <div class="event-navigation">
                        <?php
                        $prev_event = get_previous_post();
                        $next_event = get_next_post();
                        ?>
                        
                        <?php if ( $prev_event ) : ?>
                            <div class="prev-event">
                                <a href="<?php echo get_permalink( $prev_event->ID ); ?>" rel="prev">
                                    <span class="nav-subtitle"><?php esc_html_e( 'Forrige Event:', 'wp-events' ); ?></span>
                                    <span class="nav-title"><?php echo get_the_title( $prev_event->ID ); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $next_event ) : ?>
                            <div class="next-event">
                                <a href="<?php echo get_permalink( $next_event->ID ); ?>" rel="next">
                                    <span class="nav-subtitle"><?php esc_html_e( 'Næste Event:', 'wp-events' ); ?></span>
                                    <span class="nav-title"><?php echo get_the_title( $next_event->ID ); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="back-to-events">
                        <a href="<?php echo get_post_type_archive_link( 'event' ); ?>" class="btn btn-outline">
                            <?php esc_html_e( '← Tilbage til alle events', 'wp-events' ); ?>
                        </a>
                    </div>
                </footer>
            </article>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>