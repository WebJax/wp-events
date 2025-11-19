<?php
/**
 * WP Events Blocks - Clean version from scratch
 */
if ( ! defined( 'ABSPATH' ) ) { 
    exit; 
}

class WPEvents_Blocks_Clean {
    
    public static function init() {
        // Add block category
        add_filter( 'block_categories_all', [ __CLASS__, 'add_block_category' ], 10, 2 );
        
        // Register blocks on init
        add_action( 'init', [ __CLASS__, 'register_all_blocks' ], 20 );
        
        // Enqueue scripts for editor
        add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_editor_scripts' ] );
        
        // Enqueue frontend assets
        add_action( 'enqueue_block_assets', [ __CLASS__, 'enqueue_frontend_assets' ] );
        
        // Template loader system
        add_filter( 'template_include', [ __CLASS__, 'template_loader' ] );
    }
    
    /**
     * Add WP Events block category
     */
    public static function add_block_category( $categories, $post ) {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'wp-events',
                    'title' => 'WP Events',
                    'icon' => 'calendar-alt',
                ],
            ]
        );
    }
    
    /**
     * Register all blocks
     */
    public static function register_all_blocks() {
        register_block_type('wp-events/venue', array(
            'render_callback' => array(__CLASS__, 'render_venue_block'),
            'supports' => array(
                'align' => array('left', 'center', 'right'),
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'showAddress' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showContact' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showDirections' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'linkToVenue' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));

                register_block_type('wp-events/organizer', array(
            'render_callback' => array(__CLASS__, 'render_organizer_block'),
            'supports' => array(
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'textAlign' => array(
                    'type' => 'string'
                )
            )
        ));

        register_block_type('wp-events/event-start', array(
            'render_callback' => array(__CLASS__, 'render_event_start_block'),
            'supports' => array(
                'align' => array('left', 'center', 'right'),
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'format' => array(
                    'type' => 'string',
                    'default' => 'F j, Y g:i A'
                ),
                'showLabel' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'customLabel' => array(
                    'type' => 'string',
                    'default' => 'Start:'
                ),
                'labelBold' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'labelItalic' => array(
                    'type' => 'boolean',
                    'default' => false
                )
            )
        ));

        register_block_type('wp-events/event-end', array(
            'render_callback' => array(__CLASS__, 'render_event_end_block'),
            'supports' => array(
                'align' => array('left', 'center', 'right'),
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'format' => array(
                    'type' => 'string',
                    'default' => 'F j, Y g:i A'
                ),
                'showLabel' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'customLabel' => array(
                    'type' => 'string',
                    'default' => 'Slut:'
                ),
                'labelBold' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'labelItalic' => array(
                    'type' => 'boolean',
                    'default' => false
                )
            )
        ));

                register_block_type('wp-events/events-list', array(
            'render_callback' => array(__CLASS__, 'render_events_list_block'),
            'supports' => array(
                'align' => array('left', 'center', 'right', 'wide', 'full'),
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'numberOfEvents' => array(
                    'type' => 'number',
                    'default' => 5
                ),
                'showVenue' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showDate' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showExcerpt' => array(
                    'type' => 'boolean',
                    'default' => false
                )
            )
        ));

        register_block_type('wp-events/events-carousel', array(
            'render_callback' => array(__CLASS__, 'render_events_carousel_block'),
            'supports' => array(
                'align' => array('left', 'center', 'right', 'wide', 'full'),
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'numberOfEvents' => array(
                    'type' => 'number',
                    'default' => 3
                ),
                'autoplay' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showDots' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showArrows' => array(
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));

        // Event Price Block
        register_block_type('wp-events/event-price', array(
            'render_callback' => array(__CLASS__, 'render_event_price_block'),
            'supports' => array(
                'anchor' => true,
                'className' => true,
                'color' => array(
                    'gradients' => true,
                    'link' => true,
                    '__experimentalDefaultControls' => array(
                        'background' => true,
                        'text' => true
                    )
                ),
                'spacing' => array(
                    'margin' => true,
                    'padding' => true,
                    '__experimentalDefaultControls' => array(
                        'margin' => false,
                        'padding' => false
                    )
                ),
                'typography' => array(
                    'fontSize' => true,
                    'fontFamily' => true,
                    'fontStyle' => true,
                    'fontWeight' => true,
                    'letterSpacing' => true,
                    'lineHeight' => true,
                    'textDecoration' => true,
                    'textTransform' => true,
                    '__experimentalDefaultControls' => array(
                        'fontSize' => true
                    )
                )
            ),
            'attributes' => array(
                'textAlign' => array(
                    'type' => 'string'
                ),
                'showLabel' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'customLabel' => array(
                    'type' => 'string',
                    'default' => 'Pris:'
                ),
                'labelBold' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'labelItalic' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'showCurrency' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'priceFormat' => array(
                    'type' => 'string',
                    'default' => 'after'
                )
            )
        ));
    }
    
    /**
     * Enqueue editor scripts
     */
    public static function enqueue_editor_scripts() {
        // Enqueue main blocks script
        wp_enqueue_script(
            'wp-events-blocks',
            WPEVENTS_PLUGIN_URL . 'assets/blocks.js',
            [ 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components' ],
            WPEVENTS_VERSION,
            true
        );
        
        // Enqueue blocks CSS
        wp_enqueue_style(
            'wp-events-blocks-style',
            WPEVENTS_PLUGIN_URL . 'assets/wp-events.css',
            [],
            WPEVENTS_VERSION
        );
        
        // Localize script for translations
        wp_localize_script( 'wp-events-blocks', 'wpEventsBlocks', [
            'category' => 'wp-events'
        ] );
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // Enqueue frontend CSS - only loads when blocks are present on the page
        wp_enqueue_style(
            'wp-events-frontend',
            WPEVENTS_PLUGIN_URL . 'assets/wp-events-frontend.css',
            [],
            WPEVENTS_VERSION
        );

        // Enqueue Swiper for carousel block
        if ( has_block( 'wp-events/events-carousel' ) ) {
            wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0' );
            wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true );
            
            wp_enqueue_script(
                'wp-events-frontend-js',
                WPEVENTS_PLUGIN_URL . 'assets/wp-events-frontend.js',
                [ 'swiper' ],
                WPEVENTS_VERSION,
                true
            );
        }
    }
    
    /**
     * Template loader - checks theme first, then plugin templates
     */
    public static function template_loader( $template ) {
        if ( is_embed() ) {
            return $template;
        }

        $default_file = self::get_template_loader_default_file();

        if ( $default_file ) {
            /**
             * Filter hook to choose which files to find before WP does its thing.
             *
             * @param array $search_files Array of template files to search for.
             * @param string $default_file The default template filename.
             */
            $search_files = self::get_template_loader_files( $default_file );
            $template = locate_template( $search_files );

            if ( ! $template ) {
                $template = WPEVENTS_PLUGIN_DIR . 'templates/' . $default_file;
            }
            
            // Enqueue frontend assets for our templates
            if ( strpos( $template, 'wp-events' ) !== false || strpos( $template, WPEVENTS_PLUGIN_DIR ) !== false ) {
                add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ], 20 );
            }
        }

        return $template;
    }

    /**
     * Get the default filename for a template.
     */
    private static function get_template_loader_default_file() {
        if ( is_single() && get_post_type() === 'event' ) {
            $default_file = 'single-event.php';
        } elseif ( is_single() && get_post_type() === 'venue' ) {
            $default_file = 'single-venue.php';
        } elseif ( is_single() && get_post_type() === 'organizer' ) {
            $default_file = 'single-organizer.php';
        } elseif ( is_post_type_archive( 'event' ) ) {
            $default_file = 'archive-event.php';
        } elseif ( is_tax( 'event_category' ) ) {
            $default_file = 'taxonomy-event_category.php';
        } elseif ( is_tax( 'event_tag' ) ) {
            $default_file = 'taxonomy-event_tag.php';
        } else {
            $default_file = '';
        }

        return $default_file;
    }

    /**
     * Get an array of filenames to search for a given template.
     */
    private static function get_template_loader_files( $default_file ) {
        $templates = array();
        $template = str_replace( WPEVENTS_PLUGIN_DIR . 'templates/', '', $default_file );

        if ( is_tax( 'event_category' ) || is_tax( 'event_tag' ) ) {
            $object = get_queried_object();

            // Look for specific term template first (e.g., taxonomy-event_category-udstilling.php)
            $specific_template = str_replace( '.php', '-' . $object->slug . '.php', $template );
            $templates[] = 'wp-events/' . $specific_template;
            
            // Then general taxonomy template (e.g., taxonomy-event_category.php)
            $templates[] = 'wp-events/' . $template;
        } else {
            $templates[] = 'wp-events/' . $template;
        }

        // Add theme root fallback
        $templates[] = $template;

        return array_unique( $templates );
    }
    
    /**
     * Render venue block
     */
    public static function render_venue_block( $attributes ) {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<p>Venue block can only be used in event posts.</p>';
        }
        
        $venue_id = get_post_meta( $post_id, 'event_venue', true );
        if ( ! $venue_id ) {
            return '<p>No venue assigned to this event.</p>';
        }
        
        $venue = get_post( $venue_id );
        if ( ! $venue ) {
            return '<p>Venue not found.</p>';
        }

        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'wp-events-venue'
        ) );
        
        $output = '<div ' . $wrapper_attributes . '>';
        
        $venue_title = esc_html( $venue->post_title );
        if ( ! empty( $attributes['linkToVenue'] ) ) {
            $venue_title = '<a href="' . get_permalink($venue_id) . '">' . $venue_title . '</a>';
        }
        $output .= '<h3>' . $venue_title . '</h3>';
        
        if ( ! empty( $attributes['showAddress'] ) ) {
            $address = get_post_meta( $venue_id, 'venue_address', true );
            $city = get_post_meta( $venue_id, 'venue_city', true );
            $postal_code = get_post_meta( $venue_id, 'venue_postal_code', true );
            $country = get_post_meta( $venue_id, 'venue_country', true );
            
            $full_address = array_filter([$address, $postal_code . ' ' . $city, $country]);
            
            if ( !empty($full_address) ) {
                $output .= '<div class="venue-address">';
                foreach ($full_address as $line) {
                    $output .= '<div>' . esc_html(trim($line)) . '</div>';
                }
                $output .= '</div>';
            }
        }
        
        if ( ! empty( $attributes['showContact'] ) ) {
            $phone = get_post_meta( $venue_id, 'venue_phone', true );
            $email = get_post_meta( $venue_id, 'venue_email', true );
            
            if ( $phone ) {
                $output .= '<p class="venue-phone">Phone: ' . esc_html( $phone ) . '</p>';
            }
            if ( $email ) {
                $output .= '<p class="venue-email">Email: <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
            }
        }
        
        // Show directions link if enabled
        if ( ! empty( $attributes['showDirections'] ) ) {
            $show_directions = get_post_meta( $venue_id, 'venue_show_directions', true );
            if ( $show_directions ) {
                $directions_url = WPEvents_CPT::get_venue_directions_url( $venue_id );
                if ( $directions_url ) {
                    $output .= '<p class="venue-directions">';
                    $output .= '<a href="' . esc_url( $directions_url ) . '" target="_blank" rel="noopener">';
                    $output .= '<span class="icon-location"></span> ';
                    $output .= __( 'Find vej', 'wp-events' );
                    $output .= '</a>';
                    $output .= '</p>';
                }
            }
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Render organizer block
     */
    public static function render_organizer_block( $attributes ) {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<p>Organizer block can only be used in event posts.</p>';
        }
        
        $organizer_ids = get_post_meta( $post_id, 'event_organizer', true );
        if ( ! is_array( $organizer_ids ) || empty( $organizer_ids ) ) {
            return '<p>No organizer assigned to this event.</p>';
        }

        // Build CSS classes including text alignment
        $classes = array('wp-events-organizer-heading');
        if ( ! empty( $attributes['textAlign'] ) ) {
            $classes[] = 'has-text-align-' . $attributes['textAlign'];
        }
        
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => implode( ' ', $classes )
        ) );
        
        // Get first organizer only for heading
        $organizer = get_post( $organizer_ids[0] );
        if ( ! $organizer ) {
            return '<p>Organizer not found.</p>';
        }
        
        $organizer_title = esc_html( $organizer->post_title );
        
        return '<h2 ' . $wrapper_attributes . '>' . $organizer_title . '</h2>';
    }
    
    /**
     * Render start block
     */
    public static function render_event_start_block( $attributes ) {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<p>Start time block can only be used in event posts.</p>';
        }
        
        $start_date = get_post_meta( $post_id, 'event_start', true );
        if ( ! $start_date ) {
            return '<p>No start date set for this event.</p>';
        }
        
        $timestamp = strtotime( $start_date );
        if ( ! $timestamp ) {
            return '<p>Invalid start date format.</p>';
        }
        
        $formatted_date = wp_date( 'j. F Y \k\l. H:i', $timestamp );
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'wp-events-start'
        ) );
        
        $output = '<div ' . $wrapper_attributes . '>';
        if ( ! empty( $attributes['showLabel'] ) ) {
            $label_text = ! empty( $attributes['customLabel'] ) ? $attributes['customLabel'] : 'Start:';
            $label_styles = array();
            
            if ( ! empty( $attributes['labelBold'] ) ) {
                $label_styles[] = 'font-weight: bold';
            }
            if ( ! empty( $attributes['labelItalic'] ) ) {
                $label_styles[] = 'font-style: italic';
            }
            
            $label_style_attr = ! empty( $label_styles ) ? ' style="' . implode('; ', $label_styles) . '"' : '';
            $output .= '<span class="label"' . $label_style_attr . '>' . esc_html( $label_text ) . ' </span>';
        }
        $output .= '<time datetime="' . esc_attr( $start_date ) . '">' . esc_html( $formatted_date ) . '</time>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render end block
     */
    public static function render_event_end_block( $attributes ) {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<p>End time block can only be used in event posts.</p>';
        }
        
        $end_date = get_post_meta( $post_id, 'event_end', true );
        if ( ! $end_date ) {
            return '<p>No end date set for this event.</p>';
        }
        
        $timestamp = strtotime( $end_date );
        if ( ! $timestamp ) {
            return '<p>Invalid end date format.</p>';
        }
        
        $formatted_date = wp_date( 'j. F Y \k\l. H:i', $timestamp );
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'wp-events-end'
        ) );
        
        $output = '<div ' . $wrapper_attributes . '>';
        if ( ! empty( $attributes['showLabel'] ) ) {
            $label_text = ! empty( $attributes['customLabel'] ) ? $attributes['customLabel'] : 'Slut:';
            $label_styles = array();
            
            if ( ! empty( $attributes['labelBold'] ) ) {
                $label_styles[] = 'font-weight: bold';
            }
            if ( ! empty( $attributes['labelItalic'] ) ) {
                $label_styles[] = 'font-style: italic';
            }
            
            $label_style_attr = ! empty( $label_styles ) ? ' style="' . implode('; ', $label_styles) . '"' : '';
            $output .= '<span class="label"' . $label_style_attr . '>' . esc_html( $label_text ) . ' </span>';
        }
        $output .= '<time datetime="' . esc_attr( $end_date ) . '">' . esc_html( $formatted_date ) . '</time>';
        $output .= '</div>';
        
        return $output;
    }

    public static function render_events_list_block($attributes) {
        $limit = isset($attributes['numberOfEvents']) ? intval($attributes['numberOfEvents']) : 5;
        
        $args = array(
            'post_type' => 'event',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'event_start',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'meta_key' => 'event_start',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        
        $events = get_posts($args);
        
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'wp-events-list'
        ) );

        if (empty($events)) {
            return '<div ' . $wrapper_attributes . '>Ingen kommende events fundet.</div>';
        }
        
        $output = '<div ' . $wrapper_attributes . '>';
        foreach ($events as $event) {
            $start_date = get_post_meta($event->ID, 'event_start', true);
            $venue_id = get_post_meta($event->ID, 'event_venue', true);
            $venue = $venue_id ? get_the_title($venue_id) : '';
            
            $output .= '<div class="event-item">';
            $output .= '<h3><a href="' . get_permalink($event->ID) . '">' . get_the_title($event->ID) . '</a></h3>';
            if ($start_date) {
                $output .= '<div class="event-date">' . date('d. M Y', strtotime($start_date)) . '</div>';
            }
            if ($venue) {
                $output .= '<div class="event-venue">' . esc_html($venue) . '</div>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        
        return $output;
    }

    public static function render_events_carousel_block($attributes) {
        $limit = isset($attributes['numberOfEvents']) ? intval($attributes['numberOfEvents']) : 3;
        
        $args = array(
            'post_type' => 'event',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'event_start',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
            'meta_key' => 'event_start',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        
        $events = get_posts($args);
        
        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => 'wp-events-carousel swiper'
        ) );

        if (empty($events)) {
            return '<div ' . $wrapper_attributes . '>Ingen kommende events fundet.</div>';
        }
        
        $output = '<div ' . $wrapper_attributes . '>';
        $output .= '<div class="swiper-wrapper">';
        foreach ($events as $event) {
            $start_date = get_post_meta($event->ID, 'event_start', true);
            $venue_id = get_post_meta($event->ID, 'event_venue', true);
            $venue = $venue_id ? get_the_title($venue_id) : '';
            $featured_image = get_the_post_thumbnail($event->ID, 'medium');
            
            $output .= '<div class="swiper-slide">';
            $output .= '<div class="event-card">';
            if ($featured_image) {
                $output .= '<div class="event-image">' . $featured_image . '</div>';
            }
            $output .= '<div class="event-content">';
            $output .= '<h3><a href="' . get_permalink($event->ID) . '">' . get_the_title($event->ID) . '</a></h3>';
            if ($start_date) {
                $output .= '<div class="event-date">' . date('d. M Y', strtotime($start_date)) . '</div>';
            }
            if ($venue) {
                $output .= '<div class="event-venue">' . esc_html($venue) . '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>'; // .swiper-wrapper
        
        if ( ! empty( $attributes['showDots'] ) ) {
            $output .= '<div class="swiper-pagination"></div>';
        }
        if ( ! empty( $attributes['showArrows'] ) ) {
            $output .= '<div class="swiper-button-next"></div>';
            $output .= '<div class="swiper-button-prev"></div>';
        }
        
        $output .= '</div>'; // .swiper
        
        return $output;
    }

    // Event Price Block
    public static function render_event_price_block( $attributes, $content, $block ) {
        $post_id = get_the_ID();
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<p>Price block can only be used in event posts.</p>';
        }
        
        $price = get_post_meta( $post_id, 'event_price', true );
        $currency = get_post_meta( $post_id, 'event_currency', true );

        if ( empty( $price ) || ! is_numeric( $price ) || (float) $price <= 0 ) {
            return '';
        }

        // Get attributes
        $text_align = isset( $attributes['textAlign'] ) ? $attributes['textAlign'] : '';
        $show_label = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;
        $custom_label = isset( $attributes['customLabel'] ) ? $attributes['customLabel'] : 'Pris';
        $label_bold = isset( $attributes['labelBold'] ) ? $attributes['labelBold'] : false;
        $label_italic = isset( $attributes['labelItalic'] ) ? $attributes['labelItalic'] : false;
        $show_currency = isset( $attributes['showCurrency'] ) ? $attributes['showCurrency'] : true;
        $price_format = isset( $attributes['priceFormat'] ) ? $attributes['priceFormat'] : 'after';

        // Build wrapper classes
        $classes = array( 'wp-events-price' );
        if ( ! empty( $text_align ) ) {
            $classes[] = 'has-text-align-' . $text_align;
        }

        $wrapper_attributes = get_block_wrapper_attributes( array(
            'class' => implode( ' ', $classes )
        ) );

        // Format currency
        $currency_code = ! empty( $currency ) ? strtoupper( $currency ) : 'DKK';
        
        // Common currency symbols
        $currency_symbols = array(
            'DKK' => 'kr',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'NOK' => 'kr',
            'SEK' => 'kr'
        );
        
        $currency_symbol = isset( $currency_symbols[$currency_code] ) ? $currency_symbols[$currency_code] : $currency_code;

        // Format price with currency
        $formatted_price = number_format( floatval( $price ), 0, ',', '.' );
        
        if ( $show_currency ) {
            if ( $price_format === 'before' ) {
                $price_display = $currency_symbol . ' ' . $formatted_price;
            } else {
                $price_display = $formatted_price . ' ' . $currency_symbol;
            }
        } else {
            $price_display = $formatted_price;
        }

        // Build label HTML
        $label_html = '';
        if ( $show_label && ! empty( $custom_label ) ) {
            $label_classes = array();
            if ( $label_bold ) {
                $label_classes[] = 'label-bold';
            }
            if ( $label_italic ) {
                $label_classes[] = 'label-italic';
            }
            
            $label_class_attr = ! empty( $label_classes ) ? ' class="' . implode( ' ', $label_classes ) . '"' : '';
            $label_html = '<span' . $label_class_attr . '>' . esc_html( $custom_label ) . ':</span> ';
        }

        // Build output with schema markup
        $output = '<div ' . $wrapper_attributes . '>';
        $output .= '<div class="price-container" itemscope itemtype="https://schema.org/Offer">';
        $output .= $label_html;
        $output .= '<span class="price-amount" itemprop="price" content="' . esc_attr( $price ) . '">';
        $output .= $price_display;
        $output .= '</span>';
        if ( $show_currency ) {
            $output .= '<meta itemprop="priceCurrency" content="' . esc_attr( $currency_code ) . '">';
        }
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }


}