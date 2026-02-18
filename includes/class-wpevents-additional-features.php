<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WPEvents Additional Features
 * Event status, registration, and enhancements
 */
class WPEvents_Additional_Features {
    
    /**
     * Initialize additional features
     */
    public static function init() {
        // Add event status meta box
        add_action('add_meta_boxes_event', [__CLASS__, 'add_event_status_box']);
        add_action('save_post_event', [__CLASS__, 'save_event_status']);
        
        // Add registration/RSVP functionality
        add_action('add_meta_boxes_event', [__CLASS__, 'add_registration_box']);
        add_action('save_post_event', [__CLASS__, 'save_registration_settings']);
        
        // Display registration form on event pages
        add_filter('the_content', [__CLASS__, 'add_registration_form']);
        
        // Handle registration submissions
        add_action('admin_post_event_registration', [__CLASS__, 'handle_registration']);
        add_action('admin_post_nopriv_event_registration', [__CLASS__, 'handle_registration']);
        
        // Add event badges/labels
        add_filter('the_title', [__CLASS__, 'add_event_status_badge'], 10, 2);
        
        // Add admin columns for status
        add_filter('manage_event_posts_columns', [__CLASS__, 'add_status_column']);
        add_action('manage_event_posts_custom_column', [__CLASS__, 'display_status_column'], 10, 2);
        
        // Add CSS for frontend
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_styles']);
    }
    
    /**
     * Add event status meta box
     */
    public static function add_event_status_box() {
        add_meta_box(
            'wpevents_event_status',
            __('Event Status', 'wp-events'),
            [__CLASS__, 'render_event_status_box'],
            'event',
            'side',
            'high'
        );
    }
    
    /**
     * Render event status meta box
     */
    public static function render_event_status_box($post) {
        wp_nonce_field('wpevents_event_status', 'wpevents_event_status_nonce');
        
        $event_status = get_post_meta($post->ID, 'event_status', true);
        if (!$event_status) {
            $event_status = 'scheduled';
        }
        
        $statuses = [
            'scheduled' => __('Scheduled', 'wp-events'),
            'cancelled' => __('Cancelled', 'wp-events'),
            'postponed' => __('Postponed', 'wp-events'),
            'rescheduled' => __('Rescheduled', 'wp-events'),
            'sold_out' => __('Sold Out', 'wp-events'),
            'completed' => __('Completed', 'wp-events')
        ];
        
        echo '<select name="event_status" style="width: 100%;">';
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($event_status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
        
        echo '<p><small>' . __('This status will be displayed on the event page', 'wp-events') . '</small></p>';
    }
    
    /**
     * Save event status
     */
    public static function save_event_status($post_id) {
        if (!isset($_POST['wpevents_event_status_nonce']) || 
            !wp_verify_nonce($_POST['wpevents_event_status_nonce'], 'wpevents_event_status')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['event_status'])) {
            update_post_meta($post_id, 'event_status', sanitize_text_field($_POST['event_status']));
        }
    }
    
    /**
     * Add registration settings meta box
     */
    public static function add_registration_box() {
        add_meta_box(
            'wpevents_registration',
            __('Registration Settings', 'wp-events'),
            [__CLASS__, 'render_registration_box'],
            'event',
            'normal',
            'default'
        );
    }
    
    /**
     * Render registration meta box
     */
    public static function render_registration_box($post) {
        wp_nonce_field('wpevents_registration', 'wpevents_registration_nonce');
        
        $enable_registration = get_post_meta($post->ID, 'enable_registration', true);
        $max_attendees = get_post_meta($post->ID, 'max_attendees', true);
        $registration_deadline = get_post_meta($post->ID, 'registration_deadline', true);
        $require_approval = get_post_meta($post->ID, 'require_approval', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="enable_registration"><?php _e('Enable Registration', 'wp-events'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_registration" name="enable_registration" value="1" 
                               <?php checked($enable_registration, '1'); ?>>
                        <?php _e('Allow attendees to register for this event', 'wp-events'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="max_attendees"><?php _e('Maximum Attendees', 'wp-events'); ?></label></th>
                <td>
                    <input type="number" id="max_attendees" name="max_attendees" 
                           value="<?php echo esc_attr($max_attendees); ?>" min="0" step="1" style="width: 200px;">
                    <p class="description"><?php _e('0 = unlimited', 'wp-events'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="registration_deadline"><?php _e('Registration Deadline', 'wp-events'); ?></label></th>
                <td>
                    <input type="datetime-local" id="registration_deadline" name="registration_deadline" 
                           value="<?php echo esc_attr($registration_deadline); ?>" style="width: 300px;">
                    <p class="description"><?php _e('Last date/time to register', 'wp-events'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="require_approval"><?php _e('Require Approval', 'wp-events'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" id="require_approval" name="require_approval" value="1" 
                               <?php checked($require_approval, '1'); ?>>
                        <?php _e('Registrations require admin approval', 'wp-events'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <?php
        // Display registrations list
        $registrations = self::get_registrations($post->ID);
        if (!empty($registrations)) {
            echo '<h4>' . __('Current Registrations', 'wp-events') . ' (' . count($registrations) . ')</h4>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Name', 'wp-events') . '</th>';
            echo '<th>' . __('Email', 'wp-events') . '</th>';
            echo '<th>' . __('Date', 'wp-events') . '</th>';
            echo '<th>' . __('Status', 'wp-events') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($registrations as $reg) {
                printf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    esc_html($reg['name']),
                    esc_html($reg['email']),
                    esc_html(date_i18n(get_option('date_format'), strtotime($reg['date']))),
                    esc_html(ucfirst($reg['status']))
                );
            }
            
            echo '</tbody></table>';
        }
    }
    
    /**
     * Save registration settings
     */
    public static function save_registration_settings($post_id) {
        if (!isset($_POST['wpevents_registration_nonce']) || 
            !wp_verify_nonce($_POST['wpevents_registration_nonce'], 'wpevents_registration')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $enable = isset($_POST['enable_registration']) ? '1' : '0';
        update_post_meta($post_id, 'enable_registration', $enable);
        
        if (isset($_POST['max_attendees'])) {
            update_post_meta($post_id, 'max_attendees', absint($_POST['max_attendees']));
        }
        
        if (isset($_POST['registration_deadline'])) {
            $deadline_input = sanitize_text_field($_POST['registration_deadline']);
            $deadline = '';
            if ($deadline_input !== '') {
                $timestamp = strtotime($deadline_input);
                if ($timestamp !== false) {
                    $deadline = date('Y-m-d H:i:s', $timestamp);
                }
            }
            update_post_meta($post_id, 'registration_deadline', $deadline);
        }
        
        $require_approval = isset($_POST['require_approval']) ? '1' : '0';
        update_post_meta($post_id, 'require_approval', $require_approval);
    }
    
    /**
     * Add registration form to event content
     */
    public static function add_registration_form($content) {
        if (!is_singular('event')) {
            return $content;
        }
        
        $event_id = get_the_ID();
        $enable_registration = get_post_meta($event_id, 'enable_registration', true);
        
        if ($enable_registration !== '1') {
            return $content;
        }
        
        // Check if registration is closed
        $deadline = get_post_meta($event_id, 'registration_deadline', true);
        if ($deadline && strtotime($deadline) < time()) {
            $content .= '<div class="event-registration-closed">';
            $content .= '<p><strong>' . __('Registration is closed for this event.', 'wp-events') . '</strong></p>';
            $content .= '</div>';
            return $content;
        }
        
        // Check capacity
        $max_attendees = get_post_meta($event_id, 'max_attendees', true);
        $registrations = self::get_registrations($event_id);
        $current_count = count($registrations);
        
        if ($max_attendees > 0 && $current_count >= $max_attendees) {
            $content .= '<div class="event-registration-full">';
            $content .= '<p><strong>' . __('This event is full. Registration is closed.', 'wp-events') . '</strong></p>';
            $content .= '</div>';
            return $content;
        }
        
        // Show success message if just registered
        if (isset($_GET['registered']) && $_GET['registered'] === '1') {
            $content .= '<div class="event-registration-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin: 20px 0;">';
            $content .= '<p><strong>' . __('Thank you for registering! You will receive a confirmation email.', 'wp-events') . '</strong></p>';
            $content .= '</div>';
        }
        
        // Build registration form
        $form = '<div class="event-registration-form" style="margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 5px;">';
        $form .= '<h3>' . __('Register for This Event', 'wp-events') . '</h3>';
        
        if ($max_attendees > 0) {
            $remaining = $max_attendees - $current_count;
            $form .= '<p>' . sprintf(__('%d spots remaining', 'wp-events'), $remaining) . '</p>';
        }
        
        $form .= '<form method="post" action="' . admin_url('admin-post.php') . '">';
        $form .= wp_nonce_field('event_registration', 'registration_nonce', true, false);
        $form .= '<input type="hidden" name="action" value="event_registration">';
        $form .= '<input type="hidden" name="event_id" value="' . $event_id . '">';
        
        $form .= '<p>';
        $form .= '<label for="reg_name">' . __('Your Name *', 'wp-events') . '</label><br>';
        $form .= '<input type="text" id="reg_name" name="reg_name" required style="width: 100%; padding: 8px;">';
        $form .= '</p>';
        
        $form .= '<p>';
        $form .= '<label for="reg_email">' . __('Your Email *', 'wp-events') . '</label><br>';
        $form .= '<input type="email" id="reg_email" name="reg_email" required style="width: 100%; padding: 8px;">';
        $form .= '</p>';
        
        $form .= '<p>';
        $form .= '<label for="reg_phone">' . __('Phone Number', 'wp-events') . '</label><br>';
        $form .= '<input type="tel" id="reg_phone" name="reg_phone" style="width: 100%; padding: 8px;">';
        $form .= '</p>';
        
        $form .= '<p>';
        $form .= '<label for="reg_notes">' . __('Notes/Comments', 'wp-events') . '</label><br>';
        $form .= '<textarea id="reg_notes" name="reg_notes" rows="3" style="width: 100%; padding: 8px;"></textarea>';
        $form .= '</p>';
        
        $form .= '<p>';
        $form .= '<input type="submit" value="' . esc_attr__('Register Now', 'wp-events') . '" class="button button-primary" style="padding: 12px 24px; font-size: 16px;">';
        $form .= '</p>';
        
        $form .= '</form>';
        $form .= '</div>';
        
        return $content . $form;
    }
    
    /**
     * Handle registration submission
     */
    public static function handle_registration() {
        if (!isset($_POST['registration_nonce']) || 
            !wp_verify_nonce($_POST['registration_nonce'], 'event_registration')) {
            wp_die(__('Security check failed', 'wp-events'));
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $name = isset($_POST['reg_name']) ? sanitize_text_field($_POST['reg_name']) : '';
        $email = isset($_POST['reg_email']) ? sanitize_email($_POST['reg_email']) : '';
        $phone = isset($_POST['reg_phone']) ? preg_replace('/[^0-9+\-\(\)\s]/', '', $_POST['reg_phone']) : '';
        $notes = isset($_POST['reg_notes']) ? sanitize_textarea_field($_POST['reg_notes']) : '';
        
        if (!$event_id || !$name || !$email) {
            wp_die(__('Required fields missing', 'wp-events'));
        }
        
        // Validate that the event exists and is an event post type
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'event') {
            wp_die(__('Invalid event.', 'wp-events'));
        }
        
        // Ensure registration is enabled for this event
        $enable_registration = get_post_meta($event_id, 'enable_registration', true);
        if ($enable_registration !== '1') {
            wp_die(__('Registration for this event is closed.', 'wp-events'));
        }
        
        // Check registration deadline
        $registration_deadline = get_post_meta($event_id, 'registration_deadline', true);
        if (!empty($registration_deadline)) {
            $deadline_ts = strtotime($registration_deadline);
            if ($deadline_ts && current_time('timestamp') > $deadline_ts) {
                wp_die(__('Registration for this event has ended.', 'wp-events'));
            }
        }
        
        // Check capacity again
        $max_attendees = get_post_meta($event_id, 'max_attendees', true);
        $registrations = self::get_registrations($event_id);
        
        if ($max_attendees > 0 && count($registrations) >= $max_attendees) {
            wp_die(__('Sorry, this event is now full', 'wp-events'));
        }
        
        $require_approval = get_post_meta($event_id, 'require_approval', true);
        
        // Save registration
        $registration = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes,
            'date' => current_time('mysql'),
            'status' => $require_approval === '1' ? 'pending' : 'confirmed'
        ];
        
        $registrations[] = $registration;
        update_post_meta($event_id, 'event_registrations', $registrations);
        
        // Send confirmation email
        $subject = sprintf(__('Registration Confirmation: %s', 'wp-events'), get_the_title($event_id));
        $message = sprintf(__('Thank you for registering for %s!', 'wp-events'), get_the_title($event_id));
        $message .= "\n\n" . __('Event Details:', 'wp-events') . "\n";
        $message .= __('Event:', 'wp-events') . ' ' . get_the_title($event_id) . "\n";
        
        $start = get_post_meta($event_id, 'event_start', true);
        if ($start) {
            $message .= __('Date:', 'wp-events') . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start)) . "\n";
        }
        
        $message .= __('URL:', 'wp-events') . ' ' . get_permalink($event_id) . "\n";
        
        if ($require_approval === '1') {
            $message .= "\n" . __('Your registration is pending approval. You will receive another email when approved.', 'wp-events');
        }
        
        wp_mail($email, $subject, $message);
        
        // Redirect back with success message
        wp_safe_redirect(add_query_arg('registered', '1', get_permalink($event_id)));
        exit;
    }
    
    /**
     * Get registrations for an event
     */
    protected static function get_registrations($event_id) {
        $registrations = get_post_meta($event_id, 'event_registrations', true);
        return is_array($registrations) ? $registrations : [];
    }
    
    /**
     * Add status badge to event title
     */
    public static function add_event_status_badge($title, $post_id = null) {
        if (!$post_id || get_post_type($post_id) !== 'event') {
            return $title;
        }
        
        if (!is_singular('event') && !is_post_type_archive('event') && !is_tax(['event_category', 'event_tag'])) {
            return $title;
        }
        
        $status = get_post_meta($post_id, 'event_status', true);
        
        if (!$status || $status === 'scheduled') {
            return $title;
        }
        
        $badges = [
            'cancelled' => '<span class="event-badge event-cancelled">' . __('CANCELLED', 'wp-events') . '</span>',
            'postponed' => '<span class="event-badge event-postponed">' . __('POSTPONED', 'wp-events') . '</span>',
            'rescheduled' => '<span class="event-badge event-rescheduled">' . __('RESCHEDULED', 'wp-events') . '</span>',
            'sold_out' => '<span class="event-badge event-sold-out">' . __('SOLD OUT', 'wp-events') . '</span>',
            'completed' => '<span class="event-badge event-completed">' . __('COMPLETED', 'wp-events') . '</span>'
        ];
        
        if (isset($badges[$status])) {
            $title .= ' ' . $badges[$status];
        }
        
        return $title;
    }
    
    /**
     * Add status column to admin
     */
    public static function add_status_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['event_status_col'] = __('Status', 'wp-events');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display status column
     */
    public static function display_status_column($column, $post_id) {
        if ($column === 'event_status_col') {
            $status = get_post_meta($post_id, 'event_status', true);
            if (!$status) {
                $status = 'scheduled';
            }
            
            $statuses = [
                'scheduled' => __('Scheduled', 'wp-events'),
                'cancelled' => __('Cancelled', 'wp-events'),
                'postponed' => __('Postponed', 'wp-events'),
                'rescheduled' => __('Rescheduled', 'wp-events'),
                'sold_out' => __('Sold Out', 'wp-events'),
                'completed' => __('Completed', 'wp-events')
            ];
            
            echo isset($statuses[$status]) ? esc_html($statuses[$status]) : esc_html($status);
        }
    }
    
    /**
     * Enqueue frontend styles
     */
    public static function enqueue_frontend_styles() {
        if (is_singular('event') || is_post_type_archive('event') || is_tax(['event_category', 'event_tag'])) {
            // Register a lightweight plugin-owned stylesheet handle to reliably attach inline styles
            wp_register_style('wpevents-frontend-styles', false, array(), null);
            wp_enqueue_style('wpevents-frontend-styles');
            
            wp_add_inline_style('wpevents-frontend-styles', '
                .event-badge {
                    display: inline-block;
                    padding: 2px 8px;
                    font-size: 12px;
                    font-weight: bold;
                    border-radius: 3px;
                    margin-left: 8px;
                }
                .event-cancelled {
                    background: #dc3545;
                    color: white;
                }
                .event-postponed {
                    background: #ffc107;
                    color: #000;
                }
                .event-rescheduled {
                    background: #17a2b8;
                    color: white;
                }
                .event-sold-out {
                    background: #6c757d;
                    color: white;
                }
                .event-completed {
                    background: #28a745;
                    color: white;
                }
            ');
        }
    }
}
