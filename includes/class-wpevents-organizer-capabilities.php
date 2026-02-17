<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WPEvents Organizer Capabilities
 * Handles organizer role and event management permissions
 */
class WPEvents_Organizer_Capabilities {
    
    /**
     * Initialize organizer capabilities
     */
    public static function init() {
        // Add organizer role on plugin activation
        add_action('init', [__CLASS__, 'add_organizer_role']);
        
        // Filter event editing capabilities
        add_filter('map_meta_cap', [__CLASS__, 'map_event_capabilities'], 10, 4);
        
        // Add organizer assignment meta box
        add_action('add_meta_boxes_event', [__CLASS__, 'add_organizer_assignment_box']);
        add_action('save_post_event', [__CLASS__, 'save_organizer_assignment']);
        
        // Add organizer dashboard shortcode
        add_shortcode('organizer_dashboard', [__CLASS__, 'render_organizer_dashboard']);
        add_shortcode('event_submission_form', [__CLASS__, 'render_submission_form']);
        
        // Handle frontend event submission
        add_action('admin_post_submit_event', [__CLASS__, 'handle_event_submission']);
        add_action('admin_post_nopriv_submit_event', [__CLASS__, 'handle_event_submission']);
        
        // Restrict admin event list for organizers
        add_filter('pre_get_posts', [__CLASS__, 'filter_events_for_organizers']);
        
        // Add organizer profile link
        add_action('show_user_profile', [__CLASS__, 'show_organizer_profile_fields']);
        add_action('edit_user_profile', [__CLASS__, 'show_organizer_profile_fields']);
        add_action('personal_options_update', [__CLASS__, 'save_organizer_profile_fields']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_organizer_profile_fields']);
    }
    
    /**
     * Add event organizer role
     */
    public static function add_organizer_role() {
        if (get_role('event_organizer')) {
            return; // Role already exists
        }
        
        add_role('event_organizer', __('Event Organizer', 'wp-events'), [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            // Custom capabilities for events
            'edit_events' => true,
            'edit_published_events' => true,
            'publish_events' => true,
            'delete_events' => true,
            'delete_published_events' => true,
        ]);
        
        // Add capabilities to administrator and editor
        $admin = get_role('administrator');
        $editor = get_role('editor');
        
        $caps = [
            'edit_events',
            'edit_others_events',
            'edit_published_events',
            'publish_events',
            'delete_events',
            'delete_others_events',
            'delete_published_events',
            'read_private_events',
            'edit_private_events',
            'delete_private_events'
        ];
        
        foreach ($caps as $cap) {
            if ($admin) {
                $admin->add_cap($cap);
            }
            if ($editor) {
                $editor->add_cap($cap);
            }
        }
    }
    
    /**
     * Map event capabilities based on organizer assignment
     */
    public static function map_event_capabilities($caps, $cap, $user_id, $args) {
        // Only handle event capabilities
        if (!in_array($cap, ['edit_event', 'delete_event', 'publish_event'])) {
            return $caps;
        }
        
        // If no specific event, use default capabilities
        if (empty($args[0])) {
            return $caps;
        }
        
        $post_id = $args[0];
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'event') {
            return $caps;
        }
        
        // Admins and editors can edit all events
        $user = get_userdata($user_id);
        if ($user && (in_array('administrator', $user->roles) || in_array('editor', $user->roles))) {
            return ['edit_events'];
        }
        
        // Check if user is assigned as organizer for this event
        $assigned_organizers = get_post_meta($post_id, 'assigned_organizer_users', true);
        
        if (is_array($assigned_organizers) && in_array($user_id, $assigned_organizers)) {
            // User is assigned organizer - allow editing
            return ['edit_events'];
        }
        
        // Check if user created the event
        if ($post->post_author == $user_id) {
            return ['edit_events'];
        }
        
        // Default deny
        return $caps;
    }
    
    /**
     * Add organizer assignment meta box
     */
    public static function add_organizer_assignment_box() {
        add_meta_box(
            'wpevents_organizer_assignment',
            __('Assigned Organizers (Users)', 'wp-events'),
            [__CLASS__, 'render_organizer_assignment_box'],
            'event',
            'side',
            'high'
        );
    }
    
    /**
     * Render organizer assignment meta box
     */
    public static function render_organizer_assignment_box($post) {
        wp_nonce_field('wpevents_organizer_assignment', 'wpevents_organizer_assignment_nonce');
        
        $assigned_organizers = get_post_meta($post->ID, 'assigned_organizer_users', true);
        if (!is_array($assigned_organizers)) {
            $assigned_organizers = [];
        }
        
        // Get users with event_organizer role
        $organizers = get_users([
            'role__in' => ['event_organizer', 'administrator', 'editor']
        ]);
        
        echo '<p>' . __('Select users who can manage this event:', 'wp-events') . '</p>';
        
        foreach ($organizers as $organizer) {
            $checked = in_array($organizer->ID, $assigned_organizers) ? 'checked' : '';
            printf(
                '<label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="assigned_organizers[]" value="%d" %s> %s (%s)
                </label>',
                $organizer->ID,
                $checked,
                esc_html($organizer->display_name),
                esc_html($organizer->user_email)
            );
        }
        
        if (empty($organizers)) {
            echo '<p><em>' . __('No organizers found. Create users with Event Organizer role.', 'wp-events') . '</em></p>';
        }
    }
    
    /**
     * Save organizer assignment
     */
    public static function save_organizer_assignment($post_id) {
        if (!isset($_POST['wpevents_organizer_assignment_nonce']) || 
            !wp_verify_nonce($_POST['wpevents_organizer_assignment_nonce'], 'wpevents_organizer_assignment')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $assigned_organizers = isset($_POST['assigned_organizers']) ? array_map('absint', $_POST['assigned_organizers']) : [];
        update_post_meta($post_id, 'assigned_organizer_users', $assigned_organizers);
    }
    
    /**
     * Filter events in admin for organizers
     */
    public static function filter_events_for_organizers($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        global $pagenow;
        if ($pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'event') {
            return;
        }
        
        $user = wp_get_current_user();
        
        // Admins and editors see all events
        if (in_array('administrator', $user->roles) || in_array('editor', $user->roles)) {
            return;
        }
        
        // Event organizers only see their assigned events
        if (in_array('event_organizer', $user->roles)) {
            $query->set('meta_query', [
                'relation' => 'OR',
                [
                    'key' => 'assigned_organizer_users',
                    'value' => sprintf(':"%d";', $user->ID),
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'assigned_organizer_users',
                    'value' => sprintf('i:%d;', $user->ID),
                    'compare' => 'LIKE'
                ]
            ]);
        }
    }
    
    /**
     * Render organizer dashboard shortcode
     */
    public static function render_organizer_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'wp-events') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        // Get user's events
        $args = [
            'post_type' => 'event',
            'author' => $user_id,
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => 'event_start',
            'order' => 'ASC'
        ];
        
        // Also get events where user is assigned
        $meta_query = [
            'relation' => 'OR',
            [
                'key' => 'assigned_organizer_users',
                'value' => sprintf(':"%d";', $user_id),
                'compare' => 'LIKE'
            ],
            [
                'key' => 'assigned_organizer_users',
                'value' => sprintf('i:%d;', $user_id),
                'compare' => 'LIKE'
            ]
        ];
        
        $assigned_events = get_posts([
            'post_type' => 'event',
            'posts_per_page' => -1,
            'meta_query' => $meta_query
        ]);
        
        $my_events = get_posts($args);
        $all_events = array_unique(array_merge($my_events, $assigned_events), SORT_REGULAR);
        
        ob_start();
        ?>
        <div class="organizer-dashboard">
            <h2><?php _e('Your Events Dashboard', 'wp-events'); ?></h2>
            
            <p>
                <a href="<?php echo admin_url('post-new.php?post_type=event'); ?>" class="button button-primary">
                    <?php _e('Add New Event', 'wp-events'); ?>
                </a>
            </p>
            
            <?php if (empty($all_events)): ?>
                <p><?php _e('You have no events yet.', 'wp-events'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th><?php _e('Event', 'wp-events'); ?></th>
                            <th><?php _e('Date', 'wp-events'); ?></th>
                            <th><?php _e('Status', 'wp-events'); ?></th>
                            <th><?php _e('Actions', 'wp-events'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_events as $event): ?>
                            <?php 
                            $start = get_post_meta($event->ID, 'event_start', true);
                            $edit_url = admin_url('post.php?post=' . $event->ID . '&action=edit');
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($event->post_title); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($start) {
                                        echo date_i18n(get_option('date_format'), strtotime($start));
                                    }
                                    ?>
                                </td>
                                <td><?php echo ucfirst($event->post_status); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php _e('Edit', 'wp-events'); ?></a> |
                                    <a href="<?php echo get_permalink($event->ID); ?>" target="_blank"><?php _e('View', 'wp-events'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render event submission form shortcode
     */
    public static function render_submission_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to submit an event.', 'wp-events') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="event-submission-form">
            <h2><?php _e('Submit New Event', 'wp-events'); ?></h2>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('submit_event', 'event_submission_nonce'); ?>
                <input type="hidden" name="action" value="submit_event">
                
                <p>
                    <label for="event_title"><?php _e('Event Title *', 'wp-events'); ?></label>
                    <input type="text" id="event_title" name="event_title" required style="width: 100%;">
                </p>
                
                <p>
                    <label for="event_content"><?php _e('Event Description *', 'wp-events'); ?></label>
                    <textarea id="event_content" name="event_content" rows="8" required style="width: 100%;"></textarea>
                </p>
                
                <p>
                    <label for="event_start"><?php _e('Start Date & Time *', 'wp-events'); ?></label>
                    <input type="datetime-local" id="event_start" name="event_start" required style="width: 100%;">
                </p>
                
                <p>
                    <label for="event_end"><?php _e('End Date & Time', 'wp-events'); ?></label>
                    <input type="datetime-local" id="event_end" name="event_end" style="width: 100%;">
                </p>
                
                <p>
                    <label for="event_price"><?php _e('Ticket Price', 'wp-events'); ?></label>
                    <input type="number" id="event_price" name="event_price" step="0.01" min="0" style="width: 100%;">
                </p>
                
                <p>
                    <input type="submit" value="<?php esc_attr_e('Submit Event', 'wp-events'); ?>" class="button button-primary">
                </p>
                
                <p><small><?php _e('Your event will be reviewed before being published.', 'wp-events'); ?></small></p>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle frontend event submission
     */
    public static function handle_event_submission() {
        if (!isset($_POST['event_submission_nonce']) || 
            !wp_verify_nonce($_POST['event_submission_nonce'], 'submit_event')) {
            wp_die(__('Security check failed', 'wp-events'));
        }
        
        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to submit events', 'wp-events'));
        }
        
        $user_id = get_current_user_id();
        
        // Create event post
        $event_data = [
            'post_title' => sanitize_text_field($_POST['event_title']),
            'post_content' => wp_kses_post($_POST['event_content']),
            'post_type' => 'event',
            'post_status' => 'pending', // Require review
            'post_author' => $user_id
        ];
        
        $event_id = wp_insert_post($event_data);
        
        if (is_wp_error($event_id)) {
            wp_die(__('Failed to create event', 'wp-events'));
        }
        
        // Save event meta
        if (!empty($_POST['event_start'])) {
            $start = sanitize_text_field($_POST['event_start']);
            update_post_meta($event_id, 'event_start', date('Y-m-d H:i:s', strtotime($start)));
        }
        
        if (!empty($_POST['event_end'])) {
            $end = sanitize_text_field($_POST['event_end']);
            update_post_meta($event_id, 'event_end', date('Y-m-d H:i:s', strtotime($end)));
        }
        
        if (!empty($_POST['event_price'])) {
            update_post_meta($event_id, 'event_price', floatval($_POST['event_price']));
        }
        
        // Assign user as organizer
        update_post_meta($event_id, 'assigned_organizer_users', [$user_id]);
        
        // Redirect to success page or back to form
        wp_redirect(add_query_arg('event_submitted', '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Show organizer profile fields
     */
    public static function show_organizer_profile_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        $organizer_post_id = get_user_meta($user->ID, 'organizer_post_id', true);
        
        ?>
        <h3><?php _e('Event Organizer Settings', 'wp-events'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="organizer_post_id"><?php _e('Link to Organizer Post', 'wp-events'); ?></label></th>
                <td>
                    <select name="organizer_post_id" id="organizer_post_id">
                        <option value=""><?php _e('-- None --', 'wp-events'); ?></option>
                        <?php
                        $organizers = get_posts([
                            'post_type' => 'organizer',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);
                        
                        foreach ($organizers as $org) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                $org->ID,
                                selected($organizer_post_id, $org->ID, false),
                                esc_html($org->post_title)
                            );
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Link this user account to an organizer post', 'wp-events'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save organizer profile fields
     */
    public static function save_organizer_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['organizer_post_id'])) {
            update_user_meta($user_id, 'organizer_post_id', absint($_POST['organizer_post_id']));
        }
    }
}
