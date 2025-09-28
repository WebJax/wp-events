<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WPEvents_CPT {
    public static function register() {
        self::register_taxonomies();
        self::register_cpts();
        self::register_meta();
        add_action('add_meta_boxes_event', [__CLASS__, 'add_event_meta_box']);
        add_action('save_post_event', [__CLASS__, 'save_event_times']);
        add_action('add_meta_boxes_event', [__CLASS__, 'add_recurrence_meta_box']);
        add_action('save_post_event', [__CLASS__, 'save_recurrence_meta']);
        add_action('add_meta_boxes_event', [__CLASS__, 'add_event_venue_organizer_box']);
        add_action('save_post_event', [__CLASS__, 'save_event_venue_organizer']);
        add_action('add_meta_boxes_event', [__CLASS__, 'add_event_price_box']);
        add_action('save_post_event', [__CLASS__, 'save_event_price']);
        add_action('add_meta_boxes_venue', [__CLASS__, 'add_venue_meta_box']);
        add_action('save_post_venue', [__CLASS__, 'save_venue_meta']);
        add_action('add_meta_boxes_organizer', [__CLASS__, 'add_organizer_meta_box']);
        add_action('save_post_organizer', [__CLASS__, 'save_organizer_meta']);
        
        // Admin scripts and AJAX
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
        add_action('wp_ajax_set_venue_featured_image', [__CLASS__, 'ajax_set_venue_featured_image']);
        add_action('wp_ajax_remove_venue_featured_image', [__CLASS__, 'ajax_remove_venue_featured_image']);
    }

    protected static function register_taxonomies() {
        // Event categories
        register_taxonomy( 'event_category', 'event', [
            'label' => __( 'Event Categories', 'wp-events' ),
            'labels' => [
                'name' => __( 'Event Categories', 'wp-events' ),
                'singular_name' => __( 'Event Category', 'wp-events' ),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => [ 'slug' => 'event-category' ],
        ] );

        // Event tags
        register_taxonomy( 'event_tag', 'event', [
            'label' => __( 'Event Tags', 'wp-events' ),
            'labels' => [
                'name' => __( 'Event Tags', 'wp-events' ),
                'singular_name' => __( 'Event Tag', 'wp-events' ),
            ],
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => [ 'slug' => 'event-tag' ],
        ] );

        // Venue categories
        register_taxonomy( 'venue_category', 'venue', [
            'label' => __( 'Venue Categories', 'wp-events' ),
            'labels' => [
                'name' => __( 'Venue Categories', 'wp-events' ),
                'singular_name' => __( 'Venue Category', 'wp-events' ),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => [ 'slug' => 'venue-category' ],
        ] );

        // Organizer categories
        register_taxonomy( 'organizer_category', 'organizer', [
            'label' => __( 'Organizer Categories', 'wp-events' ),
            'labels' => [
                'name' => __( 'Organizer Categories', 'wp-events' ),
                'singular_name' => __( 'Organizer Category', 'wp-events' ),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => [ 'slug' => 'organizer-category' ],
        ] );
    }

    protected static function register_cpts() {
        // Event (main menu item)
        register_post_type( 'event', [
            'label' => __( 'Events', 'wp-events' ),
            'labels' => [
                'name' => __( 'Events', 'wp-events' ),
                'singular_name' => __( 'Event', 'wp-events' ),
                'menu_name' => __( 'WP Events', 'wp-events' ),
            ],
            'public' => true,
            'show_in_rest' => true,
            'supports' => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
            'has_archive' => true,
            'rewrite' => [ 'slug' => 'events' ],
            'menu_icon' => 'dashicons-calendar-alt',
            'taxonomies' => [ 'event_category', 'event_tag' ],
        ] );

        // Organizer (sub-menu)
        register_post_type( 'organizer', [
            'label' => __( 'Organizers', 'wp-events' ),
            'labels' => [
                'name' => __( 'Organizers', 'wp-events' ),
                'singular_name' => __( 'Organizer', 'wp-events' ),
            ],
            'public' => true,
            'show_in_rest' => true,
            'supports' => [ 'title' ],
            'rewrite' => [ 'slug' => 'organizers' ],
            'show_in_menu' => 'edit.php?post_type=event',
            'taxonomies' => [ 'organizer_category' ],
        ] );

        // Venue (sub-menu)
        register_post_type( 'venue', [
            'label' => __( 'Venues', 'wp-events' ),
            'labels' => [
                'name' => __( 'Venues', 'wp-events' ),
                'singular_name' => __( 'Venue', 'wp-events' ),
            ],
            'public' => true,
            'show_in_rest' => true,
            'supports' => [ 'title' ],
            'rewrite' => [ 'slug' => 'venues' ],
            'show_in_menu' => 'edit.php?post_type=event',
            'taxonomies' => [ 'venue_category' ],
        ] );
    }

    protected static function register_meta() {
        // Event meta
        register_post_meta( 'event', 'event_start', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [ __CLASS__, 'sanitize_iso8601' ],
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'event_end', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [ __CLASS__, 'sanitize_iso8601' ],
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'event_price', [
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => function( $value ) { return is_numeric( $value ) ? (float) $value : ''; },
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'event_currency', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => function( $value ) { $v = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $value ) ); return substr( $v, 0, 3 ); },
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );

        // Relationships
        register_post_meta( 'event', 'event_organizer', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
            ],
            'sanitize_callback' => [ __CLASS__, 'sanitize_ids_array' ],
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'event_venue', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );

        // Recurrence
        register_post_meta( 'event', 'recurrence_type', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [ __CLASS__, 'sanitize_recurrence_type' ],
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'recurrence_interval', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'recurrence_end', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [ __CLASS__, 'sanitize_date' ],
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'is_occurrence', [
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );
        register_post_meta( 'event', 'occurrence_of', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => [ __CLASS__, 'can_edit_event' ],
        ] );

        // Organizer meta
        register_post_meta( 'organizer', 'organizer_website', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw'
        ] );
        register_post_meta( 'organizer', 'organizer_phone', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => [ __CLASS__, 'sanitize_phone' ]
        ] );
        register_post_meta( 'organizer', 'organizer_email', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_email'
        ] );

        // Venue meta
        register_post_meta( 'venue', 'venue_address', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field'
        ] );
        register_post_meta( 'venue', 'venue_phone', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => [ __CLASS__, 'sanitize_phone' ]
        ] );
        register_post_meta( 'venue', 'venue_email', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_email'
        ] );
        register_post_meta( 'venue', 'venue_website', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw'
        ] );
        register_post_meta( 'venue', 'venue_facebook', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw'
        ] );
        register_post_meta( 'venue', 'venue_instagram', [
            'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw'
        ] );
        register_post_meta( 'venue', 'venue_other_social', [
            'type' => 'array', 
            'single' => true, 
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'uri'
                    ]
                ]
            ], 
            'sanitize_callback' => [ __CLASS__, 'sanitize_urls_array' ]
        ] );
    }

    // Meta boxes
    public static function add_event_meta_box() {
        add_meta_box(
            'wpevents_event_times',
            __('Event Time', 'wp-events'),
            [__CLASS__, 'render_event_times_box'],
            'event',
            'side',
            'default'
        );
    }

    public static function render_event_times_box($post) {
        $start = get_post_meta($post->ID, 'event_start', true);
        $end = get_post_meta($post->ID, 'event_end', true);
        echo '<label>' . __('Start', 'wp-events') . '</label><br />';
        echo '<input type="datetime-local" name="event_start" value="' . esc_attr(self::local_datetime($start)) . '" style="width:100%" /><br />';
        echo '<label>' . __('End', 'wp-events') . '</label><br />';
        echo '<input type="datetime-local" name="event_end" value="' . esc_attr(self::local_datetime($end)) . '" style="width:100%" />';
    }

    public static function save_event_times($post_id) {
        if (isset($_POST['event_start'])) {
            update_post_meta($post_id, 'event_start', self::sanitize_iso8601($_POST['event_start']));
        }
        if (isset($_POST['event_end'])) {
            update_post_meta($post_id, 'event_end', self::sanitize_iso8601($_POST['event_end']));
        }
    }

    protected static function local_datetime($iso) {
        if (!$iso) return '';
        $ts = strtotime($iso);
        if (!$ts) return '';
        return date('Y-m-d\TH:i', $ts);
    }

    public static function add_recurrence_meta_box() {
        add_meta_box(
            'wpevents_event_recurrence',
            __('Event Recurrence', 'wp-events'),
            [__CLASS__, 'render_recurrence_box'],
            'event',
            'side',
            'default'
        );
    }

    public static function render_recurrence_box($post) {
        $type = get_post_meta($post->ID, 'recurrence_type', true);
        $interval = get_post_meta($post->ID, 'recurrence_interval', true);
        $end = get_post_meta($post->ID, 'recurrence_end', true);
        echo '<label>' . __('Type', 'wp-events') . '</label><br />';
        echo '<select name="recurrence_type" style="width:85%">';
        $types = [
            '' => __('None', 'wp-events'),
            'daily' => __('Daily', 'wp-events'),
            'weekly' => __('Weekly', 'wp-events'),
            'monthly' => __('Monthly', 'wp-events'),
            'yearly' => __('Yearly', 'wp-events'),
            'custom' => __('Custom', 'wp-events'),
        ];
        foreach ($types as $val => $label) {
            echo '<option value="' . esc_attr($val) . '"' . selected($type, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select><br />';
        echo '<label>' . __('Interval', 'wp-events') . '</label><br />';
        echo '<input type="number" name="recurrence_interval" value="' . esc_attr($interval) . '" min="1" style="width:100%" /><br />';
        echo '<label>' . __('End date', 'wp-events') . '</label><br />';
        echo '<input type="date" name="recurrence_end" value="' . esc_attr($end) . '" style="width:100%" />';
    }

    public static function save_recurrence_meta($post_id) {
        if (isset($_POST['recurrence_type'])) {
            update_post_meta($post_id, 'recurrence_type', sanitize_text_field($_POST['recurrence_type']));
        }
        if (isset($_POST['recurrence_interval'])) {
            update_post_meta($post_id, 'recurrence_interval', absint($_POST['recurrence_interval']));
        }
        if (isset($_POST['recurrence_end'])) {
            update_post_meta($post_id, 'recurrence_end', sanitize_text_field($_POST['recurrence_end']));
        }
    }

    public static function add_event_venue_organizer_box() {
        add_meta_box(
            'wpevents_event_venue_organizer',
            __('Event Venue & Organizers', 'wp-events'),
            [__CLASS__, 'render_event_venue_organizer_box'],
            'event',
            'side',
            'default'
        );
    }

    public static function render_event_venue_organizer_box($post) {
        wp_nonce_field('wpevents_venue_organizer_nonce', 'wpevents_venue_organizer_nonce');
        
        $selected_venue = (int) get_post_meta($post->ID, 'event_venue', true);
        $selected_organizers = get_post_meta($post->ID, 'event_organizer', true);
        if (!is_array($selected_organizers)) {
            $selected_organizers = [];
        }
        
        // Venue selection
        echo '<h4>' . __('Venue', 'wp-events') . '</h4>';
        $venues = get_posts([
            'post_type' => 'venue',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<select name="event_venue" style="width:85%">';
        echo '<option value="">' . __('Select a venue...', 'wp-events') . '</option>';
        foreach ($venues as $venue) {
            echo '<option value="' . esc_attr($venue->ID) . '"' . selected($selected_venue, $venue->ID, false) . '>' . esc_html($venue->post_title) . '</option>';
        }
        echo '</select><br><br>';
        
        // Organizer selection (multiple)
        echo '<h4>' . __('Organizers', 'wp-events') . '</h4>';
        $organizers = get_posts([
            'post_type' => 'organizer',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<div style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 5px;">';
        foreach ($organizers as $organizer) {
            $checked = in_array($organizer->ID, $selected_organizers) ? 'checked="checked"' : '';
            echo '<label style="display: block; margin: 5px 0;">';
            echo '<input type="checkbox" name="event_organizer[]" value="' . esc_attr($organizer->ID) . '" ' . $checked . '> ';
            echo esc_html($organizer->post_title);
            echo '</label>';
        }
        echo '</div>';
        
        if (empty($venues)) {
            echo '<p><em>' . __('No venues found. Create venues first.', 'wp-events') . '</em></p>';
        }
        if (empty($organizers)) {
            echo '<p><em>' . __('No organizers found. Create organizers first.', 'wp-events') . '</em></p>';
        }
    }

    public static function save_event_venue_organizer($post_id) {
        // Check nonce
        if (!isset($_POST['wpevents_venue_organizer_nonce']) || !wp_verify_nonce($_POST['wpevents_venue_organizer_nonce'], 'wpevents_venue_organizer_nonce')) {
            return;
        }

        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save venue
        if (isset($_POST['event_venue'])) {
            $venue_id = (int) $_POST['event_venue'];
            if ($venue_id > 0) {
                update_post_meta($post_id, 'event_venue', $venue_id);
            } else {
                delete_post_meta($post_id, 'event_venue');
            }
        }

        // Save organizers
        if (isset($_POST['event_organizer']) && is_array($_POST['event_organizer'])) {
            $organizer_ids = array_map('absint', $_POST['event_organizer']);
            $organizer_ids = array_filter($organizer_ids); // Remove empty values
            if (!empty($organizer_ids)) {
                update_post_meta($post_id, 'event_organizer', $organizer_ids);
            } else {
                delete_post_meta($post_id, 'event_organizer');
            }
        } else {
            delete_post_meta($post_id, 'event_organizer');
        }
    }

    public static function add_event_price_box() {
        add_meta_box(
            'wpevents_event_price',
            __('Event Price', 'wp-events'),
            [__CLASS__, 'render_event_price_box'],
            'event',
            'side',
            'default'
        );
    }

    public static function render_event_price_box($post) {
        wp_nonce_field('wpevents_price_nonce', 'wpevents_price_nonce');
        
        $price = get_post_meta($post->ID, 'event_price', true);
        $currency = get_post_meta($post->ID, 'event_currency', true);
        
        // Default currency
        if (empty($currency)) {
            $currency = 'DKK';
        }
        
        echo '<p>';
        echo '<label for="event_price">' . __('Price', 'wp-events') . '</label><br>';
        echo '<input type="number" id="event_price" name="event_price" value="' . esc_attr($price) . '" min="0" step="0.01" style="width:100%" placeholder="0.00" />';
        echo '</p>';
        
        echo '<p>';
        echo '<label for="event_currency">' . __('Currency', 'wp-events') . '</label><br>';
        echo '<select id="event_currency" name="event_currency" style="width:85%">';
        
        $currencies = [
            'DKK' => 'DKK - Danish Krone',
            'EUR' => 'EUR - Euro',
            'USD' => 'USD - US Dollar',
            'GBP' => 'GBP - British Pound',
            'NOK' => 'NOK - Norwegian Krone',
            'SEK' => 'SEK - Swedish Krona'
        ];
        
        foreach ($currencies as $code => $label) {
            echo '<option value="' . esc_attr($code) . '"' . selected($currency, $code, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</p>';
        
        echo '<p><small>' . __('Leave price empty if the event is free', 'wp-events') . '</small></p>';
    }

    public static function save_event_price($post_id) {
        // Check nonce
        if (!isset($_POST['wpevents_price_nonce']) || !wp_verify_nonce($_POST['wpevents_price_nonce'], 'wpevents_price_nonce')) {
            return;
        }

        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save price
        if (isset($_POST['event_price'])) {
            $price = $_POST['event_price'];
            if (is_numeric($price) && $price > 0) {
                update_post_meta($post_id, 'event_price', (float) $price);
            } else {
                delete_post_meta($post_id, 'event_price');
            }
        }

        // Save currency
        if (isset($_POST['event_currency'])) {
            $currency = strtoupper(sanitize_text_field($_POST['event_currency']));
            $allowed_currencies = ['DKK', 'EUR', 'USD', 'GBP', 'NOK', 'SEK'];
            if (in_array($currency, $allowed_currencies)) {
                update_post_meta($post_id, 'event_currency', $currency);
            }
        }
    }

    public static function add_venue_meta_box() {
        add_meta_box(
            'wpevents_venue_details',
            __('Venue Details', 'wp-events'),
            [__CLASS__, 'render_venue_box'],
            'venue',
            'normal',
            'default'
        );
    }

    public static function render_venue_box($post) {
        // Featured Image section
        echo '<div style="margin-bottom: 20px;">';
        echo '<h4>' . __('Featured Image', 'wp-events') . '</h4>';
        $featured_image = get_the_post_thumbnail($post->ID, 'medium');
        if ($featured_image) {
            echo '<div style="margin-bottom: 10px;">' . $featured_image . '</div>';
        }
        echo '<p>';
        echo '<input type="button" id="venue_featured_image_button" class="button" value="' . __('Set Featured Image', 'wp-events') . '" />';
        if ($featured_image) {
            echo ' <input type="button" id="venue_remove_image_button" class="button" value="' . __('Remove Image', 'wp-events') . '" />';
        }
        echo '</p>';
        echo '</div>';

        $fields = [
            'venue_address' => __('Street Address', 'wp-events'),
            'venue_city' => __('City', 'wp-events'),
            'venue_postal_code' => __('Postal Code', 'wp-events'),
            'venue_country' => __('Country', 'wp-events'),
            'venue_phone' => __('Phone', 'wp-events'),
            'venue_email' => __('Email', 'wp-events'),
            'venue_website' => __('Website', 'wp-events'),
            'venue_facebook' => __('Facebook URL', 'wp-events'),
            'venue_instagram' => __('Instagram URL', 'wp-events'),
        ];
        
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            $type = ($key === 'venue_email') ? 'email' : (strpos($key, 'facebook') !== false || strpos($key, 'instagram') !== false || strpos($key, 'website') !== false ? 'url' : 'text');
            echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br>';
            echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%" /></p>';
        }
        
        // Google Maps settings
        echo '<h4>' . __('Google Maps', 'wp-events') . '</h4>';
        $show_directions = get_post_meta($post->ID, 'venue_show_directions', true);
        echo '<p><label>';
        echo '<input type="checkbox" name="venue_show_directions" value="1"' . checked($show_directions, 1, false) . '>';
        echo ' ' . __('Show "Get Directions" link', 'wp-events');
        echo '</label></p>';
        
        // Custom Google Maps URL (optional override)
        $custom_maps_url = get_post_meta($post->ID, 'venue_custom_maps_url', true);
        echo '<p><label for="venue_custom_maps_url">' . __('Custom Google Maps URL (optional)', 'wp-events') . '</label><br>';
        echo '<input type="url" id="venue_custom_maps_url" name="venue_custom_maps_url" value="' . esc_attr($custom_maps_url) . '" style="width:100%" placeholder="https://maps.google.com/..." /></p>';
        echo '<small>' . __('Leave empty to auto-generate from address fields', 'wp-events') . '</small>';

        // Other social media (array)
        $other_social = get_post_meta($post->ID, 'venue_other_social', true);
        if (!is_array($other_social)) $other_social = [];
        echo '<p><label>' . __('Other Social Media URLs', 'wp-events') . '</label><br>';
        echo '<textarea name="venue_other_social" rows="3" style="width:100%" placeholder="' . esc_attr__('One URL per line', 'wp-events') . '">' . esc_textarea(implode("\n", $other_social)) . '</textarea></p>';
    }

    public static function save_venue_meta($post_id) {
        $fields = ['venue_address', 'venue_city', 'venue_postal_code', 'venue_country', 'venue_phone', 'venue_email', 'venue_website', 'venue_facebook', 'venue_instagram', 'venue_custom_maps_url'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                if (in_array($field, ['venue_email'])) {
                    $value = sanitize_email($value);
                } elseif (in_array($field, ['venue_website', 'venue_facebook', 'venue_instagram', 'venue_custom_maps_url'])) {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Handle checkbox for directions
        if (isset($_POST['venue_show_directions'])) {
            update_post_meta($post_id, 'venue_show_directions', 1);
        } else {
            delete_post_meta($post_id, 'venue_show_directions');
        }
        
        if (isset($_POST['venue_other_social'])) {
            $urls = array_filter(array_map('trim', explode("\n", $_POST['venue_other_social'])));
            $urls = array_map('esc_url_raw', $urls);
            update_post_meta($post_id, 'venue_other_social', $urls);
        }
    }

    public static function add_organizer_meta_box() {
        add_meta_box(
            'wpevents_organizer_details',
            __('Organizer Details', 'wp-events'),
            [__CLASS__, 'render_organizer_box'],
            'organizer',
            'normal',
            'default'
        );
    }

    public static function render_organizer_box($post) {
        $fields = [
            'organizer_phone' => __('Phone', 'wp-events'),
            'organizer_email' => __('Email', 'wp-events'),
            'organizer_website' => __('Website', 'wp-events'),
        ];
        
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            $type = ($key === 'organizer_email') ? 'email' : (strpos($key, 'website') !== false ? 'url' : 'text');
            echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br>';
            echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" style="width:100%" /></p>';
        }
    }

    public static function save_organizer_meta($post_id) {
        $fields = ['organizer_phone', 'organizer_email', 'organizer_website'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                if ($field === 'organizer_email') {
                    $value = sanitize_email($value);
                } elseif ($field === 'organizer_website') {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }
    }

    // Sanitize helpers
    public static function sanitize_iso8601( $value ) {
        $v = (string) $value;
        // Accept full ISO 8601 or Y-m-d H:i:s and convert to ISO8601
        $ts = strtotime( $v );
        if ( $ts === false ) return '';
        return wp_date( DATE_ATOM, $ts, wp_timezone() );
    }
    public static function sanitize_date( $value ) {
        $v = (string) $value;
        $ts = strtotime( $v );
        if ( $ts === false ) return '';
        return wp_date( 'Y-m-d', $ts, wp_timezone() );
    }
    public static function sanitize_phone( $value ) {
        $v = preg_replace( '/[^0-9+\-()\s]/', '', (string) $value );
        return trim( $v );
    }
    public static function sanitize_ids_array( $value ) {
        if ( is_string( $value ) ) {
            // Try JSON first
            $maybe = json_decode( $value, true );
            if ( is_array( $maybe ) ) $value = $maybe;
        }
        if ( ! is_array( $value ) ) return [];
        return array_values( array_filter( array_map( 'absint', $value ) ) );
    }
    public static function sanitize_urls_array( $value ) {
        if ( is_string( $value ) ) {
            $maybe = json_decode( $value, true );
            if ( is_array( $maybe ) ) $value = $maybe;
        }
        if ( ! is_array( $value ) ) return [];
        return array_values( array_filter( array_map( 'esc_url_raw', $value ) ) );
    }
    public static function sanitize_recurrence_type( $value ) {
        $allowed = [ 'daily', 'weekly', 'monthly', 'yearly', 'custom', '' ];
        $v = sanitize_text_field( (string) $value );
        return in_array( $v, $allowed, true ) ? $v : '';
    }
    public static function can_edit_event( $allowed, $meta_key, $post_id, $user_id, $cap, $caps ) {
        return current_user_can( 'edit_post', $post_id );
    }

    public static function get_venue_directions_url($venue_id) {
        // Check for custom URL first
        $custom_url = get_post_meta($venue_id, 'venue_custom_maps_url', true);
        if (!empty($custom_url)) {
            return $custom_url;
        }
        
        // Build address from components
        $address = get_post_meta($venue_id, 'venue_address', true);
        $city = get_post_meta($venue_id, 'venue_city', true);
        $postal_code = get_post_meta($venue_id, 'venue_postal_code', true);
        $country = get_post_meta($venue_id, 'venue_country', true);
        
        $address_parts = array_filter([
            $address,
            $postal_code,
            $city,
            $country
        ]);
        
        if (empty($address_parts)) {
            return false;
        }
        
        $full_address = implode(', ', $address_parts);
        $encoded_address = urlencode($full_address);
        
        return 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded_address;
    }

    public static function enqueue_admin_scripts($hook_suffix) {
        global $post_type;
        
        if (in_array($post_type, ['event', 'venue', 'organizer'])) {
            wp_enqueue_media();
            wp_enqueue_script(
                'wp-events-admin',
                WPEVENTS_PLUGIN_URL . 'assets/admin.js',
                ['jquery', 'media-upload', 'media-views'],
                WPEVENTS_VERSION,
                true
            );
            
            wp_localize_script('wp-events-admin', 'wp_events_admin', [
                'nonce' => wp_create_nonce('wp_events_admin_nonce'),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
    }

    public static function ajax_set_venue_featured_image() {
        check_ajax_referer('wp_events_admin_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $attachment_id = absint($_POST['attachment_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(json_encode(['success' => false, 'data' => 'Permission denied']));
        }
        
        $result = set_post_thumbnail($post_id, $attachment_id);
        
        if ($result) {
            wp_die(json_encode(['success' => true]));
        } else {
            wp_die(json_encode(['success' => false, 'data' => 'Failed to set featured image']));
        }
    }

    public static function ajax_remove_venue_featured_image() {
        check_ajax_referer('wp_events_admin_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(json_encode(['success' => false, 'data' => 'Permission denied']));
        }
        
        $result = delete_post_thumbnail($post_id);
        
        if ($result) {
            wp_die(json_encode(['success' => true]));
        } else {
            wp_die(json_encode(['success' => false, 'data' => 'Failed to remove featured image']));
        }
    }
}