<?php
/**
 * Event Filters Template Part
 * 
 * Reusable filters for archive, category, and tag pages
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current values
$current_category = isset( $_GET['event_category'] ) ? sanitize_text_field( $_GET['event_category'] ) : '';
$current_timeframe = isset( $_GET['timeframe'] ) ? sanitize_text_field( $_GET['timeframe'] ) : 'all';
$current_sort = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'asc';

// Build base URL for form action (preserve current page context)
$base_url = '';
if ( is_tax( 'event_category' ) ) {
    $term = get_queried_object();
    $base_url = get_term_link( $term );
} elseif ( is_tax( 'event_tag' ) ) {
    $term = get_queried_object();
    $base_url = get_term_link( $term );
} elseif ( is_post_type_archive( 'event' ) ) {
    $base_url = get_post_type_archive_link( 'event' );
}
?>

<div class="wp-events-filters">
    <form method="get" action="<?php echo esc_url( $base_url ); ?>" class="event-filters-form">
        
        <?php if ( ! is_tax( 'event_category' ) ) : // Hide category filter on category pages ?>
        <div class="filter-group">
            <label for="event-category-filter">
                <i class="icon-tag"></i>
                <?php esc_html_e( 'Kategori:', 'wp-events' ); ?>
            </label>
            <select id="event-category-filter" name="event_category">
                <option value=""><?php esc_html_e( 'Alle kategorier', 'wp-events' ); ?></option>
                <?php
                $categories = get_terms( array(
                    'taxonomy' => 'event_category',
                    'hide_empty' => true,
                ) );
                if ( $categories && ! is_wp_error( $categories ) ) :
                    foreach ( $categories as $category ) :
                        $selected = $current_category === $category->slug ? 'selected' : '';
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
        <?php endif; ?>

        <div class="filter-group">
            <label for="event-timeframe-filter">
                <i class="icon-calendar"></i>
                <?php esc_html_e( 'Tidspunkt:', 'wp-events' ); ?>
            </label>
            <select id="event-timeframe-filter" name="timeframe">
                <option value="all" <?php selected( $current_timeframe, 'all' ); ?>>
                    <?php esc_html_e( 'Alle events', 'wp-events' ); ?>
                </option>
                <option value="upcoming" <?php selected( $current_timeframe, 'upcoming' ); ?>>
                    <?php esc_html_e( 'Kommende events', 'wp-events' ); ?>
                </option>
                <option value="past" <?php selected( $current_timeframe, 'past' ); ?>>
                    <?php esc_html_e( 'Tidligere events', 'wp-events' ); ?>
                </option>
                <option value="today" <?php selected( $current_timeframe, 'today' ); ?>>
                    <?php esc_html_e( 'I dag', 'wp-events' ); ?>
                </option>
                <option value="this-week" <?php selected( $current_timeframe, 'this-week' ); ?>>
                    <?php esc_html_e( 'Denne uge', 'wp-events' ); ?>
                </option>
                <option value="this-month" <?php selected( $current_timeframe, 'this-month' ); ?>>
                    <?php esc_html_e( 'Denne måned', 'wp-events' ); ?>
                </option>
            </select>
        </div>

        <div class="filter-group">
            <label for="event-sort-filter">
                <i class="icon-up-dir"></i>
                <?php esc_html_e( 'Sortering:', 'wp-events' ); ?>
            </label>
            <select id="event-sort-filter" name="sort">
                <option value="asc" <?php selected( $current_sort, 'asc' ); ?>>
                    <?php esc_html_e( 'Nærmeste først', 'wp-events' ); ?>
                </option>
                <option value="desc" <?php selected( $current_sort, 'desc' ); ?>>
                    <?php esc_html_e( 'Fjerneste først', 'wp-events' ); ?>
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i class="icon-right-dir"></i>
                <?php esc_html_e( 'Filtrér', 'wp-events' ); ?>
            </button>
            <a href="<?php echo esc_url( $base_url ); ?>" class="btn btn-secondary">
                <?php esc_html_e( 'Nulstil', 'wp-events' ); ?>
            </a>
        </div>
    </form>
</div>

<script>
// Auto-submit on filter change
document.addEventListener('DOMContentLoaded', function() {
    var filterForm = document.querySelector('.event-filters-form');
    if (filterForm) {
        var selects = filterForm.querySelectorAll('select');
        selects.forEach(function(select) {
            select.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
});
</script>
