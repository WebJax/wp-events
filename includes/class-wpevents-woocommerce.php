<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WPEvents WooCommerce Integration
 * Handles ticket sales through WooCommerce
 */
class WPEvents_WooCommerce {
    
    /**
     * Initialize WooCommerce integration
     */
    public static function init() {
        // Only load if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Add meta box for ticket settings
        add_action('add_meta_boxes_event', [__CLASS__, 'add_ticket_meta_box']);
        add_action('save_post_event', [__CLASS__, 'save_ticket_meta']);
        
        // Display ticket purchase button on event pages
        add_filter('the_content', [__CLASS__, 'add_ticket_button']);
        
        // Add event info to order
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'add_event_to_order_item'], 10, 4);
        
        // Display event info in order details
        add_filter('woocommerce_order_item_meta_end', [__CLASS__, 'display_event_in_order'], 10, 4);
        
        // Add attendee fields to checkout
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_attendee_fields']);
        add_action('woocommerce_checkout_update_order_meta', [__CLASS__, 'save_attendee_data']);
        
        // Add event ticket product type
        add_filter('product_type_selector', [__CLASS__, 'add_event_ticket_product_type']);
        
        // Sync event capacity with product stock
        add_action('save_post_event', [__CLASS__, 'sync_ticket_stock'], 20);
    }
    
    /**
     * Add ticket settings meta box
     */
    public static function add_ticket_meta_box() {
        add_meta_box(
            'wpevents_ticket_settings',
            __('Ticket Settings (WooCommerce)', 'wp-events'),
            [__CLASS__, 'render_ticket_meta_box'],
            'event',
            'side',
            'default'
        );
    }
    
    /**
     * Render ticket settings meta box
     */
    public static function render_ticket_meta_box($post) {
        wp_nonce_field('wpevents_ticket_meta', 'wpevents_ticket_nonce');
        
        $enable_tickets = get_post_meta($post->ID, 'enable_tickets', true);
        $ticket_product_id = get_post_meta($post->ID, 'ticket_product_id', true);
        $ticket_capacity = get_post_meta($post->ID, 'ticket_capacity', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="enable_tickets" value="1" <?php checked($enable_tickets, '1'); ?>>
                <?php _e('Enable ticket sales', 'wp-events'); ?>
            </label>
        </p>
        
        <p>
            <label><?php _e('Ticket Product:', 'wp-events'); ?></label>
            <select name="ticket_product_id" style="width: 100%;">
                <option value=""><?php _e('-- Select Product --', 'wp-events'); ?></option>
                <?php
                $products = get_posts([
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ]);
                
                foreach ($products as $product) {
                    printf(
                        '<option value="%d" %s>%s</option>',
                        $product->ID,
                        selected($ticket_product_id, $product->ID, false),
                        esc_html($product->post_title)
                    );
                }
                ?>
            </select>
            <small><?php _e('Select the WooCommerce product to use for tickets', 'wp-events'); ?></small>
        </p>
        
        <p>
            <label><?php _e('Event Capacity:', 'wp-events'); ?></label>
            <input type="number" name="ticket_capacity" value="<?php echo esc_attr($ticket_capacity); ?>" 
                   min="0" step="1" style="width: 100%;">
            <small><?php _e('Maximum number of attendees (0 = unlimited)', 'wp-events'); ?></small>
        </p>
        
        <p>
            <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button" target="_blank">
                <?php _e('Create New Product', 'wp-events'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Save ticket settings
     */
    public static function save_ticket_meta($post_id) {
        if (!isset($_POST['wpevents_ticket_nonce']) || 
            !wp_verify_nonce($_POST['wpevents_ticket_nonce'], 'wpevents_ticket_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $enable_tickets = isset($_POST['enable_tickets']) ? '1' : '0';
        update_post_meta($post_id, 'enable_tickets', $enable_tickets);
        
        if (isset($_POST['ticket_product_id'])) {
            update_post_meta($post_id, 'ticket_product_id', absint($_POST['ticket_product_id']));
        }
        
        if (isset($_POST['ticket_capacity'])) {
            $capacity = absint($_POST['ticket_capacity']);
            update_post_meta($post_id, 'ticket_capacity', $capacity);
        }
    }
    
    /**
     * Add ticket purchase button to event content
     */
    public static function add_ticket_button($content) {
        if (!is_singular('event')) {
            return $content;
        }
        
        $event_id = get_the_ID();
        $enable_tickets = get_post_meta($event_id, 'enable_tickets', true);
        $product_id = get_post_meta($event_id, 'ticket_product_id', true);
        
        if ($enable_tickets !== '1' || !$product_id) {
            return $content;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return $content;
        }
        
        // Check capacity
        $capacity = get_post_meta($event_id, 'ticket_capacity', true);
        $sold = self::get_tickets_sold($event_id);
        
        $button_html = '<div class="wp-events-ticket-section" style="margin: 30px 0; padding: 20px; background: #f7f7f7; border-radius: 5px;">';
        $button_html .= '<h3>' . __('Get Tickets', 'wp-events') . '</h3>';
        
        if ($capacity > 0) {
            $remaining = $capacity - $sold;
            $button_html .= '<p>';
            $button_html .= sprintf(
                __('Available: %d / %d tickets', 'wp-events'),
                max(0, $remaining),
                $capacity
            );
            $button_html .= '</p>';
            
            if ($remaining <= 0) {
                $button_html .= '<p><strong>' . __('Sorry, this event is sold out.', 'wp-events') . '</strong></p>';
                $button_html .= '</div>';
                return $content . $button_html;
            }
        }
        
        $button_html .= '<p><strong>' . __('Price:', 'wp-events') . '</strong> ' . $product->get_price_html() . '</p>';
        
        $add_to_cart_url = add_query_arg([
            'add-to-cart' => $product_id,
            'event_id' => $event_id
        ], wc_get_cart_url());
        
        $button_html .= '<a href="' . esc_url($add_to_cart_url) . '" class="button wp-events-buy-ticket" style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-weight: bold;">';
        $button_html .= __('Buy Ticket', 'wp-events');
        $button_html .= '</a>';
        $button_html .= '</div>';
        
        return $content . $button_html;
    }
    
    /**
     * Get number of tickets sold for an event
     */
    protected static function get_tickets_sold($event_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta 
            WHERE meta_key = '_event_id' AND meta_value = %d",
            $event_id
        ));
        
        return absint($count);
    }
    
    /**
     * Add event info to order item
     */
    public static function add_event_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($_GET['event_id'])) {
            $event_id = absint($_GET['event_id']);
            $item->add_meta_data('_event_id', $event_id, true);
            $item->add_meta_data(__('Event', 'wp-events'), get_the_title($event_id), true);
            
            // Add event date
            $start = get_post_meta($event_id, 'event_start', true);
            if ($start) {
                $item->add_meta_data(
                    __('Event Date', 'wp-events'), 
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start)),
                    true
                );
            }
        }
    }
    
    /**
     * Display event info in order details
     */
    public static function display_event_in_order($item_id, $item, $order, $plain_text) {
        $event_id = wc_get_order_item_meta($item_id, '_event_id', true);
        
        if (!$event_id) {
            return;
        }
        
        if ($plain_text) {
            echo "\n" . __('Event:', 'wp-events') . ' ' . get_the_title($event_id);
        }
    }
    
    /**
     * Add attendee fields to checkout
     */
    public static function add_attendee_fields($fields) {
        // Check if cart contains event tickets
        if (!self::cart_has_event_tickets()) {
            return $fields;
        }
        
        $fields['billing']['attendee_name'] = [
            'label' => __('Attendee Name', 'wp-events'),
            'placeholder' => __('Full name of attendee', 'wp-events'),
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 25
        ];
        
        $fields['billing']['attendee_email'] = [
            'label' => __('Attendee Email', 'wp-events'),
            'placeholder' => __('Email for ticket confirmation', 'wp-events'),
            'type' => 'email',
            'required' => false,
            'class' => ['form-row-wide'],
            'priority' => 26
        ];
        
        return $fields;
    }
    
    /**
     * Check if cart contains event tickets
     */
    protected static function cart_has_event_tickets() {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['event_id'])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Save attendee data to order
     */
    public static function save_attendee_data($order_id) {
        if (isset($_POST['attendee_name'])) {
            update_post_meta($order_id, 'attendee_name', sanitize_text_field($_POST['attendee_name']));
        }
        
        if (isset($_POST['attendee_email'])) {
            update_post_meta($order_id, 'attendee_email', sanitize_email($_POST['attendee_email']));
        }
    }
    
    /**
     * Add event ticket product type
     */
    public static function add_event_ticket_product_type($types) {
        $types['event_ticket'] = __('Event Ticket', 'wp-events');
        return $types;
    }
    
    /**
     * Sync ticket stock with event capacity
     */
    public static function sync_ticket_stock($event_id) {
        $enable_tickets = get_post_meta($event_id, 'enable_tickets', true);
        $product_id = get_post_meta($event_id, 'ticket_product_id', true);
        $capacity = get_post_meta($event_id, 'ticket_capacity', true);
        
        if ($enable_tickets !== '1' || !$product_id || !$capacity) {
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Update product stock to match capacity
        $sold = self::get_tickets_sold($event_id);
        $remaining = max(0, $capacity - $sold);
        
        $product->set_manage_stock(true);
        $product->set_stock_quantity($remaining);
        $product->save();
    }
}
