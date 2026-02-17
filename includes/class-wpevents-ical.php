<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WPEvents iCal Export
 * Handles iCalendar (.ics) file generation for events
 */
class WPEvents_iCal {
    
    /**
     * Initialize iCal export functionality
     */
    public static function init() {
        // Add download query var
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        
        // Handle iCal download requests
        add_action('template_redirect', [__CLASS__, 'handle_ical_download']);
        
        // Add iCal link to event content
        add_filter('the_content', [__CLASS__, 'add_ical_button']);
        
        // Register REST endpoint for feed
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }
    
    /**
     * Add custom query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'ical_download';
        $vars[] = 'event_id';
        return $vars;
    }
    
    /**
     * Handle iCal download requests
     */
    public static function handle_ical_download() {
        if (get_query_var('ical_download') !== '1') {
            return;
        }
        
        $event_id = get_query_var('event_id');
        if (!$event_id || get_post_type($event_id) !== 'event') {
            return;
        }
        
        self::send_ical_file($event_id);
        exit;
    }
    
    /**
     * Generate and send iCal file for an event
     */
    public static function send_ical_file($event_id) {
        $ical_content = self::generate_ical($event_id);
        
        if (!$ical_content) {
            wp_die('Event not found');
        }
        
        $filename = sanitize_title(get_the_title($event_id)) . '.ics';
        
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo $ical_content;
    }
    
    /**
     * Generate iCal content for an event
     */
    public static function generate_ical($event_id) {
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event') {
            return false;
        }
        
        $start = get_post_meta($event_id, 'event_start', true);
        $end = get_post_meta($event_id, 'event_end', true);
        $venue_id = get_post_meta($event_id, 'event_venue', true);
        
        if (!$start) {
            return false;
        }
        
        // Format dates for iCal (YYYYMMDDTHHMMSSZ)
        $dtstart = self::format_ical_date($start);
        $dtend = $end ? self::format_ical_date($end) : $dtstart;
        
        // Get venue information
        $location = '';
        if ($venue_id) {
            $venue_name = get_the_title($venue_id);
            $venue_address = get_post_meta($venue_id, 'venue_address', true);
            $venue_city = get_post_meta($venue_id, 'venue_city', true);
            $venue_country = get_post_meta($venue_id, 'venue_country', true);
            
            $location_parts = array_filter([$venue_name, $venue_address, $venue_city, $venue_country]);
            $location = implode(', ', $location_parts);
        }
        
        // Generate unique ID
        $uid = $event_id . '@' . parse_url(get_site_url(), PHP_URL_HOST);
        
        // Get event URL
        $url = get_permalink($event_id);
        
        // Get description
        $description = $event->post_excerpt ?: wp_strip_all_tags($event->post_content);
        $description = self::escape_ical_text($description);
        
        // Get organizer info
        $organizer_ids = get_post_meta($event_id, 'event_organizer', true);
        $organizer_line = '';
        if ($organizer_ids && is_array($organizer_ids) && !empty($organizer_ids)) {
            $organizer_id = $organizer_ids[0];
            $organizer_name = get_the_title($organizer_id);
            $organizer_email = get_post_meta($organizer_id, 'organizer_email', true);
            
            if ($organizer_email) {
                $organizer_line = "ORGANIZER;CN=" . self::escape_ical_text($organizer_name) . ":mailto:" . $organizer_email . "\r\n";
            }
        }
        
        // Build iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//WP Events//NONSGML v1.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $uid . "\r\n";
        $ical .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $dtstart . "\r\n";
        $ical .= "DTEND:" . $dtend . "\r\n";
        $ical .= "SUMMARY:" . self::escape_ical_text(get_the_title($event_id)) . "\r\n";
        
        if ($description) {
            $ical .= "DESCRIPTION:" . $description . "\r\n";
        }
        
        if ($location) {
            $ical .= "LOCATION:" . self::escape_ical_text($location) . "\r\n";
        }
        
        if ($url) {
            $ical .= "URL:" . $url . "\r\n";
        }
        
        if ($organizer_line) {
            $ical .= $organizer_line;
        }
        
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";
        
        return $ical;
    }
    
    /**
     * Format date for iCal format
     */
    protected static function format_ical_date($date_string) {
        $timestamp = strtotime($date_string);
        return gmdate('Ymd\THis\Z', $timestamp);
    }
    
    /**
     * Escape text for iCal format
     */
    protected static function escape_ical_text($text) {
        $text = str_replace(["\r\n", "\n", "\r"], ' ', $text);
        $text = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);
        return substr($text, 0, 1000); // Limit length
    }
    
    /**
     * Add "Add to Calendar" button to event content
     */
    public static function add_ical_button($content) {
        if (!is_singular('event')) {
            return $content;
        }
        
        $event_id = get_the_ID();
        $ical_url = add_query_arg([
            'ical_download' => '1',
            'event_id' => $event_id
        ], get_permalink($event_id));
        
        $button = '<div class="wp-events-ical-button" style="margin: 20px 0;">';
        $button .= '<a href="' . esc_url($ical_url) . '" class="button wp-events-add-to-calendar" style="display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px;">';
        $button .= '<span class="dashicons dashicons-calendar-alt" style="vertical-align: middle; margin-right: 5px;"></span>';
        $button .= __('Add to Calendar', 'wp-events');
        $button .= '</a>';
        $button .= '</div>';
        
        return $content . $button;
    }
    
    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        register_rest_route('wp-events/v1', '/ical/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'rest_get_ical'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Feed endpoint for all upcoming events
        register_rest_route('wp-events/v1', '/ical/feed', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'rest_get_feed'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * REST endpoint for single event iCal
     */
    public static function rest_get_ical($request) {
        $event_id = $request['id'];
        $ical = self::generate_ical($event_id);
        
        if (!$ical) {
            return new WP_Error('no_event', 'Event not found', ['status' => 404]);
        }
        
        return new WP_REST_Response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8'
        ]);
    }
    
    /**
     * REST endpoint for event feed
     */
    public static function rest_get_feed($request) {
        $args = [
            'post_type' => 'event',
            'posts_per_page' => 50,
            'meta_key' => 'event_start',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'event_start',
                    'value' => current_time('mysql'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                ]
            ]
        ];
        
        $events = get_posts($args);
        
        if (empty($events)) {
            return new WP_Error('no_events', 'No upcoming events found', ['status' => 404]);
        }
        
        // Build combined iCal with multiple events
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//WP Events//NONSGML v1.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:" . get_bloginfo('name') . " Events\r\n";
        $ical .= "X-WR-TIMEZONE:UTC\r\n";
        
        foreach ($events as $event) {
            $single_ical = self::generate_ical($event->ID);
            if ($single_ical) {
                // Extract just the VEVENT portion
                preg_match('/BEGIN:VEVENT.*?END:VEVENT/s', $single_ical, $matches);
                if (!empty($matches[0])) {
                    $ical .= $matches[0] . "\r\n";
                }
            }
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        return new WP_REST_Response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8'
        ]);
    }
}
