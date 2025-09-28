<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_Blocks {
    public static function register() {
        error_log( 'WP Events: WPEvents_Blocks::register() called' );
        add_action( 'init', [ __CLASS__, 'register_scripts' ], 9 ); // Register scripts first
        add_action( 'init', [ __CLASS__, 'register_blocks' ], 10 ); // Then register blocks
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
        add_filter( 'block_categories_all', [ __CLASS__, 'add_block_category' ], 10, 2 );
    }
    
    public static function add_block_category( $categories, $post ) {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'wp-events',
                    'title' => __( 'WP Events', 'wp-events' ),
                    'icon' => 'calendar-alt',
                ],
            ]
        );
    }

    public static function register_blocks() {
        // Debug: Check if this method is even called
        error_log( 'WP Events: register_blocks method called' );
        
        // Try a simple test registration first
        $result = register_block_type( 'wp-events/test-simple', [
            'render_callback' => function() {
                return '<div>Simple test block</div>';
            }
        ]);
        
        error_log( 'WP Events: Simple test block result: ' . ($result ? 'SUCCESS' : 'FAILED') );
        
        // Register individual event component blocks manually (to ensure they work)
        register_block_type( 'wp-events/event-venue', [
            'render_callback' => [ __CLASS__, 'render_event_venue' ],
            'category' => 'wp-events',
            // 'editor_script' => 'wp-events-venue-block', // Temporarily commented out
            'style' => 'wp-events-blocks',
            'attributes' => [
                'showAddress' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'showContact' => [
                    'type' => 'boolean',
                    'default' => false
                ],
                'linkToVenue' => [
                    'type' => 'boolean',
                    'default' => true
                ]
            ]
        ] );
        
        register_block_type( 'wp-events/event-organizer', [
            'render_callback' => [ __CLASS__, 'render_event_organizer' ],
            'category' => 'wp-events',
            'editor_script' => 'wp-events-organizer-block',
            'style' => 'wp-events-blocks',
            'attributes' => [
                'showContact' => [
                    'type' => 'boolean',
                    'default' => false
                ],
                'linkToOrganizer' => [
                    'type' => 'boolean',
                    'default' => true
                ]
            ]
        ] );
        
        register_block_type( 'wp-events/event-start', [
            'render_callback' => [ __CLASS__, 'render_event_start' ],
            'category' => 'wp-events',
            'editor_script' => 'wp-events-start-block',
            'style' => 'wp-events-blocks',
            'attributes' => [
                'dateFormat' => [
                    'type' => 'string',
                    'default' => 'full'
                ],
                'customFormat' => [
                    'type' => 'string',
                    'default' => 'j. F Y \\k\\l. H:i'
                ],
                'showLabel' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'label' => [
                    'type' => 'string',
                    'default' => 'Starts:'
                ]
            ]
        ] );
        
        register_block_type( 'wp-events/event-end', [
            'render_callback' => [ __CLASS__, 'render_event_end' ],
            'category' => 'wp-events',
            'editor_script' => 'wp-events-end-block',
            'style' => 'wp-events-blocks',
            'attributes' => [
                'dateFormat' => [
                    'type' => 'string',
                    'default' => 'full'
                ],
                'customFormat' => [
                    'type' => 'string',
                    'default' => 'j. F Y \\k\\l. H:i'
                ],
                'showLabel' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'label' => [
                    'type' => 'string',
                    'default' => 'Ends:'
                ],
                'hideIfSameDay' => [
                    'type' => 'boolean',
                    'default' => true
                ]
            ]
        ] );
        
        // Register legacy blocks (events list and carousel) if they exist
        if ( file_exists( __DIR__ . '/../blocks/events-list.js' ) ) {
            register_block_type( 'wp-events/events-list', [
                'render_callback' => [ __CLASS__, 'render_events_list' ],
                'category' => 'wp-events',
                'editor_script' => 'wp-events-list-block',
                'style' => 'wp-events-blocks',
            ] );
        }
        
        if ( file_exists( __DIR__ . '/../blocks/events-carousel.js' ) ) {
            register_block_type( 'wp-events/events-carousel', [
                'render_callback' => [ __CLASS__, 'render_events_carousel' ],
                'category' => 'wp-events',
                'editor_script' => 'wp-events-carousel-block',
                'style' => 'wp-events-blocks',
            ] );
        }
    }

    public static function register_scripts() {
        error_log( 'WP Events: register_scripts method called' );
        $deps = [ 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n', 'wp-block-editor', 'wp-components' ];
        
        // Register individual event component blocks
        wp_register_script( 'wp-events-venue-block', plugins_url( '../blocks/event-venue/index.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        wp_register_script( 'wp-events-organizer-block', plugins_url( '../blocks/event-organizer/index.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        wp_register_script( 'wp-events-start-block', plugins_url( '../blocks/event-start/index.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        wp_register_script( 'wp-events-end-block', plugins_url( '../blocks/event-end/index.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        
        // Legacy blocks (keep for backwards compatibility if they exist)
        if ( file_exists( plugin_dir_path( __FILE__ ) . '../blocks/events-list.js' ) ) {
            wp_register_script( 'wp-events-list-block', plugins_url( '../blocks/events-list.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        }
        if ( file_exists( plugin_dir_path( __FILE__ ) . '../blocks/events-carousel.js' ) ) {
            wp_register_script( 'wp-events-carousel-block', plugins_url( '../blocks/events-carousel.js', __FILE__ ), $deps, WPEVENTS_VERSION, true );
        }
        
        // Register CSS for blocks (both editor and frontend)
        wp_register_style( 'wp-events-blocks', plugins_url( '../assets/wp-events.css', __FILE__ ), [], WPEVENTS_VERSION );
    }

    public static function enqueue_frontend_assets() {
        // Enqueue blocks CSS for frontend
        wp_enqueue_style( 'wp-events-blocks' );
        
        // Enqueue Swiper for carousels
        wp_enqueue_script( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', [], '10.0.0', true );
        wp_enqueue_style( 'swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', [], '10.0.0' );
    }

    public static function render_events_list( $attributes = [], $content = '' ) {
        // Reuse shortcode output
        return WPEvents_Shortcodes::events_list( [ 'limit' => isset( $attributes['limit'] ) ? $attributes['limit'] : 5 ] );
    }

    public static function render_events_carousel( $attributes = [], $content = '' ) {
        $atts = [ 'limit' => isset( $attributes['limit'] ) ? $attributes['limit'] : 5 ];
        $q = new WP_Query( [
            'post_type' => 'event',
            'posts_per_page' => (int) $atts['limit'],
            'post_status' => 'publish',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => 'event_start',
            'meta_type' => 'DATETIME',
            'meta_query' => [
                [
                    'key' => 'event_start',
                    'value' => wp_date( DATE_ATOM, time(), wp_timezone() ),
                    'compare' => '>=',
                    'type' => 'CHAR',
                ]
            ],
        ] );
        if ( ! $q->have_posts() ) return '<div class="wp-events-carousel empty">' . esc_html__( 'No upcoming events', 'wp-events' ) . '</div>';
        ob_start();
        echo '<div class="swiper wp-events-carousel"><div class="swiper-wrapper">';
        while ( $q->have_posts() ) { $q->the_post();
            $start = get_post_meta( get_the_ID(), 'event_start', true );
            $venue_id = (int) get_post_meta( get_the_ID(), 'event_venue', true );
            echo '<div class="swiper-slide wp-event-item">';
            echo '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
            if ( $start ) {
                echo ' <time datetime="' . esc_attr( $start ) . '">' . esc_html( wp_date( 'j. M Y H:i', strtotime( $start ) ) ) . '</time>';
            }
            if ( $venue_id ) {
                echo ' <span class="venue">' . esc_html( get_the_title( $venue_id ) ) . '</span>';
            }
            echo '</div>';
        }
        echo '</div></div>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){if(window.Swiper){new Swiper(".wp-events-carousel",{slidesPerView:1,spaceBetween:10,loop:true,pagination:{el:".swiper-pagination",clickable:true},navigation:{nextEl:".swiper-button-next",prevEl:".swiper-button-prev"}});}});</script>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function render_event_venue( $attributes = [], $content = '', $block = null ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        // Debug information for development
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'WP Events: Rendering venue block for post ID: ' . $post_id );
        }
        
        // Check if we're in an event post or have event context
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<div class="wp-events-venue-block error">' . esc_html__( 'This block can only be used within event posts. Current post type: ', 'wp-events' ) . get_post_type( $post_id ) . '</div>';
        }
        
        $venue_id = (int) get_post_meta( $post_id, 'event_venue', true );
        if ( ! $venue_id ) {
            return '<div class="wp-events-venue-block empty">' . esc_html__( 'No venue assigned to this event.', 'wp-events' ) . '</div>';
        }
        
        $venue = get_post( $venue_id );
        if ( ! $venue || $venue->post_status !== 'publish' ) {
            return '<div class="wp-events-venue-block error">' . esc_html__( 'Venue not found.', 'wp-events' ) . '</div>';
        }
        
        $showAddress = isset( $attributes['showAddress'] ) ? $attributes['showAddress'] : true;
        $showContact = isset( $attributes['showContact'] ) ? $attributes['showContact'] : false;
        $linkToVenue = isset( $attributes['linkToVenue'] ) ? $attributes['linkToVenue'] : true;
        
        $address = get_post_meta( $venue_id, 'venue_address', true );
        $phone = get_post_meta( $venue_id, 'venue_phone', true );
        $email = get_post_meta( $venue_id, 'venue_email', true );
        $website = get_post_meta( $venue_id, 'venue_website', true );
        
        ob_start();
        echo '<div class="wp-events-venue-block">';
        
        if ( $linkToVenue ) {
            echo '<h3><a href="' . esc_url( get_permalink( $venue_id ) ) . '">' . esc_html( $venue->post_title ) . '</a></h3>';
        } else {
            echo '<h3>' . esc_html( $venue->post_title ) . '</h3>';
        }
        
        if ( $showAddress && $address ) {
            echo '<div class="venue-address">' . esc_html( $address ) . '</div>';
        }
        
        if ( $showContact ) {
            if ( $phone ) {
                echo '<div class="venue-phone"><strong>' . esc_html__( 'Phone:', 'wp-events' ) . '</strong> ' . esc_html( $phone ) . '</div>';
            }
            if ( $email ) {
                echo '<div class="venue-email"><strong>' . esc_html__( 'Email:', 'wp-events' ) . '</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>';
            }
            if ( $website ) {
                echo '<div class="venue-website"><strong>' . esc_html__( 'Website:', 'wp-events' ) . '</strong> <a href="' . esc_url( $website ) . '" target="_blank">' . esc_html( $website ) . '</a></div>';
            }
        }
        
        echo '</div>';
        return ob_get_clean();
    }

    public static function render_event_organizer( $attributes = [], $content = '', $block = null ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<div class="wp-events-organizer-block error">' . esc_html__( 'This block can only be used within event posts.', 'wp-events' ) . '</div>';
        }
        
        $organizer_ids = get_post_meta( $post_id, 'event_organizer', true );
        if ( ! is_array( $organizer_ids ) || empty( $organizer_ids ) ) {
            return '<div class="wp-events-organizer-block empty">' . esc_html__( 'No organizer assigned to this event.', 'wp-events' ) . '</div>';
        }
        
        $showContact = isset( $attributes['showContact'] ) ? $attributes['showContact'] : false;
        $linkToOrganizer = isset( $attributes['linkToOrganizer'] ) ? $attributes['linkToOrganizer'] : true;
        
        ob_start();
        echo '<div class="wp-events-organizer-block">';
        
        foreach ( $organizer_ids as $organizer_id ) {
            $organizer = get_post( $organizer_id );
            if ( ! $organizer || $organizer->post_status !== 'publish' ) continue;
            
            echo '<div class="organizer-item">';
            
            if ( $linkToOrganizer ) {
                echo '<h3><a href="' . esc_url( get_permalink( $organizer_id ) ) . '">' . esc_html( $organizer->post_title ) . '</a></h3>';
            } else {
                echo '<h3>' . esc_html( $organizer->post_title ) . '</h3>';
            }
            
            if ( $showContact ) {
                $phone = get_post_meta( $organizer_id, 'organizer_phone', true );
                $email = get_post_meta( $organizer_id, 'organizer_email', true );
                $website = get_post_meta( $organizer_id, 'organizer_website', true );
                
                if ( $phone ) {
                    echo '<div class="organizer-phone"><strong>' . esc_html__( 'Phone:', 'wp-events' ) . '</strong> ' . esc_html( $phone ) . '</div>';
                }
                if ( $email ) {
                    echo '<div class="organizer-email"><strong>' . esc_html__( 'Email:', 'wp-events' ) . '</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></div>';
                }
                if ( $website ) {
                    echo '<div class="organizer-website"><strong>' . esc_html__( 'Website:', 'wp-events' ) . '</strong> <a href="' . esc_url( $website ) . '" target="_blank">' . esc_html( $website ) . '</a></div>';
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        return ob_get_clean();
    }

    public static function render_event_start( $attributes = [], $content = '', $block = null ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<div class="wp-events-start-block error">' . esc_html__( 'This block can only be used within event posts.', 'wp-events' ) . '</div>';
        }
        
        $start_date = get_post_meta( $post_id, 'event_start', true );
        if ( ! $start_date ) {
            return '<div class="wp-events-start-block empty">' . esc_html__( 'No start date set for this event.', 'wp-events' ) . '</div>';
        }
        
        $dateFormat = isset( $attributes['dateFormat'] ) ? $attributes['dateFormat'] : 'full';
        $customFormat = isset( $attributes['customFormat'] ) ? $attributes['customFormat'] : 'j. F Y \\k\\l. H:i';
        $showLabel = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;
        $label = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Starts:', 'wp-events' );
        
        $timestamp = strtotime( $start_date );
        if ( ! $timestamp ) {
            return '<div class="wp-events-start-block error">' . esc_html__( 'Invalid start date format.', 'wp-events' ) . '</div>';
        }
        
        switch ( $dateFormat ) {
            case 'date-only':
                $formatted_date = wp_date( 'j. F Y', $timestamp );
                break;
            case 'time-only':
                $formatted_date = wp_date( 'H:i', $timestamp );
                break;
            case 'custom':
                $formatted_date = wp_date( $customFormat, $timestamp );
                break;
            default: // full
                $formatted_date = wp_date( 'j. F Y \\k\\l. H:i', $timestamp );
                break;
        }
        
        ob_start();
        echo '<div class="wp-events-start-block">';
        if ( $showLabel ) {
            echo '<span class="event-start-label">' . esc_html( $label ) . '</span> ';
        }
        echo '<time class="event-start-time" datetime="' . esc_attr( $start_date ) . '">' . esc_html( $formatted_date ) . '</time>';
        echo '</div>';
        return ob_get_clean();
    }

    public static function render_event_end( $attributes = [], $content = '', $block = null ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) {
            return '<div class="wp-events-end-block error">' . esc_html__( 'This block can only be used within event posts.', 'wp-events' ) . '</div>';
        }
        
        $end_date = get_post_meta( $post_id, 'event_end', true );
        if ( ! $end_date ) {
            return '<div class="wp-events-end-block empty">' . esc_html__( 'No end date set for this event.', 'wp-events' ) . '</div>';
        }
        
        $dateFormat = isset( $attributes['dateFormat'] ) ? $attributes['dateFormat'] : 'full';
        $customFormat = isset( $attributes['customFormat'] ) ? $attributes['customFormat'] : 'j. F Y \\k\\l. H:i';
        $showLabel = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;
        $label = isset( $attributes['label'] ) ? $attributes['label'] : __( 'Ends:', 'wp-events' );
        $hideIfSameDay = isset( $attributes['hideIfSameDay'] ) ? $attributes['hideIfSameDay'] : true;
        
        $end_timestamp = strtotime( $end_date );
        if ( ! $end_timestamp ) {
            return '<div class="wp-events-end-block error">' . esc_html__( 'Invalid end date format.', 'wp-events' ) . '</div>';
        }
        
        // Check if we should hide date part if same day as start
        if ( $hideIfSameDay && $dateFormat === 'full' ) {
            $start_date = get_post_meta( $post_id, 'event_start', true );
            if ( $start_date ) {
                $start_timestamp = strtotime( $start_date );
                if ( $start_timestamp && wp_date( 'Y-m-d', $start_timestamp ) === wp_date( 'Y-m-d', $end_timestamp ) ) {
                    $formatted_date = wp_date( 'H:i', $end_timestamp );
                } else {
                    $formatted_date = wp_date( 'j. F Y \\k\\l. H:i', $end_timestamp );
                }
            } else {
                $formatted_date = wp_date( 'j. F Y \\k\\l. H:i', $end_timestamp );
            }
        } else {
            switch ( $dateFormat ) {
                case 'date-only':
                    $formatted_date = wp_date( 'j. F Y', $end_timestamp );
                    break;
                case 'time-only':
                    $formatted_date = wp_date( 'H:i', $end_timestamp );
                    break;
                case 'custom':
                    $formatted_date = wp_date( $customFormat, $end_timestamp );
                    break;
                default: // full
                    $formatted_date = wp_date( 'j. F Y \\k\\l. H:i', $end_timestamp );
                    break;
            }
        }
        
        ob_start();
        echo '<div class="wp-events-end-block">';
        if ( $showLabel ) {
            echo '<span class="event-end-label">' . esc_html( $label ) . '</span> ';
        }
        echo '<time class="event-end-time" datetime="' . esc_attr( $end_date ) . '">' . esc_html( $formatted_date ) . '</time>';
        echo '</div>';
        return ob_get_clean();
    }
}
