<?php
/**
 * Template Helper Functions
 * 
 * @package WP Events
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load a WP Events template part with fallback to plugin directory
 * 
 * First tries to load from theme's wp-events folder, then falls back to plugin templates
 * 
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialized template (optional).
 * @return void
 */
function wpevents_get_template_part( $slug, $name = null ) {
    $templates = array();
    
    if ( $name ) {
        $templates[] = "wp-events/{$slug}-{$name}.php";
    }
    $templates[] = "wp-events/{$slug}.php";
    
    // Try to locate in theme
    $located = locate_template( $templates, false, false );
    
    // If not found in theme, use plugin template
    if ( ! $located ) {
        if ( $name && file_exists( WPEVENTS_PLUGIN_DIR . "templates/{$slug}-{$name}.php" ) ) {
            $located = WPEVENTS_PLUGIN_DIR . "templates/{$slug}-{$name}.php";
        } elseif ( file_exists( WPEVENTS_PLUGIN_DIR . "templates/{$slug}.php" ) ) {
            $located = WPEVENTS_PLUGIN_DIR . "templates/{$slug}.php";
        }
    }
    
    // Load the template
    if ( $located ) {
        load_template( $located, false );
    }
}
