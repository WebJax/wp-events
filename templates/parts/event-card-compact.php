<?php
/**
 * Event Card - Compact Style
 * 
 * Displays an event card in compact format with minimal information
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$event_id = get_the_ID();
$start_date = get_post_meta( $event_id, 'event_start', true );
$venue_id = get_post_meta( $event_id, 'event_venue', true );
$venue_name = $venue_id ? get_the_title( $venue_id ) : '';

// Format date
$formatted_start = '';
$date_parts = array();
if ( $start_date ) {
    $start_timestamp = strtotime( $start_date );
    if ( $start_timestamp ) {
        $formatted_start = wp_date( 'j. M Y, H:i', $start_timestamp );
        $date_parts = array(
            'day' => wp_date( 'j', $start_timestamp ),
            'month' => wp_date( 'M', $start_timestamp ),
            'time' => wp_date( 'H:i', $start_timestamp )
        );
    }
}

// Get first category
$categories = get_the_terms( $event_id, 'event_category' );
$first_category = $categories && ! is_wp_error( $categories ) ? $categories[0] : null;
?>

<article class="event-card event-card-compact">
    <a href="<?php the_permalink(); ?>" class="event-card-link">
        <?php if ( $date_parts ) : ?>
            <div class="event-date-badge">
                <span class="day"><?php echo esc_html( $date_parts['day'] ); ?></span>
                <span class="month"><?php echo esc_html( $date_parts['month'] ); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="event-content">
            <h3 class="event-title"><?php the_title(); ?></h3>
            
            <div class="event-meta">
                <?php if ( $date_parts ) : ?>
                    <span class="event-time">
                        <span class="icon-calendar"></span>
                        <?php echo esc_html( $date_parts['time'] ); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ( $venue_name ) : ?>
                    <span class="event-venue">
                        <span class="icon-location"></span>
                        <?php echo esc_html( $venue_name ); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ( $first_category ) : ?>
                <span class="event-category-badge">
                    <?php echo esc_html( $first_category->name ); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <span class="event-arrow icon-right-dir"></span>
    </a>
</article>
