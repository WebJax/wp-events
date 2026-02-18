<?php
/**
 * Event Card - List Style
 * 
 * Displays an event card in horizontal list layout format
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
$date_badge = '';
if ( $start_date ) {
    $start_timestamp = strtotime( $start_date );
    if ( $start_timestamp ) {
        $formatted_start = wp_date( 'j. F Y \k\l. H:i', $start_timestamp );
        $date_badge = array(
            'day' => wp_date( 'j', $start_timestamp ),
            'month' => wp_date( 'M', $start_timestamp ),
            'year' => wp_date( 'Y', $start_timestamp )
        );
        
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

// Get event categories
$categories = get_the_terms( $event_id, 'event_category' );
?>

<article class="event-card event-card-list">
    <?php if ( $date_badge ) : ?>
        <div class="event-date-badge">
            <span class="day"><?php echo esc_html( $date_badge['day'] ); ?></span>
            <span class="month"><?php echo esc_html( $date_badge['month'] ); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="event-image">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'medium' ); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="event-content">
        <div class="event-header-section">
            <h2 class="event-title">
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </h2>
            
            <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                <div class="event-categories">
                    <?php foreach ( $categories as $category ) : ?>
                        <span class="event-category">
                            <a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
                                <?php echo esc_html( $category->name ); ?>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="event-meta">
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
                    <span class="venue-info">
                        <strong><?php echo esc_html( $venue_name ); ?></strong>
                        <?php if ( $venue_city ) : ?>
                            <span class="venue-city">, <?php echo esc_html( $venue_city ); ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ( has_excerpt() ) : ?>
            <div class="event-excerpt">
                <?php the_excerpt(); ?>
            </div>
        <?php endif; ?>
        
        <a href="<?php the_permalink(); ?>" class="event-read-more">
            <?php esc_html_e( 'Læs mere', 'wp-events' ); ?>
            <span class="icon-right-dir"></span>
        </a>
    </div>
</article>
