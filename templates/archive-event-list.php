<?php
/**
 * Template for displaying Events archive page - List View
 * 
 * This file can be overridden by copying it to yourtheme/wp-events/archive-event-list.php
 * Shows events in a horizontal list layout
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

<div class="wp-events-archive-page wp-events-list-view">
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

        <?php 
        // Include view switcher
        get_template_part( 'wp-events/parts/view-switcher' );
        if ( ! locate_template( 'wp-events/parts/view-switcher.php' ) ) {
            include WPEVENTS_PLUGIN_DIR . 'templates/parts/view-switcher.php';
        }
        ?>

        <?php 
        // Include event filters
        get_template_part( 'wp-events/parts/event-filters' );
        if ( ! locate_template( 'wp-events/parts/event-filters.php' ) ) {
            include WPEVENTS_PLUGIN_DIR . 'templates/parts/event-filters.php';
        }
        ?>

        <div class="wp-events-archive">
            <?php if ( have_posts() ) : ?>
                <div class="events-list-container">
                    <?php while ( have_posts() ) : the_post(); 
                        // Load list card template
                        get_template_part( 'wp-events/parts/event-card-list' );
                        if ( ! locate_template( 'wp-events/parts/event-card-list.php' ) ) {
                            include WPEVENTS_PLUGIN_DIR . 'templates/parts/event-card-list.php';
                        }
                    endwhile; ?>
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

<?php get_footer(); ?>
