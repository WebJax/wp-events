<?php
/**
 * WooCommerce integration for event tickets.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Ticket sales through WooCommerce.
 */
class WooCommerce {

	/**
	 * Meta key used when event capacity forces a product out of stock.
	 *
	 * @var string
	 */
	const CAPACITY_BLOCK_META = '_wpevents_capacity_blocked';

	/**
	 * Initialize WooCommerce integration
	 */
	public static function init() {
		// Only load if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Add meta box for ticket settings.
		add_action( 'add_meta_boxes_event', array( __CLASS__, 'add_ticket_meta_box' ) );
		add_action( 'save_post_event', array( __CLASS__, 'save_ticket_meta' ) );

		// Display ticket purchase button on event pages.
		add_filter( 'the_content', array( __CLASS__, 'add_ticket_button' ) );

		// Add event info to cart items.
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_event_to_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_event_in_cart' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_event_capacity_on_add_to_cart' ), 10, 3 );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart_event_capacity' ) );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( __CLASS__, 'validate_cart_item_quantity' ), 10, 4 );

		// Add event info to order.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_event_to_order_item' ), 10, 4 );

		// Display event info in order details.
		add_filter( 'woocommerce_order_item_meta_end', array( __CLASS__, 'display_event_in_order' ), 10, 4 );

		// Add attendee fields to checkout.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_attendee_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_attendee_data' ) );

		// Add event ticket product type (behaves as a simple product).
		add_filter( 'product_type_selector', array( __CLASS__, 'add_event_ticket_product_type' ) );
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'get_event_ticket_product_class' ), 10, 2 );
		add_action( 'woocommerce_event_ticket_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
		add_action( 'admin_footer', array( __CLASS__, 'enable_event_ticket_product_js' ) );

		// Keep sold_out status in sync; do not mutate WooCommerce product stock
		// (the same product can be sold for multiple events).
		add_action( 'save_post_event', array( __CLASS__, 'sync_ticket_stock' ), 20 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'sync_capacity_on_order_status_change' ), 10, 4 );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'sync_capacity_on_order_refunded' ) );
	}

	/**
	 * Get linked ticket product IDs for an event.
	 *
	 * Falls back to legacy single `ticket_product_id` when the new meta is empty.
	 *
	 * @param int $event_id Event post ID.
	 * @return int[]
	 */
	public static function get_ticket_product_ids( $event_id ) {
		$event_id    = absint( $event_id );
		$product_ids = get_post_meta( $event_id, 'ticket_product_ids', true );

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array();
		}

		$product_ids = array_values(
			array_unique(
				array_filter( array_map( 'absint', $product_ids ) )
			)
		);

		if ( empty( $product_ids ) ) {
			$legacy_id = absint( get_post_meta( $event_id, 'ticket_product_id', true ) );
			if ( $legacy_id > 0 ) {
				$product_ids = array( $legacy_id );
			}
		}

		return $product_ids;
	}

	/**
	 * Whether an event is linked to a given WooCommerce product.
	 *
	 * @param int $event_id   Event post ID.
	 * @param int $product_id Product post ID.
	 * @return bool
	 */
	public static function event_has_ticket_product( $event_id, $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			return false;
		}

		return in_array( $product_id, self::get_ticket_product_ids( $event_id ), true );
	}

	/**
	 * Resolve the event ID for a product from the request or an unambiguous link.
	 *
	 * @param int $product_id Product ID.
	 * @return int Event ID or 0.
	 */
	protected static function resolve_event_id_for_product( $product_id ) {
		$product_id = absint( $product_id );
		$event_id   = 0;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Add-to-cart request; WC handles checkout nonce separately.
		if ( isset( $_REQUEST['wpevents_event_id'] ) ) {
			$requested_event_id = absint( $_REQUEST['wpevents_event_id'] );
			if ( $requested_event_id > 0 && self::event_has_ticket_product( $requested_event_id, $product_id ) ) {
				$event_id = $requested_event_id;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $event_id ) {
			$event_ids = self::find_event_ids_for_product( $product_id );
			if ( count( $event_ids ) === 1 ) {
				$event_id = (int) $event_ids[0];
			}
		}

		return $event_id;
	}

	/**
	 * Find event IDs linked to a product (multi-product meta + legacy scalar).
	 *
	 * @param int $product_id Product post ID.
	 * @return int[]
	 */
	protected static function find_event_ids_for_product( $product_id ) {
		global $wpdb;

		$product_id = absint( $product_id );
		if ( $product_id <= 0 ) {
			return array();
		}

		$event_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta}
				WHERE (
					(meta_key = 'ticket_product_id' AND meta_value = %s)
					OR (
						meta_key = 'ticket_product_ids'
						AND (meta_value LIKE %s OR meta_value LIKE %s)
					)
				)",
				(string) $product_id,
				'%i:' . $product_id . ';%',
				'%s:"' . $product_id . '";%'
			)
		);

		if ( ! is_array( $event_ids ) ) {
			return array();
		}

		$event_ids = array_values( array_unique( array_map( 'absint', $event_ids ) ) );

		// Confirm membership via helper (handles malformed serialized data).
		return array_values(
			array_filter(
				$event_ids,
				static function ( $event_id ) use ( $product_id ) {
					return self::event_has_ticket_product( $event_id, $product_id );
				}
			)
		);
	}

	/**
	 * Remaining shared event capacity, or null when unlimited / unset.
	 *
	 * @param int $event_id Event post ID.
	 * @return int|null Null when unlimited or capacity not set.
	 */
	protected static function get_event_capacity_remaining( $event_id ) {
		$capacity_raw = get_post_meta( $event_id, 'ticket_capacity', true );

		if ( '' === $capacity_raw || false === $capacity_raw ) {
			return null;
		}

		$capacity = (int) $capacity_raw;
		if ( $capacity <= 0 ) {
			return null;
		}

		return max( 0, $capacity - self::get_tickets_sold( $event_id ) );
	}

	/**
	 * Public remaining shared capacity, or null when unlimited / unset.
	 *
	 * @param int $event_id Event post ID.
	 * @return int|null
	 */
	public static function get_remaining_capacity( $event_id ) {
		return self::get_event_capacity_remaining( $event_id );
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

		$enable_tickets     = get_post_meta( $post->ID, 'enable_tickets', true );
		$ticket_product_ids = self::get_ticket_product_ids( $post->ID );
		$ticket_capacity    = get_post_meta( $post->ID, 'ticket_capacity', true );

		?>
		<p>
			<label>
				<input type="checkbox" name="enable_tickets" value="1" <?php checked( $enable_tickets, '1' ); ?>>
				<?php _e( 'Enable ticket sales', 'wp-events' ); ?>
			</label>
		</p>

		<p>
			<label><?php _e( 'Ticket Products:', 'wp-events' ); ?></label>
			<select name="ticket_product_ids[]" multiple size="6" style="width: 100%;">
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

				if ( empty( $products ) ) {
					echo '<option value="" disabled>' . esc_html__( 'No products found', 'wp-events' ) . '</option>';
				}

				foreach ( $products as $product ) {
					printf(
						'<option value="%d" %s>%s</option>',
						$product->ID,
						selected( in_array( (int) $product->ID, $ticket_product_ids, true ), true, false ),
						esc_html( $product->post_title )
					);
				}
				?>
			</select>
			<small><?php _e( 'Select one or more WooCommerce products as ticket types. Hold Ctrl/Cmd to select multiple.', 'wp-events' ); ?></small>
		</p>

		<p>
			<label><?php _e( 'Event Capacity:', 'wp-events' ); ?></label>
			<input type="number" name="ticket_capacity" value="<?php echo esc_attr( $ticket_capacity ); ?>" min="0" step="1" style="width: 100%;">
			<small><?php _e( 'Shared maximum attendees across all ticket types (0 = unlimited). Each product also keeps its own WooCommerce stock.', 'wp-events' ); ?></small>
		</p>

		<p>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>" class="button" target="_blank">
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
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpevents_ticket_nonce'] ) ), 'wpevents_ticket_meta' ) ) {
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

		$product_ids = array();
		if ( isset( $_POST['ticket_product_ids'] ) && is_array( $_POST['ticket_product_ids'] ) ) {
			$product_ids = array_map( 'absint', wp_unslash( $_POST['ticket_product_ids'] ) );
			$product_ids = array_values( array_unique( array_filter( $product_ids ) ) );
		}

		if ( ! empty( $product_ids ) ) {
			update_post_meta( $post_id, 'ticket_product_ids', $product_ids );
			// Keep legacy scalar in sync with the first product for older lookups.
			update_post_meta( $post_id, 'ticket_product_id', $product_ids[0] );
		} else {
			delete_post_meta( $post_id, 'ticket_product_ids' );
			delete_post_meta( $post_id, 'ticket_product_id' );
		}

		if ( isset( $_POST['ticket_capacity'] ) ) {
			$capacity = absint( $_POST['ticket_capacity'] );
			update_post_meta( $post_id, 'ticket_capacity', $capacity );
		}
	}

	/**
	 * Add ticket purchase buttons to event content
	 */
	public static function add_ticket_button( $content ) {
		if ( ! is_singular( 'event' ) ) {
			return $content;
		}

		$event_id       = get_the_ID();
		$enable_tickets = get_post_meta( $event_id, 'enable_tickets', true );
		$product_ids    = self::get_ticket_product_ids( $event_id );

		if ( '1' !== $enable_tickets || empty( $product_ids ) ) {
			return $content;
		}

		$capacity  = get_post_meta( $event_id, 'ticket_capacity', true );
		$sold      = self::get_tickets_sold( $event_id );
		$remaining = null;
		if ( '' !== $capacity && false !== $capacity && (int) $capacity > 0 ) {
			$remaining = max( 0, (int) $capacity - $sold );
		}

		$button_html  = '<div class="wp-events-ticket-section" style="margin: 30px 0; padding: 20px; background: #f7f7f7; border-radius: 5px;">';
		$button_html .= '<h3>' . esc_html__( 'Get Tickets', 'wp-events' ) . '</h3>';

		if ( null !== $remaining ) {
			$button_html .= '<p>';
			$button_html .= sprintf(
				esc_html__( 'Available: %d / %d tickets', 'wp-events' ),
				$remaining,
				(int) $capacity
			);
			$button_html .= '</p>';

			if ( $remaining <= 0 ) {
				$button_html .= '<p><strong>' . esc_html__( 'Sorry, this event is sold out.', 'wp-events' ) . '</strong></p>';
				$button_html .= '</div>';
				return $content . $button_html;
			}
		}

		$button_html .= '<ul class="wp-events-ticket-types" style="list-style: none; padding: 0; margin: 0;">';

		$has_purchasable = false;
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$is_purchasable = $product->is_purchasable() && $product->is_in_stock();
			$button_html   .= '<li class="wp-events-ticket-type" style="margin: 0 0 16px; padding: 12px 0; border-top: 1px solid #e2e2e2;">';
			$button_html   .= '<strong>' . esc_html( $product->get_name() ) . '</strong>';
			$button_html   .= '<p style="margin: 6px 0;"><strong>' . esc_html__( 'Price:', 'wp-events' ) . '</strong> ' . $product->get_price_html() . '</p>';

			if ( ! $is_purchasable ) {
				$button_html .= '<p><em>' . esc_html__( 'This ticket type is sold out.', 'wp-events' ) . '</em></p>';
			} else {
				$has_purchasable = true;
				$add_to_cart_url = add_query_arg(
					array(
						'add-to-cart'       => $product_id,
						'wpevents_event_id' => $event_id,
					),
					wc_get_cart_url()
				);

				$button_html .= '<a href="' . esc_url( $add_to_cart_url ) . '" class="button wp-events-buy-ticket" style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; font-weight: bold;">';
				$button_html .= esc_html__( 'Buy Ticket', 'wp-events' );
				$button_html .= '</a>';
			}

			$button_html .= '</li>';
		}

		$button_html .= '</ul>';

		if ( ! $has_purchasable ) {
			$button_html .= '<p><strong>' . esc_html__( 'Sorry, no ticket types are currently available.', 'wp-events' ) . '</strong></p>';
		}

		$button_html .= '</div>';

		return $content . $button_html;
	}

	/**
	 * Add event ID to cart item data
	 */
	public static function add_event_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
		$event_id = self::resolve_event_id_for_product( $product_id );

		if ( $event_id > 0 ) {
			$cart_item_data['event_id'] = $event_id;
		}

		return $cart_item_data;
	}

	/**
	 * Block add-to-cart when shared event capacity is exhausted.
	 *
	 * @param bool $passed     Whether validation passed.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity being added.
	 * @return bool
	 */
	public static function validate_event_capacity_on_add_to_cart( $passed, $product_id, $quantity ) {
		if ( ! $passed ) {
			return $passed;
		}

		$event_id = self::resolve_event_id_for_product( $product_id );
		if ( ! $event_id ) {
			return $passed;
		}

		$enable_tickets = get_post_meta( $event_id, 'enable_tickets', true );
		if ( '1' !== $enable_tickets ) {
			return $passed;
		}

		$remaining = self::get_event_capacity_remaining( $event_id );
		if ( null === $remaining ) {
			return $passed;
		}

		$quantity_in_cart = 0;
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item['event_id'] ) && (int) $cart_item['event_id'] === $event_id ) {
					$quantity_in_cart += (int) $cart_item['quantity'];
				}
			}
		}

		if ( ( $quantity_in_cart + (int) $quantity ) > $remaining ) {
			wc_add_notice(
				__( 'Sorry, this event does not have enough remaining capacity for that quantity.', 'wp-events' ),
				'error'
			);
			return false;
		}

		return $passed;
	}

	/**
	 * Validate shared event capacity for all ticket lines currently in the cart.
	 *
	 * Runs on cart and checkout so quantity changes and race conditions are caught.
	 */
	public static function validate_cart_event_capacity() {
		$quantities = self::get_cart_quantities_by_event();
		if ( empty( $quantities ) ) {
			return;
		}

		foreach ( $quantities as $event_id => $quantity ) {
			$remaining = self::get_event_capacity_remaining( $event_id );
			if ( null === $remaining ) {
				continue;
			}

			if ( $quantity > $remaining ) {
				wc_add_notice(
					sprintf(
						/* translators: 1: event title, 2: remaining ticket count */
						__( 'Not enough remaining capacity for "%1$s". Only %2$d ticket(s) left.', 'wp-events' ),
						get_the_title( $event_id ),
						$remaining
					),
					'error'
				);
			}
		}
	}

	/**
	 * Revert a cart quantity increase that would exceed shared event capacity.
	 *
	 * @param string     $cart_item_key Cart item key.
	 * @param int        $quantity      New quantity.
	 * @param int        $old_quantity  Previous quantity.
	 * @param \WC_Cart   $cart          Cart object.
	 */
	public static function validate_cart_item_quantity( $cart_item_key, $quantity, $old_quantity, $cart ) {
		if ( (int) $quantity <= (int) $old_quantity ) {
			return;
		}

		$cart_item = $cart->get_cart_item( $cart_item_key );
		if ( empty( $cart_item['event_id'] ) ) {
			return;
		}

		$event_id  = absint( $cart_item['event_id'] );
		$remaining = self::get_event_capacity_remaining( $event_id );
		if ( null === $remaining ) {
			return;
		}

		$quantities = self::get_cart_quantities_by_event( $cart );
		$in_cart    = isset( $quantities[ $event_id ] ) ? (int) $quantities[ $event_id ] : (int) $quantity;

		if ( $in_cart > $remaining ) {
			$allowed = max( 0, (int) $old_quantity );
			$cart->set_quantity( $cart_item_key, $allowed, false );
			wc_add_notice(
				sprintf(
					/* translators: 1: event title, 2: remaining ticket count */
					__( 'Not enough remaining capacity for "%1$s". Only %2$d ticket(s) left.', 'wp-events' ),
					get_the_title( $event_id ),
					$remaining
				),
				'error'
			);
		}
	}

	/**
	 * Sum ticket quantities in the cart grouped by event ID.
	 *
	 * @param \WC_Cart|null $cart Optional cart. Defaults to the current cart.
	 * @return array<int,int> Event ID => quantity.
	 */
	protected static function get_cart_quantities_by_event( $cart = null ) {
		if ( ! $cart ) {
			if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
				return array();
			}
			$cart = WC()->cart;
		}

		$quantities = array();
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['event_id'] ) ) {
				continue;
			}
			$event_id = absint( $cart_item['event_id'] );
			if ( $event_id <= 0 ) {
				continue;
			}
			if ( ! isset( $quantities[ $event_id ] ) ) {
				$quantities[ $event_id ] = 0;
			}
			$quantities[ $event_id ] += (int) $cart_item['quantity'];
		}

		return $quantities;
	}

	/**
	 * Show the linked event name on cart and checkout lines.
	 *
	 * @param array $item_data Cart item data rows.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public static function display_event_in_cart( $item_data, $cart_item ) {
		if ( empty( $cart_item['event_id'] ) ) {
			return $item_data;
		}

		$event_id = absint( $cart_item['event_id'] );
		$title    = get_the_title( $event_id );
		if ( ! $title ) {
			return $item_data;
		}

		$item_data[] = array(
			'key'   => __( 'Event', 'wp-events' ),
			'value' => $title,
		);

		return $item_data;
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

		$event_id = absint( $event_id );
		if ( $event_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			return 0;
		}

		$item_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta
				WHERE meta_key = '_event_id' AND meta_value = %s",
				(string) $event_id
			)
		);

		if ( empty( $item_ids ) ) {
			return 0;
		}

		$count         = 0;
		$counted_items = array();

		foreach ( $item_ids as $item_id ) {
			$item_id = absint( $item_id );
			if ( $item_id <= 0 || isset( $counted_items[ $item_id ] ) ) {
				continue;
			}
			$counted_items[ $item_id ] = true;

			$order_id = 0;
			if ( function_exists( 'wc_get_order_id_by_order_item_id' ) ) {
				$order_id = absint( wc_get_order_id_by_order_item_id( $item_id ) );
			}
			if ( $order_id <= 0 ) {
				continue;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			$status = $order->get_status();
			if ( ! in_array( $status, array( 'completed', 'processing' ), true ) ) {
				continue;
			}

			$item = $order->get_item( $item_id );
			if ( $item && is_callable( array( $item, 'get_quantity' ) ) ) {
				$count += absint( $item->get_quantity() );
			} else {
				$count += absint( wc_get_order_item_meta( $item_id, '_qty', true ) );
			}
		}

		return $count;
	}

	/**
	 * Add event info to order item
	 */
	public static function add_event_to_order_item( $item, $cart_item_key, $values, $order ) {
		// Get event_id from cart item data, not from $_GET.
		if ( isset( $values['event_id'] ) ) {
			$event_id = absint( $values['event_id'] );
			$item->add_meta_data( '_event_id', $event_id, true );
			$item->add_meta_data( __( 'Event', 'wp-events' ), get_the_title( $event_id ), true );

			// Add event date.
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

		$title = get_the_title( $event_id );
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Event:', 'wp-events' ) . ' ' . esc_html( $title );
			return;
		}

		echo '<p><strong>' . esc_html__( 'Event:', 'wp-events' ) . '</strong> ';
		echo '<a href="' . esc_url( get_permalink( $event_id ) ) . '">' . esc_html( $title ) . '</a></p>';
	}

	/**
	 * Add attendee fields to checkout
	 */
	public static function add_attendee_fields( $fields ) {
		// Check if cart contains event tickets.
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
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies checkout nonce before this hook fires.
		if ( isset( $_POST['attendee_name'] ) ) {
			$order->update_meta_data( 'attendee_name', sanitize_text_field( wp_unslash( $_POST['attendee_name'] ) ) );
		}

		if ( isset( $_POST['attendee_email'] ) ) {
			$order->update_meta_data( 'attendee_email', sanitize_email( wp_unslash( $_POST['attendee_email'] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$order->save();
	}

	/**
	 * Add event ticket product type
	 */
	public static function add_event_ticket_product_type( $types ) {
		$types['event_ticket'] = __( 'Event Ticket', 'wp-events' );
		return $types;
	}

	/**
	 * Map the event_ticket type to a purchasable simple-product class.
	 *
	 * @param string $classname    Product class name.
	 * @param string $product_type Product type slug.
	 * @return string
	 */
	public static function get_event_ticket_product_class( $classname, $product_type ) {
		if ( 'event_ticket' === $product_type ) {
			return EventTicketProduct::class;
		}
		return $classname;
	}

	/**
	 * Show simple-product panels when Event Ticket is selected in admin.
	 */
	public static function enable_event_ticket_product_js() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}
		?>
		<script type="text/javascript">
		jQuery( function( $ ) {
			$( '.product_data_tabs .general_tab' ).addClass( 'show_if_event_ticket' );
			$( '.options_group.pricing' ).addClass( 'show_if_event_ticket' );
			$( '.show_if_simple' ).addClass( 'show_if_event_ticket' );
			$( '.inventory_options' ).addClass( 'show_if_event_ticket' );
			$( '._manage_stock_field' ).addClass( 'show_if_event_ticket' );
			$( '._sold_individually_field' ).addClass( 'show_if_event_ticket' );
		} );
		</script>
		<?php
	}

	/**
	 * Keep event sold_out status in sync without mutating WooCommerce product stock.
	 *
	 * Shared ticket products must stay purchasable for other events. Capacity is
	 * enforced per event on add-to-cart, cart, and checkout.
	 */
	public static function sync_ticket_stock( $event_id ) {
		$product_ids = self::get_ticket_product_ids( $event_id );
		foreach ( $product_ids as $product_id ) {
			self::clear_capacity_block( $product_id );
		}

		if ( '1' === get_post_meta( $event_id, 'enable_tickets', true ) ) {
			self::maybe_update_sold_out_status( $event_id );
		}
	}

	/**
	 * Re-sync capacity for events on an order after status changes.
	 *
	 * @param int              $order_id   Order ID.
	 * @param string           $old_status Previous status.
	 * @param string           $new_status New status.
	 * @param \WC_Order|false  $order      Order object.
	 */
	public static function sync_capacity_on_order_status_change( $order_id, $old_status, $new_status, $order = null ) {
		unset( $old_status, $new_status );
		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		self::sync_capacity_for_order( $order );
	}

	/**
	 * Re-sync capacity after a refund.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function sync_capacity_on_order_refunded( $order_id ) {
		self::sync_capacity_for_order( wc_get_order( $order_id ) );
	}

	/**
	 * Sync ticket stock for every event referenced on an order.
	 *
	 * @param \WC_Order|false|null $order Order object.
	 */
	protected static function sync_capacity_for_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$event_ids = array();
		foreach ( $order->get_items() as $item ) {
			$event_id = absint( $item->get_meta( '_event_id' ) );
			if ( $event_id > 0 ) {
				$event_ids[ $event_id ] = true;
			}
		}

		foreach ( array_keys( $event_ids ) as $event_id ) {
			self::sync_ticket_stock( $event_id );
		}
	}

	/**
	 * Set or clear sold_out status from remaining shared capacity.
	 *
	 * Does not override cancelled, postponed, rescheduled, or completed.
	 *
	 * @param int $event_id Event post ID.
	 */
	protected static function maybe_update_sold_out_status( $event_id ) {
		$current = get_post_meta( $event_id, 'event_status', true );
		if ( $current && ! in_array( $current, array( 'scheduled', 'sold_out' ), true ) ) {
			return;
		}

		$remaining = self::get_event_capacity_remaining( $event_id );
		if ( null === $remaining ) {
			return;
		}

		if ( $remaining <= 0 ) {
			update_post_meta( $event_id, 'event_status', 'sold_out' );
		} elseif ( 'sold_out' === $current ) {
			update_post_meta( $event_id, 'event_status', 'scheduled' );
		}
	}

	/**
	 * Force a product out of stock due to shared event capacity without changing qty.
	 *
	 * @param \WC_Product $product Product instance.
	 */
	protected static function apply_capacity_block( $product ) {
		$product_id = $product->get_id();
		$already    = get_post_meta( $product_id, self::CAPACITY_BLOCK_META, true );

		if ( '1' !== $already ) {
			update_post_meta(
				$product_id,
				self::CAPACITY_BLOCK_META,
				array(
					'previous_status' => $product->get_stock_status(),
				)
			);
		}

		if ( 'outofstock' !== $product->get_stock_status() ) {
			$product->set_stock_status( 'outofstock' );
			$product->save();
		}
	}

	/**
	 * Clear a capacity block and restore previous stock status when appropriate.
	 *
	 * @param int              $product_id Product ID.
	 * @param \WC_Product|null $product    Optional product instance.
	 */
	protected static function clear_capacity_block( $product_id, $product = null ) {
		$block = get_post_meta( $product_id, self::CAPACITY_BLOCK_META, true );
		if ( empty( $block ) ) {
			return;
		}

		if ( ! $product ) {
			$product = wc_get_product( $product_id );
		}
		if ( ! $product ) {
			delete_post_meta( $product_id, self::CAPACITY_BLOCK_META );
			return;
		}

		$previous_status = 'instock';
		if ( is_array( $block ) && ! empty( $block['previous_status'] ) ) {
			$previous_status = sanitize_text_field( $block['previous_status'] );
		}

		// Do not restore to instock if the product itself has no stock left.
		if ( $product->managing_stock() && (int) $product->get_stock_quantity() <= 0 ) {
			$previous_status = 'outofstock';
		}

		if ( $product->get_stock_status() !== $previous_status ) {
			$product->set_stock_status( $previous_status );
			$product->save();
		}

		delete_post_meta( $product_id, self::CAPACITY_BLOCK_META );
	}
}
