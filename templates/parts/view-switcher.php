<?php
/**
 * View Switcher Component
 * 
 * Allows users to switch between different event archive views
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Only show on archive pages
if ( ! is_post_type_archive( 'event' ) ) {
    return;
}

// Get current view
$current_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'grid';

// Build base URL
$base_url = get_post_type_archive_link( 'event' );

// Preserve query parameters
$query_params = $_GET;
unset( $query_params['view'] ); // Remove view param, we'll add it back

// Build query string for other params
$query_string = http_build_query( $query_params );
$url_separator = $query_string ? '&' : '';

// View options
$views = array(
    'grid' => array(
        'label' => __( 'Kalender', 'wp-events' ),
        'icon' => 'icon-calendar',
        'url' => $base_url . ( $query_string ? '?' . $query_string : '' ),
    ),
    'list' => array(
        'label' => __( 'Liste', 'wp-events' ),
        'icon' => 'icon-down-dir',
        'url' => $base_url . '?view=list' . ( $query_string ? $url_separator . $query_string : '' ),
    ),
    'compact' => array(
        'label' => __( 'Kompakt', 'wp-events' ),
        'icon' => 'icon-right-dir',
        'url' => $base_url . '?view=compact' . ( $query_string ? $url_separator . $query_string : '' ),
    ),
);
?>

<div class="wp-events-view-switcher">
    <span class="view-switcher-label"><?php esc_html_e( 'Visning:', 'wp-events' ); ?></span>
    <div class="view-switcher-buttons">
        <?php foreach ( $views as $view_key => $view_data ) : 
            $is_active = ( $view_key === $current_view ) ? 'active' : '';
        ?>
            <a href="<?php echo esc_url( $view_data['url'] ); ?>" 
               class="view-switcher-button <?php echo esc_attr( $is_active ); ?>"
               title="<?php echo esc_attr( $view_data['label'] ); ?>">
                <span class="<?php echo esc_attr( $view_data['icon'] ); ?>"></span>
                <span class="view-label"><?php echo esc_html( $view_data['label'] ); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
