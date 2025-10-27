<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Only load WP_CLI commands if WP_CLI is available
if ( defined( 'WP_CLI' ) && class_exists( 'WP_CLI' ) ) {
    class WPEvents_Import_Tribe_CLI {
        public static function import_command( $args, $assoc_args ) {
            $batch = isset( $assoc_args['batch'] ) ? absint( $assoc_args['batch'] ) : 0;
            if ( ! post_type_exists( 'tribe_events' ) ) {
                /** @disregard P1009 Undefined type */
                WP_CLI::error( 'CPT tribe_events not found. Is The Events Calendar active?' );
            }
            $res = WPEvents_Import_Tribe::run_import( $batch );
            /** @disregard P1009 Undefined type */
            WP_CLI::success( sprintf( 'Imported: %d, Skipped: %d', $res['imported'], $res['skipped'] ) );
        }

        public static function reset_command( $args, $assoc_args ) {
            if ( ! post_type_exists( 'tribe_events' ) ) {
                /** @disregard P1009 Undefined type */
                WP_CLI::error( 'CPT tribe_events not found. Is The Events Calendar active?' );
            }
            
            $confirm = isset( $assoc_args['yes'] ) ? true : false;
            if ( ! $confirm ) {
                /** @disregard P1009 Undefined type */
                WP_CLI::confirm( 'This will reset all import statistics. Previously imported events will NOT be deleted, but can be re-imported. Continue?' );
            }
            
            $result = WPEvents_Import_Tribe::reset_import_stats();
            /** @disregard P1009 Undefined type */
            WP_CLI::success( sprintf( 'Reset %d import markers. Events can now be re-imported.', $result ) );
        }
    }
    /** @disregard P1009 Undefined type */
    WP_CLI::add_command( 'wpevents import-tribe', [ 'WPEvents_Import_Tribe_CLI', 'import_command' ] );
    /** @disregard P1009 Undefined type */
    WP_CLI::add_command( 'wpevents reset-import', [ 'WPEvents_Import_Tribe_CLI', 'reset_command' ] );
}

class WPEvents_Import_Tribe {
    public static function register() {
        add_action( 'admin_menu', [ __CLASS__, 'add_tools_page' ] );
        add_action( 'admin_post_wpevents_import_tribe', [ __CLASS__, 'handle_admin_import' ] );
        add_action( 'admin_post_wpevents_import_tribe_selected', [ __CLASS__, 'handle_import_selected' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'event_page_wpevents-import-tribe' !== $hook ) return;
        wp_enqueue_style( 'wpevents-import', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/wp-events.css', [], '1.0' );
        wp_enqueue_script( 'wpevents-import', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.js', [ 'jquery' ], '1.0', true );
    }

    public static function add_tools_page() {
        add_submenu_page(
            'edit.php?post_type=event',
            __( 'Import from Tribe Events', 'wp-events' ),
            __( 'Import from Tribe', 'wp-events' ),
            'manage_options',
            'wpevents-import-tribe',
            [ __CLASS__, 'render_tools_page' ]
        );
    }

    public static function render_tools_page() {
        if ( ! post_type_exists( 'tribe_events' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Events Calendar not detected (tribe_events CPT missing).', 'wp-events' ) . '</p></div>';
            return;
        }

        // Handle success message
        if ( isset( $_GET['msg'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $_GET['msg'] ) . '</p></div>';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Import from The Events Calendar', 'wp-events' ) . '</h1>';

        // Get statistics
        $stats = self::get_import_stats();
        
        echo '<div class="wpevents-import-stats" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 4px;">';
        echo '<h2>' . esc_html__( 'Import Statistics', 'wp-events' ) . '</h2>';
        echo '<ul style="list-style: none; padding: 0;">';
        echo '<li><strong>' . esc_html__( 'Total Tribe Events:', 'wp-events' ) . '</strong> ' . esc_html( $stats['total'] ) . '</li>';
        echo '<li style="color: #46b450;"><strong>' . esc_html__( 'Already Imported:', 'wp-events' ) . '</strong> ' . esc_html( $stats['imported'] ) . '</li>';
        echo '<li style="color: #00a0d2;"><strong>' . esc_html__( 'Available to Import:', 'wp-events' ) . '</strong> ' . esc_html( $stats['available'] ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Future Events:', 'wp-events' ) . '</strong> ' . esc_html( $stats['future'] ) . '</li>';
        echo '<li><strong>' . esc_html__( 'Past Events:', 'wp-events' ) . '</strong> ' . esc_html( $stats['past'] ) . '</li>';
        echo '</ul>';
        echo '</div>';

        // Tabs for filtering
        $current_filter = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="' . esc_url( add_query_arg( 'filter', 'all' ) ) . '" class="nav-tab' . ( 'all' === $current_filter ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'All Events', 'wp-events' ) . ' (' . $stats['total'] . ')</a>';
        echo '<a href="' . esc_url( add_query_arg( 'filter', 'available' ) ) . '" class="nav-tab' . ( 'available' === $current_filter ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'Available', 'wp-events' ) . ' (' . $stats['available'] . ')</a>';
        echo '<a href="' . esc_url( add_query_arg( 'filter', 'imported' ) ) . '" class="nav-tab' . ( 'imported' === $current_filter ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'Imported', 'wp-events' ) . ' (' . $stats['imported'] . ')</a>';
        echo '<a href="' . esc_url( add_query_arg( 'filter', 'future' ) ) . '" class="nav-tab' . ( 'future' === $current_filter ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'Future', 'wp-events' ) . ' (' . $stats['future'] . ')</a>';
        echo '<a href="' . esc_url( add_query_arg( 'filter', 'past' ) ) . '" class="nav-tab' . ( 'past' === $current_filter ? ' nav-tab-active' : '' ) . '">' . esc_html__( 'Past', 'wp-events' ) . ' (' . $stats['past'] . ')</a>';
        echo '</h2>';

        // Get events list
        $events = self::get_tribe_events( $current_filter );

        if ( empty( $events ) ) {
            echo '<p>' . esc_html__( 'No events found.', 'wp-events' ) . '</p>';
        } else {
            // Bulk import form
            echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="wpevents-import-form">';
            wp_nonce_field( 'wpevents_import_tribe_selected' );
            echo '<input type="hidden" name="action" value="wpevents_import_tribe_selected" />';
            
            echo '<div class="wpevents-import-table-actions">';
            echo '<button type="button" id="select-all-events" class="button">' . esc_html__( 'Select All', 'wp-events' ) . '</button> ';
            echo '<button type="button" id="deselect-all-events" class="button">' . esc_html__( 'Deselect All', 'wp-events' ) . '</button> ';
            echo '<button type="submit" class="button button-primary">' . esc_html__( 'Import Selected Events', 'wp-events' ) . '</button>';
            echo '<span style="margin-left: auto; color: #666;"><span id="selected-count">0</span> ' . esc_html__( 'selected', 'wp-events' ) . '</span>';
            echo '</div>';

            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th class="check-column"><input type="checkbox" id="select-all" /></th>';
            echo '<th>' . esc_html__( 'Event Title', 'wp-events' ) . '</th>';
            echo '<th>' . esc_html__( 'Start Date', 'wp-events' ) . '</th>';
            echo '<th>' . esc_html__( 'End Date', 'wp-events' ) . '</th>';
            echo '<th>' . esc_html__( 'Venue', 'wp-events' ) . '</th>';
            echo '<th>' . esc_html__( 'Status', 'wp-events' ) . '</th>';
            echo '<th>' . esc_html__( 'Actions', 'wp-events' ) . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ( $events as $event ) {
                $is_imported = ! empty( $event['imported_id'] );
                $row_class = $is_imported ? 'wpevents-imported' : '';
                
                echo '<tr class="' . esc_attr( $row_class ) . '">';
                echo '<td class="check-column">';
                if ( ! $is_imported ) {
                    echo '<input type="checkbox" name="event_ids[]" value="' . esc_attr( $event['id'] ) . '" class="event-checkbox" />';
                } else {
                    echo '<span style="color: #ddd;">&mdash;</span>';
                }
                echo '</td>';
                echo '<td><strong>' . esc_html( $event['title'] ) . '</strong></td>';
                echo '<td>' . esc_html( $event['start_formatted'] ) . '</td>';
                echo '<td>' . esc_html( $event['end_formatted'] ) . '</td>';
                echo '<td>' . esc_html( $event['venue'] ) . '</td>';
                echo '<td>';
                if ( $is_imported ) {
                    echo '<span class="event-status-imported">&#10003; ' . esc_html__( 'Imported', 'wp-events' ) . '</span> ';
                    echo '<a href="' . esc_url( get_edit_post_link( $event['imported_id'] ) ) . '" target="_blank">' . esc_html__( '(Edit)', 'wp-events' ) . '</a>';
                } else {
                    echo '<span class="event-status-available">' . esc_html__( 'Available', 'wp-events' ) . '</span>';
                }
                echo '</td>';
                echo '<td>';
                echo '<a href="' . esc_url( get_edit_post_link( $event['id'] ) ) . '" target="_blank" class="button button-small">' . esc_html__( 'View Original', 'wp-events' ) . '</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</form>';
        }

        // Quick import all button
        echo '<hr style="margin: 40px 0;" />';
        echo '<h2>' . esc_html__( 'Quick Import (Legacy)', 'wp-events' ) . '</h2>';
        echo '<p>' . esc_html__( 'Import all available events in batch. For large sites, prefer WP-CLI.', 'wp-events' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'wpevents_import_tribe' );
        echo '<input type="hidden" name="action" value="wpevents_import_tribe" />';
        echo '<p><label>' . esc_html__( 'Batch size', 'wp-events' ) . ' <input type="number" name="batch" value="50" min="1" max="500" /></label></p>';
        submit_button( __( 'Import All Available', 'wp-events' ), 'secondary' );
        echo '</form>';

        echo '</div>';

        // JavaScript for select all/deselect all and counting
        ?>
        <script>
        jQuery(document).ready(function($) {
            function updateCount() {
                var count = $('.event-checkbox:checked').length;
                $('#selected-count').text(count);
            }
            
            $('#select-all').on('change', function() {
                $('.event-checkbox').prop('checked', this.checked);
                updateCount();
            });
            
            $('#select-all-events').on('click', function() {
                $('.event-checkbox').prop('checked', true);
                $('#select-all').prop('checked', true);
                updateCount();
            });
            
            $('#deselect-all-events').on('click', function() {
                $('.event-checkbox').prop('checked', false);
                $('#select-all').prop('checked', false);
                updateCount();
            });
            
            $('.event-checkbox').on('change', function() {
                updateCount();
                // Update select-all checkbox state
                var allChecked = $('.event-checkbox').length === $('.event-checkbox:checked').length;
                $('#select-all').prop('checked', allChecked);
            });
            
            // Confirm before importing
            $('#wpevents-import-form').on('submit', function(e) {
                var count = $('.event-checkbox:checked').length;
                if (count === 0) {
                    alert('<?php echo esc_js( __( 'Please select at least one event to import.', 'wp-events' ) ); ?>');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm('<?php echo esc_js( __( 'Are you sure you want to import ', 'wp-events' ) ); ?>' + count + '<?php echo esc_js( __( ' event(s)?', 'wp-events' ) ); ?>')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Initialize count
            updateCount();
        });
        </script>
        <?php
    }

    protected static function get_import_stats() {
        $args = [
            'post_type' => 'tribe_events',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];
        $query = new WP_Query( $args );
        $total = $query->post_count;
        $imported = 0;
        $future = 0;
        $past = 0;
        $now = current_time( 'timestamp' );

        foreach ( $query->posts as $tribe_id ) {
            if ( get_post_meta( $tribe_id, '_wpevents_imported', true ) ) {
                $imported++;
            }
            $start = get_post_meta( $tribe_id, '_EventStartDate', true );
            if ( ! $start ) {
                $start = get_post_meta( $tribe_id, 'tribe_event_start_date', true );
            }
            if ( $start && strtotime( $start ) > $now ) {
                $future++;
            } else {
                $past++;
            }
        }

        return [
            'total' => $total,
            'imported' => $imported,
            'available' => $total - $imported,
            'future' => $future,
            'past' => $past,
        ];
    }

    protected static function get_tribe_events( $filter = 'all' ) {
        $args = [
            'post_type' => 'tribe_events',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_EventStartDate',
            'order' => 'DESC',
        ];

        $now = current_time( 'timestamp' );
        
        // Apply meta query based on filter
        if ( 'future' === $filter ) {
            $args['meta_query'] = [
                [
                    'key' => '_EventStartDate',
                    'value' => current_time( 'mysql' ),
                    'compare' => '>=',
                    'type' => 'DATETIME',
                ]
            ];
            $args['order'] = 'ASC';
        } elseif ( 'past' === $filter ) {
            $args['meta_query'] = [
                [
                    'key' => '_EventStartDate',
                    'value' => current_time( 'mysql' ),
                    'compare' => '<',
                    'type' => 'DATETIME',
                ]
            ];
        }

        $query = new WP_Query( $args );
        $events = [];

        foreach ( $query->posts as $post ) {
            $post_id = $post->ID;
            $imported_id = get_post_meta( $post_id, '_wpevents_imported', true );
            
            // Apply imported/available filter
            if ( 'imported' === $filter && ! $imported_id ) continue;
            if ( 'available' === $filter && $imported_id ) continue;

            $start = get_post_meta( $post_id, '_EventStartDate', true );
            if ( ! $start ) {
                $start = get_post_meta( $post_id, 'tribe_event_start_date', true );
            }
            $end = get_post_meta( $post_id, '_EventEndDate', true );
            if ( ! $end ) {
                $end = get_post_meta( $post_id, 'tribe_event_end_date', true );
            }

            $venue_id = get_post_meta( $post_id, '_EventVenueID', true );
            $venue_name = $venue_id ? get_the_title( $venue_id ) : '-';

            $events[] = [
                'id' => $post_id,
                'title' => get_the_title( $post_id ),
                'start' => $start,
                'start_formatted' => $start ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start ) ) : '-',
                'end' => $end,
                'end_formatted' => $end ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $end ) ) : '-',
                'venue' => $venue_name,
                'imported_id' => $imported_id,
            ];
        }

        return $events;
    }

    public static function handle_admin_import() {
        check_admin_referer( 'wpevents_import_tribe' );
        $batch = isset( $_POST['batch'] ) ? absint( $_POST['batch'] ) : 50;
        $result = self::run_import( $batch );
        $msg = sprintf( 'Imported: %d, Skipped: %d', $result['imported'], $result['skipped'] );
        wp_safe_redirect( add_query_arg( [ 'page' => 'wpevents-import-tribe', 'msg' => rawurlencode( $msg ) ], admin_url( 'edit.php?post_type=event' ) ) );
        exit;
    }

    public static function handle_import_selected() {
        check_admin_referer( 'wpevents_import_tribe_selected' );
        
        if ( ! isset( $_POST['event_ids'] ) || ! is_array( $_POST['event_ids'] ) ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'wpevents-import-tribe', 'msg' => rawurlencode( 'No events selected.' ) ], admin_url( 'edit.php?post_type=event' ) ) );
            exit;
        }

        $event_ids = array_map( 'absint', $_POST['event_ids'] );
        $imported = 0;
        $skipped = 0;

        foreach ( $event_ids as $tribe_id ) {
            // Check if already imported
            if ( get_post_meta( $tribe_id, '_wpevents_imported', true ) ) {
                $skipped++;
                continue;
            }

            $new_id = self::import_single( $tribe_id );
            if ( $new_id ) {
                $imported++;
                update_post_meta( $tribe_id, '_wpevents_imported', $new_id );
            } else {
                $skipped++;
            }
        }

        $msg = sprintf( __( 'Successfully imported %d event(s). Skipped: %d', 'wp-events' ), $imported, $skipped );
        wp_safe_redirect( add_query_arg( [ 'page' => 'wpevents-import-tribe', 'msg' => rawurlencode( $msg ) ], admin_url( 'edit.php?post_type=event' ) ) );
        exit;
    }

    /**
     * Reset all import statistics
     * Removes _wpevents_imported meta from all tribe_events posts
     * 
     * @return int Number of import markers removed
     */
    public static function reset_import_stats() {
        global $wpdb;
        
        // Delete all _wpevents_imported meta entries
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_wpevents_imported'
            )
        );
        
        return $result ? absint( $result ) : 0;
    }



    public static function run_import( $batch = 0, $filter = 'available' ) {
        $args = [
            'post_type' => 'tribe_events',
            'post_status' => 'any',
            'posts_per_page' => $batch > 0 ? $batch : -1,
            'fields' => 'ids',
            'orderby' => 'meta_value',
            'meta_key' => '_EventStartDate',
            'order' => 'ASC',
        ];

        // Only import events that haven't been imported yet
        if ( 'available' === $filter ) {
            $args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key' => '_wpevents_imported',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key' => '_wpevents_imported',
                    'value' => '',
                    'compare' => '=',
                ]
            ];
        }

        $q = new WP_Query( $args );
        $imported = 0; $skipped = 0;
        foreach ( $q->posts as $tribe_id ) {
            if ( get_post_meta( $tribe_id, '_wpevents_imported', true ) ) { $skipped++; continue; }
            $new_id = self::import_single( $tribe_id );
            if ( $new_id ) { $imported++; update_post_meta( $tribe_id, '_wpevents_imported', $new_id ); }
            else { $skipped++; }
        }
        return [ 'imported' => $imported, 'skipped' => $skipped, 'total' => $q->post_count ];
    }

    protected static function import_single( $tribe_id ) {
        $tribe_post = get_post( $tribe_id );
        $title = get_the_title( $tribe_id );
        $content = get_post_field( 'post_content', $tribe_id );
        
        // Convert classic content to Gutenberg blocks
        $content = self::convert_to_blocks( $content );
        
        // Try both meta keys for start/end dates
        $start = get_post_meta( $tribe_id, '_EventStartDate', true );
        if ( ! $start ) {
            $start = get_post_meta( $tribe_id, 'tribe_event_start_date', true );
        }
        $end = get_post_meta( $tribe_id, '_EventEndDate', true );
        if ( ! $end ) {
            $end = get_post_meta( $tribe_id, 'tribe_event_end_date', true );
        }
        
        $currency = get_post_meta( $tribe_id, '_EventCurrencySymbol', true );
        if ( ! $currency ) {
            $currency = get_post_meta( $tribe_id, 'EventCurrencySymbol', true );
        }
        
        $cost = get_post_meta( $tribe_id, '_EventCost', true );

        $postarr = [
            'post_type' => 'event',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $content,
            'post_date' => $tribe_post->post_date,
            'post_date_gmt' => $tribe_post->post_date_gmt,
            'post_modified' => $tribe_post->post_modified,
            'post_modified_gmt' => $tribe_post->post_modified_gmt,
        ];
        $event_id = wp_insert_post( $postarr );
        if ( is_wp_error( $event_id ) || ! $event_id ) return 0;

        if ( has_post_thumbnail( $tribe_id ) ) {
            set_post_thumbnail( $event_id, get_post_thumbnail_id( $tribe_id ) );
        }

        if ( $start ) update_post_meta( $event_id, 'event_start', WPEvents_CPT::sanitize_iso8601( $start ) );
        if ( $end ) update_post_meta( $event_id, 'event_end', WPEvents_CPT::sanitize_iso8601( $end ) );
        if ( $cost ) update_post_meta( $event_id, 'event_price', floatval( $cost ) );
        if ( $currency ) update_post_meta( $event_id, 'event_currency', strtoupper( $currency ) );

        // Map venue
        $venue_id = self::map_or_create_venue( $tribe_id );
        if ( $venue_id ) update_post_meta( $event_id, 'event_venue', $venue_id );

        // Map organizers (can be multiple)
        $org_ids = self::map_or_create_organizers( $tribe_id );
        if ( $org_ids ) update_post_meta( $event_id, 'event_organizer', $org_ids );

        // Import categories from tribe_events_cat to event_category
        $tribe_cats = wp_get_object_terms( $tribe_id, 'tribe_events_cat', [ 'fields' => 'names' ] );
        if ( ! is_wp_error( $tribe_cats ) && ! empty( $tribe_cats ) ) {
            wp_set_object_terms( $event_id, $tribe_cats, 'event_category', false );
        }

        // Import tags from post_tag to event_tag
        $tribe_tags = wp_get_object_terms( $tribe_id, 'post_tag', [ 'fields' => 'names' ] );
        if ( ! is_wp_error( $tribe_tags ) && ! empty( $tribe_tags ) ) {
            wp_set_object_terms( $event_id, $tribe_tags, 'event_tag', false );
        }

        // Reference back
        update_post_meta( $event_id, '_tribe_event_id', $tribe_id );

        return $event_id;
    }

    protected static function map_or_create_venue( $tribe_event_id ) {
        $venue_id = get_post_meta( $tribe_event_id, '_EventVenueID', true );
        $venue_id = absint( $venue_id );
        if ( ! $venue_id ) return 0;
        $name = get_the_title( $venue_id );
        if ( ! $name ) return 0;
        // Find existing
        $existing = get_posts( [
            'post_type' => 'venue',
            'title' => $name,
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids'
        ] );
        if ( ! empty( $existing ) ) {
            return $existing[0];
        }
        $new_id = wp_insert_post( [ 'post_type' => 'venue', 'post_status' => 'publish', 'post_title' => $name ] );
        if ( $new_id && ! is_wp_error( $new_id ) ) {
            update_post_meta( $new_id, 'venue_address', get_post_meta( $venue_id, '_VenueAddress', true ) );
            update_post_meta( $new_id, 'venue_city', get_post_meta( $venue_id, '_VenueCity', true ) );
            update_post_meta( $new_id, 'venue_postal_code', get_post_meta( $venue_id, '_VenueZip', true ) );
            update_post_meta( $new_id, 'venue_country', get_post_meta( $venue_id, '_VenueCountry', true ) );
            update_post_meta( $new_id, 'venue_phone', get_post_meta( $venue_id, '_VenuePhone', true ) );
            update_post_meta( $new_id, 'venue_website', get_post_meta( $venue_id, '_VenueURL', true ) );
            update_post_meta( $new_id, 'venue_show_directions', 1 ); // Enable directions by default
            return $new_id;
        }
        return 0;
    }

    protected static function map_or_create_organizers( $tribe_event_id ) {
        $org_id = get_post_meta( $tribe_event_id, '_EventOrganizerID', true );
        $orgs = [];
        if ( $org_id ) { $orgs[] = absint( $org_id ); }
        // Some setups may have multiple via array meta
        $multi = get_post_meta( $tribe_event_id, '_EventOrganizerIDs', true );
        if ( is_array( $multi ) ) { $orgs = array_merge( $orgs, array_map( 'absint', $multi ) ); }
        $orgs = array_filter( array_unique( $orgs ) );
        $result = [];
        foreach ( $orgs as $oid ) {
            $name = get_the_title( $oid );
            if ( ! $name ) continue;
            $existing = get_posts( [
                'post_type' => 'organizer',
                'title' => $name,
                'post_status' => 'any',
                'numberposts' => 1,
                'fields' => 'ids'
            ] );
            if ( ! empty( $existing ) ) { $result[] = $existing[0]; continue; }
            $new_id = wp_insert_post( [ 'post_type' => 'organizer', 'post_status' => 'publish', 'post_title' => $name ] );
            if ( $new_id && ! is_wp_error( $new_id ) ) {
                update_post_meta( $new_id, 'organizer_phone', get_post_meta( $oid, '_OrganizerPhone', true ) );
                update_post_meta( $new_id, 'organizer_website', get_post_meta( $oid, '_OrganizerWebsite', true ) );
                update_post_meta( $new_id, 'organizer_email', get_post_meta( $oid, '_OrganizerEmail', true ) );
                $result[] = $new_id;
            }
        }
        return $result;
    }

    /**
     * Convert classic editor content to Gutenberg blocks
     * 
     * @param string $content Classic editor content (HTML)
     * @return string Gutenberg block content
     */
    protected static function convert_to_blocks( $content ) {
        if ( empty( $content ) ) {
            return '';
        }

        // Check if content already has blocks
        if ( has_blocks( $content ) ) {
            return $content;
        }

        // Split content into paragraphs and other elements
        $blocks = [];
        
        // Use WordPress core function to convert classic content to blocks
        // This handles paragraphs, headings, lists, images, etc.
        $converted = wp_filter_content_tags( $content );
        
        // Parse HTML and convert to blocks
        $dom = new DOMDocument();
        @$dom->loadHTML( '<?xml encoding="UTF-8">' . $converted, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        
        $body = $dom->getElementsByTagName('body')->item(0);
        if ( ! $body ) {
            // If parsing fails, wrap in paragraph blocks
            return self::wrap_in_paragraph_blocks( $content );
        }
        
        foreach ( $body->childNodes as $node ) {
            $block = self::convert_node_to_block( $node );
            if ( $block ) {
                $blocks[] = $block;
            }
        }
        
        return implode( "\n\n", $blocks );
    }

    /**
     * Convert a DOM node to a Gutenberg block
     */
    protected static function convert_node_to_block( $node ) {
        if ( $node->nodeType === XML_TEXT_NODE ) {
            $text = trim( $node->textContent );
            if ( empty( $text ) ) {
                return '';
            }
            return '<!-- wp:paragraph -->' . "\n" . '<p>' . esc_html( $text ) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
        }
        
        if ( $node->nodeType !== XML_ELEMENT_NODE ) {
            return '';
        }
        
        $tag = strtolower( $node->nodeName );
        $html = $node->ownerDocument->saveHTML( $node );
        
        // Handle different HTML elements
        switch ( $tag ) {
            case 'p':
                return '<!-- wp:paragraph -->' . "\n" . $html . "\n" . '<!-- /wp:paragraph -->';
            
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = intval( substr( $tag, 1 ) );
                return '<!-- wp:heading {"level":' . $level . '} -->' . "\n" . $html . "\n" . '<!-- /wp:heading -->';
            
            case 'ul':
                return '<!-- wp:list -->' . "\n" . $html . "\n" . '<!-- /wp:list -->';
            
            case 'ol':
                return '<!-- wp:list {"ordered":true} -->' . "\n" . $html . "\n" . '<!-- /wp:list -->';
            
            case 'blockquote':
                return '<!-- wp:quote -->' . "\n" . $html . "\n" . '<!-- /wp:quote -->';
            
            case 'img':
                $src = $node->getAttribute('src');
                $alt = $node->getAttribute('alt');
                $id = $node->getAttribute('data-id') ?: '';
                return '<!-- wp:image ' . ( $id ? '{"id":' . intval( $id ) . '}' : '' ) . ' -->' . "\n" . 
                       '<figure class="wp-block-image">' . $html . '</figure>' . "\n" . 
                       '<!-- /wp:image -->';
            
            case 'figure':
                if ( $node->getElementsByTagName('img')->length > 0 ) {
                    return '<!-- wp:image -->' . "\n" . $html . "\n" . '<!-- /wp:image -->';
                }
                return '<!-- wp:paragraph -->' . "\n" . '<p>' . $html . '</p>' . "\n" . '<!-- /wp:paragraph -->';
            
            case 'pre':
            case 'code':
                return '<!-- wp:code -->' . "\n" . '<pre class="wp-block-code"><code>' . esc_html( $node->textContent ) . '</code></pre>' . "\n" . '<!-- /wp:code -->';
            
            case 'table':
                return '<!-- wp:table -->' . "\n" . '<figure class="wp-block-table">' . $html . '</figure>' . "\n" . '<!-- /wp:table -->';
            
            default:
                // For other elements, wrap in paragraph
                return '<!-- wp:paragraph -->' . "\n" . '<p>' . $html . '</p>' . "\n" . '<!-- /wp:paragraph -->';
        }
    }

    /**
     * Fallback: wrap content in paragraph blocks
     */
    protected static function wrap_in_paragraph_blocks( $content ) {
        // Split by double line breaks
        $paragraphs = preg_split( '/\n\s*\n/', $content );
        $blocks = [];
        
        foreach ( $paragraphs as $para ) {
            $para = trim( $para );
            if ( empty( $para ) ) {
                continue;
            }
            
            // Check if it's a heading
            if ( preg_match( '/^<h([1-6])[^>]*>(.*?)<\/h\1>$/is', $para, $matches ) ) {
                $level = $matches[1];
                $blocks[] = '<!-- wp:heading {"level":' . $level . '} -->' . "\n" . $para . "\n" . '<!-- /wp:heading -->';
            } else {
                // Wrap in paragraph if not already
                if ( ! preg_match( '/^<p[^>]*>/', $para ) ) {
                    $para = '<p>' . $para . '</p>';
                }
                $blocks[] = '<!-- wp:paragraph -->' . "\n" . $para . "\n" . '<!-- /wp:paragraph -->';
            }
        }
        
        return implode( "\n\n", $blocks );
    }
}

// Bootstrap
add_action( 'init', [ 'WPEvents_Import_Tribe', 'register' ] );
