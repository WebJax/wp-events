<?php
/**
 * WooCommerce integration for event tickets.
 *
 * @package WPEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Add meta box for ticket settings
		add_action( 'add_meta_boxes_event', array( __CLASS__, 'add_ticket_meta_box' ) );
		add_action( 'save_post_event', array( __CLASS__, 'save_ticket_meta' ) );

		// Display ticket purchase button on event pages
		add_filter( 'the_content', array( __CLASS__, 'add_ticket_button' ) );

		// Add event info to cart items
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_event_to_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 10, 2 );

		// Add event info to order
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_event_to_order_item' ), 10, 4 );

		// Display event info in order details
		add_filter( 'woocommerce_order_item_meta_end', array( __CLASS__, 'display_event_in_order' ), 10, 4 );

		// Add attendee fields to checkout
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_attendee_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_attendee_data' ) );

		// Add event ticket product type
		add_filter( 'product_type_selector', array( __CLASS__, 'add_event_ticket_product_type' ) );

		// Sync event capacity with product stock
		add_action( 'save_post_event', array( __CLASS__, 'sync_ticket_stock' ), 20 );
	}

	/**
	 * Add ticket settings meta box
	 */
	public static function add_ticket_meta_box() {
		add_meta_box(
			'wpevents_ticket_settings',
			__( 'Ticket Settings (WooCommerce)', 'wp-events' ),
			array( __CLASS__, 'render_ticket_meta_box' ),
			'event',
			'side',
			'default'
		);
	}

	/**
	 * Render ticket settings meta box
	 */
	public static function render_ticket_meta_box( $post ) {
		wp_nonce_field( 'wpevents_ticket_meta', 'wpevents_ticket_nonce' );

		$enable_tickets    = get_post_meta( $post->ID, 'enable_tickets', true );
		$ticket_product_id = get_post_meta( $post->ID, 'ticket_product_id', true );
		$ticket_capacity   = get_post_meta( $post->ID, 'ticket_capacity', true );

		?>
		<p>
			<label>
				<input type="checkbox" name="enable_tickets" value="1" <?php checked( $enable_tickets, '1' ); ?>>
				<?php _e( 'Enable ticket sales', 'wp-events' ); ?>
			</label>
		</p>

		<p>
			<label><?php _e( 'Ticket Product:', 'wp-events' ); ?></label>
			<select name="ticket_product_id" style="width: 100%;">
				<option value=""><?php _e( '-- Select Product --', 'wp-events' ); ?></option>
				<?php
				$products = get_posts(
					array(
						'post_type'      => 'product',
						'posts_per_page' => 50,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'no_found_rows'  => true,
					)
				);

				foreach ( $products as $product ) {
					printf(
						'<option value="%d" %s>%s</option>',
						$product->ID,
						selected( $ticket_product_id, $product->ID, false ),
						esc_html( $product->post_title )
					);
				}
				?>
			</select>
			<small><?php _e( 'Select the WooCommerce product to use for tickets', 'wp-events' ); ?></small>
		</p>

		<p>
			<label><?php _e( 'Event Capacity:', 'wp-events' ); ?></label>
			<input type="number" name="ticket_capacity" value="<?php echo esc_attr( $ticket_capacity ); ?>" min="0" step="1" style="width: 100%;">
			<small><?php _e( 'Maximum number of attendees (0 = unlimited)', 'wp-events' ); ?></small>
		</p>

		<p>
			<a href="<?php echo admin_url( 'post-new.php?post_type=product' ); ?>" class="button" target="_blank">
				<?php _e( 'Create New Product', 'wp-events' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Save ticket settings
	 */
	public static function save_ticket_meta( $post_id ) {
		if ( ! isset( $_POST['wpevents_ticket_nonce'] ) ||
			! wp_verify_nonce( $_POST['wpevents_ticket_nonce'], 'wpevents_ticket_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$enable_tickets = isset( $_POST['enable_tickets'] ) ? '1' : '0';
		update_post_meta( $post_id, 'enable_tickets', $enable_tickets );

		if ( isset( $_POST['ticket_product_id'] ) ) {
			update_post_meta( $post_id, 'ticket_product_id', absint( $_POST['ticket_product_id'] ) );
		}

		if ( isset( $_POST['ticket_capacity'] ) ) {
			$capacity = absint( $_POST['ticket_capacity'] );
			update_post_meta( $post_id, 'ticket_capacity', $capacity );
		}
	}

	/**
	 * Add ticket purchase button to event content
	 */
	public static function add_ticket_button( $content ) {
		if ( ! is_singular( 'event' ) ) {
			return $content;
		}

		$event_id       = get_the_ID();
		$enable_tickets = get_post_meta( $event_id, 'enable_tickets', true );
		$product_id     = get_post_meta( $event_id, 'ticket_product_id', true );

		if ( $enable_tickets !== '1' || ! $product_id ) {
			return $content;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $content;
		}

		// Check capacity
		$capacity = get_post_meta( $event_id, 'ticket_capacity', true );
		$sold     = self::get_tickets_sold( $event_id );

		$button_html  = '<div class="wp-events-ticket-section" style="margin: 30px 0; padding: 20px; background: #f7f7f7; border-radius: 5px;">';
		$button_html .= '<h3>' . esc_html__( 'Get Tickets', 'wp-events' ) . '</h3>';

		if ( $capacity > 0 ) {
			$remaining    = $capacity - $sold;
			$button_html .= '<p>';
			$button_html .= sprintf(
				esc_html__( 'Available: %d / %d tickets', 'wp-events' ),
				max( 0, $remaining ),
				$capacity
			);
			$button_html .= '</p>';

			if ( $remaining <= 0 ) {
				$button_html .= '<p><strong>' . esc_html__( 'Sorry, this event is sold out.', 'wp-events' ) . '</strong></p>';
				$button_html .= '</div>';
				return $content . $button_html;
			}
		}

		$button_html .= '<p><strong>' . esc_html__( 'Price:', 'wp-events' ) . '</strong> ' . $product->get_price_html() . '</p>';

		$add_to_cart_url = add_query_arg(
			array(
				'add-to-cart' => $product_id,
			),
			wc_get_cart_url()
		);

		$button_html .= '<a href="' . esc_url( $add_to_cart_url ) . '" class="button wp-events-buy-ticket" style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-weight: bold;">';
		$button_html .= esc_html__( 'Buy Ticket', 'wp-events' );
		$button_html .= '</a>';
		$button_html .= '</div>';

		return $content . $button_html;
	}

	/**
	 * Add event ID to cart item data
	 */
	public static function add_event_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
		global $wpdb;

		$event_id = 0;

		// Prefer an explicitly provided event ID from the add-to-cart request, if available
		if ( isset( $_REQUEST['wpevents_event_id'] ) ) {
			$requested_event_id = absint( $_REQUEST['wpevents_event_id'] );

			if ( $requested_event_id > 0 ) {
				// Validate that the requested event is actually linked to this product
				$linked_event_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} 
                         WHERE post_id = %d 
                         AND meta_key = 'ticket_product_id' 
                         AND meta_value = %d",
						$requested_event_id,
						$product_id
					)
				);

				if ( $linked_event_id ) {
					$event_id = (int) $linked_event_id;
				}
			}
		}

		// If no valid explicit event was provided, fall back to inferring it from postmeta,
		// but only when there is exactly one unambiguous match for this product
		if ( ! $event_id ) {
			$event_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
                     WHERE meta_key = 'ticket_product_id' 
                     AND meta_value = %d",
					$product_id
				)
			);

			if ( is_array( $event_ids ) && count( $event_ids ) === 1 ) {
				$event_id = (int) $event_ids[0];
			}
		}

		if ( $event_id > 0 ) {
			$cart_item_data['event_id'] = $event_id;
		}

		return $cart_item_data;
	}

	/**
	 * Get cart item from session
	 */
	public static function get_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['event_id'] ) ) {
			$cart_item['event_id'] = $values['event_id'];
		}
		return $cart_item;
	}

	/**
	 * Get number of tickets sold for an event
	 */
	protected static function get_tickets_sold( $event_id ) {
		global $wpdb;

		// Sum quantities from completed and processing orders
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(CAST(qty_meta.meta_value AS UNSIGNED)), 0)
            FROM {$wpdb->prefix}woocommerce_order_itemmeta event_meta
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON event_meta.order_item_id = oi.order_item_id
            INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta qty_meta 
                ON qty_meta.order_item_id = event_meta.order_item_id
                AND qty_meta.meta_key = '_qty'
            WHERE event_meta.meta_key = '_event_id'
            AND event_meta.meta_value = %d
            AND p.post_status IN ('wc-completed', 'wc-processing')",
				$event_id
			)
		);

		return absint( $count );
	}

	/**
	 * Add event info to order item
	 */
	public static function add_event_to_order_item( $item, $cart_item_key, $values, $order ) {
		// Get event_id from cart item data, not from $_GET
		if ( isset( $values['event_id'] ) ) {
			$event_id = absint( $values['event_id'] );
			$item->add_meta_data( '_event_id', $event_id, true );
			$item->add_meta_data( __( 'Event', 'wp-events' ), get_the_title( $event_id ), true );

			// Add event date
			$start = get_post_meta( $event_id, 'event_start', true );
			if ( $start ) {
				$item->add_meta_data(
					__( 'Event Date', 'wp-events' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start ) ),
					true
				);
			}
		}
	}

	/**
	 * Display event info in order details
	 */
	public static function display_event_in_order( $item_id, $item, $order, $plain_text ) {
		$event_id = wc_get_order_item_meta( $item_id, '_event_id', true );

		if ( ! $event_id ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Event:', 'wp-events' ) . ' ' . esc_html( get_the_title( $event_id ) );
		}
	}

	/**
	 * Add attendee fields to checkout
	 */
	public static function add_attendee_fields( $fields ) {
		// Check if cart contains event tickets
		if ( ! self::cart_has_event_tickets() ) {
			return $fields;
		}

		$fields['billing']['attendee_name'] = array(
			'label'       => __( 'Attendee Name', 'wp-events' ),
			'placeholder' => __( 'Full name of attendee', 'wp-events' ),
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 25,
		);

		$fields['billing']['attendee_email'] = array(
			'label'       => __( 'Attendee Email', 'wp-events' ),
			'placeholder' => __( 'Email for ticket confirmation', 'wp-events' ),
			'type'        => 'email',
			'required'    => false,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 26,
		);

		return $fields;
	}

	/**
	 * Check if cart contains event tickets
	 */
	protected static function cart_has_event_tickets() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['event_id'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Save attendee data to order
	 */
	public static function save_attendee_data( $order_id ) {
		// WooCommerce handles nonce verification during checkout
		// We only process if this is a legitimate checkout request
		if ( ! is_admin() && did_action( 'woocommerce_checkout_process' ) ) {
			if ( isset( $_POST['attendee_name'] ) ) {
				update_post_meta( $order_id, 'attendee_name', sanitize_text_field( $_POST['attendee_name'] ) );
			}

			if ( isset( $_POST['attendee_email'] ) ) {
				update_post_meta( $order_id, 'attendee_email', sanitize_email( $_POST['attendee_email'] ) );
			}
		}
	}

	/**
	 * Add event ticket product type
	 */
	public static function add_event_ticket_product_type( $types ) {
		$types['event_ticket'] = __( 'Event Ticket', 'wp-events' );
		return $types;
	}

	/**
	 * Sync ticket stock with event capacity
	 */
	public static function sync_ticket_stock( $event_id ) {
		$enable_tickets = get_post_meta( $event_id, 'enable_tickets', true );
		$product_id     = get_post_meta( $event_id, 'ticket_product_id', true );
		$capacity_raw   = get_post_meta( $event_id, 'ticket_capacity', true );

		// Only proceed when tickets are enabled and a product is linked
		if ( $enable_tickets !== '1' || ! $product_id ) {
			return;
		}

		// If capacity is not set at all, do not change stock settings
		if ( $capacity_raw === '' || $capacity_raw === false ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		// Normalize capacity to integer
		$capacity = (int) $capacity_raw;

		// Capacity 0 means unlimited tickets: disable stock management and ensure product is in stock
		if ( $capacity === 0 ) {
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			$product->save();
			return;
		}

		// Update product stock to match limited capacity
		$sold      = self::get_tickets_sold( $event_id );
		$remaining = max( 0, $capacity - $sold );

		$product->set_manage_stock( true );
		$product->set_stock_quantity( $remaining );
		$product->save();
	}
}
