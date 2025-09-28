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
    }
    /** @disregard P1009 Undefined type */
    WP_CLI::add_command( 'wpevents import-tribe', [ 'WPEvents_Import_Tribe_CLI', 'import_command' ] );
}

class WPEvents_Import_Tribe {
    public static function register() {
        add_action( 'admin_menu', [ __CLASS__, 'add_tools_page' ] );
        add_action( 'admin_post_wpevents_import_tribe', [ __CLASS__, 'handle_admin_import' ] );
    }

    public static function add_tools_page() {
        add_management_page( __( 'WP Events: Import Tribe', 'wp-events' ), __( 'WP Events Import', 'wp-events' ), 'manage_options', 'wpevents-import-tribe', [ __CLASS__, 'render_tools_page' ] );
    }

    public static function render_tools_page() {
        if ( ! post_type_exists( 'tribe_events' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'The Events Calendar not detected (tribe_events CPT missing).', 'wp-events' ) . '</p></div>';
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'Import from The Events Calendar', 'wp-events' ) . '</h1>';
        echo '<p>' . esc_html__( 'Click the button below to import events into WP Events. For large sites, prefer WP-CLI.', 'wp-events' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'wpevents_import_tribe' );
        echo '<input type="hidden" name="action" value="wpevents_import_tribe" />';
        echo '<p><label>' . esc_html__( 'Batch size', 'wp-events' ) . ' <input type="number" name="batch" value="50" min="1" max="500" /></label></p>';
        submit_button( __( 'Run Import', 'wp-events' ) );
        echo '</form></div>';
    }

    public static function handle_admin_import() {
        check_admin_referer( 'wpevents_import_tribe' );
        $batch = isset( $_POST['batch'] ) ? absint( $_POST['batch'] ) : 50;
        $result = self::run_import( $batch );
        $msg = sprintf( 'Imported: %d, Skipped: %d', $result['imported'], $result['skipped'] );
        wp_safe_redirect( add_query_arg( [ 'page' => 'wpevents-import-tribe', 'msg' => rawurlencode( $msg ) ], admin_url( 'tools.php' ) ) );
        exit;
    }



    public static function run_import( $batch = 0 ) {
        $args = [
            'post_type' => 'tribe_events',
            'post_status' => 'any',
            'posts_per_page' => $batch > 0 ? $batch : -1,
            'fields' => 'ids',
        ];
        $q = new WP_Query( $args );
        $imported = 0; $skipped = 0;
        foreach ( $q->posts as $tribe_id ) {
            if ( get_post_meta( $tribe_id, '_wpevents_imported', true ) ) { $skipped++; continue; }
            $new_id = self::import_single( $tribe_id );
            if ( $new_id ) { $imported++; update_post_meta( $tribe_id, '_wpevents_imported', $new_id ); }
            else { $skipped++; }
        }
        return [ 'imported' => $imported, 'skipped' => $skipped ];
    }

    protected static function import_single( $tribe_id ) {
        $title = get_the_title( $tribe_id );
        $content = get_post_field( 'post_content', $tribe_id );
        $start = get_post_meta( $tribe_id, 'tribe_event_start_date', true );
        $end   = get_post_meta( $tribe_id, 'tribe_event_end_date', true );
        $currency = get_post_meta( $tribe_id, 'EventCurrencySymbol', true );

        $postarr = [
            'post_type' => 'event',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $content,
        ];
        $event_id = wp_insert_post( $postarr );
        if ( is_wp_error( $event_id ) || ! $event_id ) return 0;

        if ( has_post_thumbnail( $tribe_id ) ) {
            set_post_thumbnail( $event_id, get_post_thumbnail_id( $tribe_id ) );
        }

        if ( $start ) update_post_meta( $event_id, 'event_start', WPEvents_CPT::sanitize_iso8601( $start ) );
        if ( $end ) update_post_meta( $event_id, 'event_end', WPEvents_CPT::sanitize_iso8601( $end ) );
        if ( $currency ) update_post_meta( $event_id, 'event_currency', strtoupper( $currency ) );

        // Map venue
        $venue_id = self::map_or_create_venue( $tribe_id );
        if ( $venue_id ) update_post_meta( $event_id, 'event_venue', $venue_id );

        // Map organizers (can be multiple)
        $org_ids = self::map_or_create_organizers( $tribe_id );
        if ( $org_ids ) update_post_meta( $event_id, 'event_organizer', $org_ids );

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
            update_post_meta( $new_id, 'venue_phone', get_post_meta( $venue_id, '_VenuePhone', true ) );
            update_post_meta( $new_id, 'venue_website', get_post_meta( $venue_id, '_VenueURL', true ) );
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
}

// Bootstrap
add_action( 'init', [ 'WPEvents_Import_Tribe', 'register' ] );
