<?php
/**
 * Template for displaying Events archive page
 * 
 * This file can be overridden by copying it to yourtheme/wp-events/archive-event.php
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="wp-events-archive-page">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-title">
                <?php 
                if ( is_post_type_archive( 'event' ) ) {
                    echo esc_html__( 'Alle Events', 'wp-events' );
                } else {
                    post_type_archive_title();
                }
                ?>
            </h1>
            
            <?php 
            $description = get_the_archive_description();
            if ( $description ) : ?>
                <div class="archive-description">
                    <?php echo wp_kses_post( wpautop( $description ) ); ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- Filter options -->
        <div class="events-filters">
            <div class="filter-group">
                <label for="event-category-filter"><?php esc_html_e( 'Kategori:', 'wp-events' ); ?></label>
                <select id="event-category-filter" name="event_category">
                    <option value=""><?php esc_html_e( 'Alle kategorier', 'wp-events' ); ?></option>
                    <?php
                    $categories = get_terms( array(
                        'taxonomy' => 'event_category',
                        'hide_empty' => true,
                    ) );
                    if ( $categories && ! is_wp_error( $categories ) ) :
                        foreach ( $categories as $category ) :
                            $selected = isset( $_GET['event_category'] ) && $_GET['event_category'] === $category->slug ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr( $category->slug ); ?>" <?php echo $selected; ?>>
                                <?php echo esc_html( $category->name ); ?> (<?php echo $category->count; ?>)
                            </option>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="event-time-filter"><?php esc_html_e( 'Tid:', 'wp-events' ); ?></label>
                <select id="event-time-filter" name="time_filter">
                    <option value="upcoming" <?php selected( isset( $_GET['time_filter'] ) ? $_GET['time_filter'] : 'upcoming', 'upcoming' ); ?>>
                        <?php esc_html_e( 'Kommende events', 'wp-events' ); ?>
                    </option>
                    <option value="past" <?php selected( isset( $_GET['time_filter'] ) ? $_GET['time_filter'] : '', 'past' ); ?>>
                        <?php esc_html_e( 'Tidligere events', 'wp-events' ); ?>
                    </option>
                    <option value="all" <?php selected( isset( $_GET['time_filter'] ) ? $_GET['time_filter'] : '', 'all' ); ?>>
                        <?php esc_html_e( 'Alle events', 'wp-events' ); ?>
                    </option>
                </select>
            </div>

            <button type="button" id="apply-filters" class="btn btn-primary">
                <?php esc_html_e( 'Anvend filtre', 'wp-events' ); ?>
            </button>
        </div>

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
                        
                        // Get event categories
                        $categories = get_the_terms( $event_id, 'event_category' );
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
                                
                                <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                                    <div class="event-categories">
                                        <?php foreach ( $categories as $category ) : ?>
                                            <span class="event-category">
                                                <a href="<?php echo get_term_link( $category ); ?>">
                                                    <?php echo esc_html( $category->name ); ?>
                                                </a>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
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
                                </div>
                                
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
                    <p><?php esc_html_e( 'Der er ingen events at vise lige nu.', 'wp-events' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Simple filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const applyButton = document.getElementById('apply-filters');
    const categorySelect = document.getElementById('event-category-filter');
    const timeSelect = document.getElementById('event-time-filter');
    
    if (applyButton) {
        applyButton.addEventListener('click', function() {
            const category = categorySelect.value;
            const timeFilter = timeSelect.value;
            
            let url = new URL(window.location);
            
            if (category) {
                url.searchParams.set('event_category', category);
            } else {
                url.searchParams.delete('event_category');
            }
            
            if (timeFilter && timeFilter !== 'upcoming') {
                url.searchParams.set('time_filter', timeFilter);
            } else {
                url.searchParams.delete('time_filter');
            }
            
            window.location = url;
        });
    }
});
</script>

<?php get_footer(); ?>