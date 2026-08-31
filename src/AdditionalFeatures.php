<?php
/**
 * Additional event features like status and registration.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Event status, registration, and enhancements.
 */
class AdditionalFeatures {

	/**
	 * Initialize additional features
	 */
	public static function init() {
		// Add event status meta box.
		add_action( 'add_meta_boxes_event', array( __CLASS__, 'add_event_status_box' ) );
		add_action( 'save_post_event', array( __CLASS__, 'save_event_status' ) );

		// Add registration/RSVP functionality.
		add_action( 'add_meta_boxes_event', array( __CLASS__, 'add_registration_box' ) );
		add_action( 'save_post_event', array( __CLASS__, 'save_registration_settings' ) );

		// Display registration form on event pages.
		add_filter( 'the_content', array( __CLASS__, 'add_registration_form' ) );

		// Handle registration submissions.
		add_action( 'admin_post_event_registration', array( __CLASS__, 'handle_registration' ) );
		add_action( 'admin_post_nopriv_event_registration', array( __CLASS__, 'handle_registration' ) );
		add_action( 'admin_post_wpevents_registration_action', array( __CLASS__, 'handle_registration_action' ) );
		add_action( 'admin_post_wpevents_export_registrations', array( __CLASS__, 'export_registrations_csv' ) );
		add_action( 'admin_notices', array( __CLASS__, 'registration_admin_notice' ) );

		// Add admin columns for status.
		add_filter( 'manage_event_posts_columns', array( __CLASS__, 'add_status_column' ) );
		add_action( 'manage_event_posts_custom_column', array( __CLASS__, 'display_status_column' ), 10, 2 );

		// Add CSS for frontend.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_styles' ) );
	}

	/**
	 * Add event status meta box
	 */
	public static function add_event_status_box() {
		add_meta_box(
			'wpevents_event_status',
			__( 'Event Status', 'wp-events' ),
			array( __CLASS__, 'render_event_status_box' ),
			'event',
			'side',
			'high'
		);
	}

	/**
	 * Render event status meta box
	 */
	public static function render_event_status_box( $post ) {
		wp_nonce_field( 'wpevents_event_status', 'wpevents_event_status_nonce' );

		$event_status = get_post_meta( $post->ID, 'event_status', true );
		if ( ! $event_status ) {
			$event_status = 'scheduled';
		}

		$statuses = array(
			'scheduled'   => __( 'Scheduled', 'wp-events' ),
			'cancelled'   => __( 'Cancelled', 'wp-events' ),
			'postponed'   => __( 'Postponed', 'wp-events' ),
			'rescheduled' => __( 'Rescheduled', 'wp-events' ),
			'sold_out'    => __( 'Sold Out', 'wp-events' ),
			'completed'   => __( 'Completed', 'wp-events' ),
		);

		echo '<select name="event_status" style="width: 100%;">';
		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $event_status, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		echo '<p><small>' . esc_html__( 'This status will be displayed on the event page', 'wp-events' ) . '</small></p>';
	}

	/**
	 * Save event status
	 */
	public static function save_event_status( $post_id ) {
		if ( ! isset( $_POST['wpevents_event_status_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpevents_event_status_nonce'] ) ), 'wpevents_event_status' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['event_status'] ) ) {
			update_post_meta( $post_id, 'event_status', self::sanitize_event_status( wp_unslash( $_POST['event_status'] ) ) );
		}
	}

	/**
	 * Sanitize event status against an allowlist.
	 *
	 * @param mixed $value Raw status value.
	 * @return string
	 */
	public static function sanitize_event_status( $value ) {
		$allowed = array( 'scheduled', 'cancelled', 'postponed', 'rescheduled', 'sold_out', 'completed' );
		$v       = sanitize_key( (string) $value );
		return in_array( $v, $allowed, true ) ? $v : 'scheduled';
	}

	/**
	 * Add registration settings meta box
	 */
	public static function add_registration_box() {
		add_meta_box(
			'wpevents_registration',
			__( 'Registration Settings', 'wp-events' ),
			array( __CLASS__, 'render_registration_box' ),
			'event',
			'side',
			'high'
		);
	}

	/**
	 * Render registration meta box
	 */
	public static function render_registration_box( $post ) {
		wp_nonce_field( 'wpevents_registration', 'wpevents_registration_nonce' );

		$enable_registration   = get_post_meta( $post->ID, 'enable_registration', true );
		$max_attendees         = get_post_meta( $post->ID, 'max_attendees', true );
		$registration_deadline = get_post_meta( $post->ID, 'registration_deadline', true );
		$require_approval      = get_post_meta( $post->ID, 'require_approval', true );

		?>
		<table class="form-table">
			<tr>
				<th><label for="enable_registration"><?php _e( 'Enable Registration', 'wp-events' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="enable_registration" name="enable_registration" value="1" <?php checked( $enable_registration, '1' ); ?>>
						<?php _e( 'Allow attendees to register for this event', 'wp-events' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="max_attendees"><?php _e( 'Maximum Attendees', 'wp-events' ); ?></label></th>
				<td>
					<input type="number" id="max_attendees" name="max_attendees" value="<?php echo esc_attr( $max_attendees ); ?>" min="0" step="1" style="width: 200px;">
					<p class="description"><?php _e( '0 = unlimited', 'wp-events' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="registration_deadline"><?php _e( 'Registration Deadline', 'wp-events' ); ?></label></th>
				<td>
					<input type="datetime-local" id="registration_deadline" name="registration_deadline" value="<?php echo esc_attr( $registration_deadline ); ?>" style="width: 300px;">
					<p class="description"><?php _e( 'Last date/time to register', 'wp-events' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="require_approval"><?php _e( 'Require Approval', 'wp-events' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" id="require_approval" name="require_approval" value="1" <?php checked( $require_approval, '1' ); ?>>
						<?php _e( 'Registrations require admin approval', 'wp-events' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php
		// Display registrations list.
		$registrations = self::get_registrations( $post->ID, true );
		if ( ! empty( $registrations ) ) {
			echo '<h4>' . esc_html__( 'Current Registrations', 'wp-events' ) . ' (' . count( self::get_active_registrations( $post->ID ) ) . ')</h4>';
			echo '<p><a class="button" href="' . esc_url( self::export_registrations_url( $post->ID ) ) . '">' . esc_html__( 'Export CSV', 'wp-events' ) . '</a></p>';
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Name', 'wp-events' ) . '</th>';
			echo '<th>' . esc_html__( 'Email', 'wp-events' ) . '</th>';
			echo '<th>' . esc_html__( 'Date', 'wp-events' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'wp-events' ) . '</th>';
			echo '<th>' . esc_html__( 'Actions', 'wp-events' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $registrations as $reg ) {
				$status = isset( $reg['status'] ) ? $reg['status'] : 'confirmed';
				$reg_id = isset( $reg['id'] ) ? $reg['id'] : '';
				echo '<tr>';
				echo '<td>' . esc_html( $reg['name'] ) . '</td>';
				echo '<td>' . esc_html( $reg['email'] ) . '</td>';
				echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reg['date'] ) ) ) . '</td>';
				echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
				echo '<td>';
				if ( $reg_id ) {
					if ( 'pending' === $status ) {
						echo '<a href="' . esc_url( self::registration_action_url( $post->ID, $reg_id, 'approve' ) ) . '">' . esc_html__( 'Approve', 'wp-events' ) . '</a> | ';
						echo '<a href="' . esc_url( self::registration_action_url( $post->ID, $reg_id, 'reject' ) ) . '">' . esc_html__( 'Reject', 'wp-events' ) . '</a> | ';
					}
					echo '<a href="' . esc_url( self::registration_action_url( $post->ID, $reg_id, 'delete' ) ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this registration?', 'wp-events' ) ) . '\');">' . esc_html__( 'Delete', 'wp-events' ) . '</a>';
				}
				echo '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}
	}

	/**
	 * Save registration settings
	 */
	public static function save_registration_settings( $post_id ) {
		if ( ! isset( $_POST['wpevents_registration_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpevents_registration_nonce'] ) ), 'wpevents_registration' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$enable = isset( $_POST['enable_registration'] ) ? '1' : '0';
		update_post_meta( $post_id, 'enable_registration', $enable );

		if ( isset( $_POST['max_attendees'] ) ) {
			update_post_meta( $post_id, 'max_attendees', absint( $_POST['max_attendees'] ) );
		}

		if ( isset( $_POST['registration_deadline'] ) ) {
			$deadline_input = sanitize_text_field( wp_unslash( $_POST['registration_deadline'] ) );
			$deadline       = '';
			if ( '' !== $deadline_input ) {
				$timestamp = strtotime( $deadline_input );
				if ( false !== $timestamp ) {
					$deadline = date( 'Y-m-d H:i:s', $timestamp );
				}
			}
			update_post_meta( $post_id, 'registration_deadline', $deadline );
		}

		$require_approval = isset( $_POST['require_approval'] ) ? '1' : '0';
		update_post_meta( $post_id, 'require_approval', $require_approval );
	}

	/**
	 * Add registration form to event content
	 */
	public static function add_registration_form( $content ) {
		if ( ! is_singular( 'event' ) ) {
			return $content;
		}

		$event_id            = get_the_ID();
		$enable_registration = get_post_meta( $event_id, 'enable_registration', true );

		if ( '1' !== $enable_registration ) {
			return $content;
		}

		// Check if registration is closed.
		$deadline = get_post_meta( $event_id, 'registration_deadline', true );
		if ( $deadline && strtotime( $deadline ) < time() ) {
			$content .= '<div class="event-registration-closed">';
			$content .= '<p><strong>' . esc_html__( 'Registration is closed for this event.', 'wp-events' ) . '</strong></p>';
			$content .= '</div>';
			return $content;
		}

		// Check capacity.
		$max_attendees = get_post_meta( $event_id, 'max_attendees', true );
		$current_count = self::get_active_registration_count( $event_id );

		if ( $max_attendees > 0 && $current_count >= $max_attendees ) {
			$content .= '<div class="event-registration-full">';
			$content .= '<p><strong>' . esc_html__( 'This event is full. Registration is closed.', 'wp-events' ) . '</strong></p>';
			$content .= '</div>';
			return $content;
		}

		// Show success message if just registered.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only redirect flag, no nonce needed.
		if ( isset( $_GET['registered'] ) && '1' === sanitize_key( wp_unslash( $_GET['registered'] ) ) ) {
			$content .= '<div class="event-registration-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin: 20px 0;">';
			$content .= '<p><strong>' . esc_html__( 'Thank you for registering! You will receive a confirmation email.', 'wp-events' ) . '</strong></p>';
			$content .= '</div>';
		}

		// Build registration form.
		$form  = '<div class="event-registration-form" style="margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 5px;">';
		$form .= '<h3>' . esc_html__( 'Register for This Event', 'wp-events' ) . '</h3>';

		if ( $max_attendees > 0 ) {
			$remaining = $max_attendees - $current_count;
			$form     .= '<p>' . sprintf( esc_html__( '%d spots remaining', 'wp-events' ), $remaining ) . '</p>';
		}

		$form .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		$form .= wp_nonce_field( 'event_registration', 'registration_nonce', true, false );
		$form .= '<input type="hidden" name="action" value="event_registration">';
		$form .= '<input type="hidden" name="event_id" value="' . esc_attr( (string) $event_id ) . '">';

		$form .= '<p>';
		$form .= '<label for="reg_name">' . __( 'Your Name *', 'wp-events' ) . '</label><br>';
		$form .= '<input type="text" id="reg_name" name="reg_name" required style="width: 100%; padding: 8px;">';
		$form .= '</p>';

		$form .= '<p>';
		$form .= '<label for="reg_email">' . __( 'Your Email *', 'wp-events' ) . '</label><br>';
		$form .= '<input type="email" id="reg_email" name="reg_email" required style="width: 100%; padding: 8px;">';
		$form .= '</p>';

		$form .= '<p>';
		$form .= '<label for="reg_phone">' . __( 'Phone Number', 'wp-events' ) . '</label><br>';
		$form .= '<input type="tel" id="reg_phone" name="reg_phone" style="width: 100%; padding: 8px;">';
		$form .= '</p>';

		$form .= '<p>';
		$form .= '<label for="reg_notes">' . __( 'Notes/Comments', 'wp-events' ) . '</label><br>';
		$form .= '<textarea id="reg_notes" name="reg_notes" rows="3" style="width: 100%; padding: 8px;"></textarea>';
		$form .= '</p>';

		$form .= '<p>';
		$form .= '<input type="submit" value="' . esc_attr__( 'Register Now', 'wp-events' ) . '" class="button button-primary" style="padding: 12px 24px; font-size: 16px;">';
		$form .= '</p>';

		$form .= '</form>';
		$form .= '</div>';

		return $content . $form;
	}

	/**
	 * Handle registration submission
	 */
	public static function handle_registration() {
		if ( ! isset( $_POST['registration_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['registration_nonce'] ) ), 'event_registration' ) ) {
			wp_die( esc_html__( 'Security check failed', 'wp-events' ) );
		}

		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$name     = isset( $_POST['reg_name'] ) ? sanitize_text_field( wp_unslash( $_POST['reg_name'] ) ) : '';
		$email    = isset( $_POST['reg_email'] ) ? sanitize_email( wp_unslash( $_POST['reg_email'] ) ) : '';
		$phone    = isset( $_POST['reg_phone'] ) ? preg_replace( '/[^0-9+\-\(\)\s]/', '', sanitize_text_field( wp_unslash( $_POST['reg_phone'] ) ) ) : '';
		$notes    = isset( $_POST['reg_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reg_notes'] ) ) : '';

		if ( ! $event_id || ! $name || ! $email ) {
			wp_die( esc_html__( 'Required fields missing', 'wp-events' ) );
		}

		// Validate that the event exists and is an event post type.
		$event = get_post( $event_id );
		if ( ! $event || 'event' !== $event->post_type ) {
			wp_die( esc_html__( 'Invalid event.', 'wp-events' ) );
		}

		// Ensure registration is enabled for this event.
		$enable_registration = get_post_meta( $event_id, 'enable_registration', true );
		if ( '1' !== $enable_registration ) {
			wp_die( esc_html__( 'Registration for this event is closed.', 'wp-events' ) );
		}

		// Check registration deadline.
		$registration_deadline = get_post_meta( $event_id, 'registration_deadline', true );
		if ( ! empty( $registration_deadline ) ) {
			$deadline_ts = strtotime( $registration_deadline );
			if ( $deadline_ts && current_time( 'timestamp' ) > $deadline_ts ) {
				wp_die( esc_html__( 'Registration for this event has ended.', 'wp-events' ) );
			}
		}

		// Check capacity again.
		$max_attendees = get_post_meta( $event_id, 'max_attendees', true );
		$registrations = self::get_registrations( $event_id );

		if ( $max_attendees > 0 && self::get_active_registration_count( $event_id ) >= $max_attendees ) {
			wp_die( esc_html__( 'Sorry, this event is now full', 'wp-events' ) );
		}

		// Rate limit: same IP + email within 10 minutes.
		$ip           = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$rate_key     = 'wpevents_reg_' . md5( $ip . $email );
		if ( get_transient( $rate_key ) ) {
			wp_die( esc_html__( 'Please wait before registering again.', 'wp-events' ) );
		}

		// Duplicate email check for this event.
		foreach ( $registrations as $existing ) {
			$existing_status = isset( $existing['status'] ) ? $existing['status'] : 'confirmed';
			if ( 'rejected' === $existing_status ) {
				continue;
			}
			if ( isset( $existing['email'] ) && strtolower( $existing['email'] ) === strtolower( $email ) ) {
				wp_die( esc_html__( 'This email is already registered for this event.', 'wp-events' ) );
			}
		}

		$require_approval = get_post_meta( $event_id, 'require_approval', true );

		// Save registration.
		$registration = array(
			'id'     => wp_generate_uuid4(),
			'name'   => $name,
			'email'  => $email,
			'phone'  => $phone,
			'notes'  => $notes,
			'date'   => current_time( 'mysql' ),
			'status' => '1' === $require_approval ? 'pending' : 'confirmed',
		);

		$registrations[] = $registration;
		update_post_meta( $event_id, 'event_registrations', $registrations );
		set_transient( $rate_key, 1, 10 * MINUTE_IN_SECONDS );

		self::send_registration_email( $event_id, $registration );

		// Redirect back with success message.
		wp_safe_redirect( add_query_arg( 'registered', '1', get_permalink( $event_id ) ) );
		exit;
	}

	/**
	 * Get registrations for an event
	 *
	 * @param int  $event_id    Event ID.
	 * @param bool $persist_ids Whether to store generated UUIDs.
	 * @return array
	 */
	public static function get_registrations( $event_id, $persist_ids = false ) {
		$registrations = get_post_meta( $event_id, 'event_registrations', true );
		if ( ! is_array( $registrations ) ) {
			return array();
		}

		$changed = false;
		foreach ( $registrations as $index => $reg ) {
			if ( empty( $reg['id'] ) ) {
				$registrations[ $index ]['id'] = wp_generate_uuid4();
				$changed                       = true;
			}
		}

		if ( $changed && $persist_ids ) {
			update_post_meta( $event_id, 'event_registrations', $registrations );
		}

		return $registrations;
	}

	/**
	 * Registrations that occupy capacity (pending + confirmed).
	 *
	 * @param int $event_id Event ID.
	 * @return array
	 */
	protected static function get_active_registrations( $event_id ) {
		$active = array();
		foreach ( self::get_registrations( $event_id ) as $reg ) {
			$status = isset( $reg['status'] ) ? $reg['status'] : 'confirmed';
			if ( 'rejected' !== $status ) {
				$active[] = $reg;
			}
		}
		return $active;
	}

	/**
	 * Count registrations that occupy capacity.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	protected static function get_active_registration_count( $event_id ) {
		return count( self::get_active_registrations( $event_id ) );
	}

	/**
	 * Admin URL for a registration CSV export.
	 *
	 * @param int $event_id Event ID.
	 * @return string
	 */
	public static function export_registrations_url( $event_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'wpevents_export_registrations',
					'event_id' => $event_id,
				),
				admin_url( 'admin-post.php' )
			),
			'wpevents_export_registrations'
		);
	}

	/**
	 * Stream registrations as CSV.
	 */
	public static function export_registrations_csv() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpevents_export_registrations' ) ) {
			wp_die( esc_html__( 'Security check failed', 'wp-events' ) );
		}

		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		if ( ! $event_id || 'event' !== get_post_type( $event_id ) ) {
			wp_die( esc_html__( 'Invalid event.', 'wp-events' ) );
		}

		if ( ! current_user_can( 'edit_post', $event_id ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this event.', 'wp-events' ) );
		}

		$registrations = self::get_registrations( $event_id );
		$filename      = 'registrations-' . $event_id . '-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			wp_die( esc_html__( 'Could not export registrations.', 'wp-events' ) );
		}

		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'Name', 'Email', 'Phone', 'Notes', 'Date', 'Status' ) );

		foreach ( $registrations as $reg ) {
			fputcsv(
				$out,
				array(
					isset( $reg['name'] ) ? $reg['name'] : '',
					isset( $reg['email'] ) ? $reg['email'] : '',
					isset( $reg['phone'] ) ? $reg['phone'] : '',
					isset( $reg['notes'] ) ? $reg['notes'] : '',
					isset( $reg['date'] ) ? $reg['date'] : '',
					isset( $reg['status'] ) ? $reg['status'] : 'confirmed',
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * Admin URL for a registration approve/reject/delete action.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $reg_id   Registration UUID.
	 * @param string $action   Action slug.
	 * @return string
	 */
	protected static function registration_action_url( $event_id, $reg_id, $action ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'              => 'wpevents_registration_action',
					'event_id'            => $event_id,
					'registration_id'     => $reg_id,
					'registration_action' => $action,
				),
				admin_url( 'admin-post.php' )
			),
			'wpevents_registration_action'
		);
	}

	/**
	 * Handle approve, reject, or delete from the event editor.
	 */
	public static function handle_registration_action() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpevents_registration_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'wp-events' ) );
		}

		$event_id = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
		$reg_id   = isset( $_GET['registration_id'] ) ? sanitize_text_field( wp_unslash( $_GET['registration_id'] ) ) : '';
		$action   = isset( $_GET['registration_action'] ) ? sanitize_key( wp_unslash( $_GET['registration_action'] ) ) : '';

		if ( ! $event_id || ! $reg_id || ! in_array( $action, array( 'approve', 'reject', 'delete' ), true ) ) {
			wp_die( esc_html__( 'Invalid registration action.', 'wp-events' ) );
		}

		if ( ! current_user_can( 'edit_post', $event_id ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this event.', 'wp-events' ) );
		}

		$registrations = self::get_registrations( $event_id, true );
		$found         = false;
		$updated_reg   = null;

		foreach ( $registrations as $index => $reg ) {
			if ( empty( $reg['id'] ) || $reg['id'] !== $reg_id ) {
				continue;
			}

			$found = true;
			if ( 'delete' === $action ) {
				unset( $registrations[ $index ] );
			} elseif ( 'approve' === $action ) {
				$registrations[ $index ]['status'] = 'confirmed';
				$updated_reg                       = $registrations[ $index ];
			} elseif ( 'reject' === $action ) {
				$registrations[ $index ]['status'] = 'rejected';
				$updated_reg                       = $registrations[ $index ];
			}
			break;
		}

		if ( ! $found ) {
			wp_die( esc_html__( 'Registration not found.', 'wp-events' ) );
		}

		update_post_meta( $event_id, 'event_registrations', array_values( $registrations ) );

		if ( $updated_reg ) {
			self::send_registration_email( $event_id, $updated_reg );
		}

		$redirect = add_query_arg(
			array(
				'post'          => $event_id,
				'action'        => 'edit',
				'wpevents_reg'  => $action,
			),
			admin_url( 'post.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Admin notice after a registration action.
	 */
	public static function registration_admin_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'event' !== $screen->id ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag after redirect.
		$action = isset( $_GET['wpevents_reg'] ) ? sanitize_key( wp_unslash( $_GET['wpevents_reg'] ) ) : '';
		if ( ! $action ) {
			return;
		}

		$messages = array(
			'approve' => __( 'Registration approved. A confirmation email was sent.', 'wp-events' ),
			'reject'  => __( 'Registration rejected. The attendee was notified.', 'wp-events' ),
			'delete'  => __( 'Registration deleted.', 'wp-events' ),
		);

		if ( ! isset( $messages[ $action ] ) ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $action ] ) . '</p></div>';
	}

	/**
	 * Email the attendee about registration status.
	 *
	 * @param int   $event_id     Event ID.
	 * @param array $registration Registration row.
	 */
	protected static function send_registration_email( $event_id, $registration ) {
		$email  = isset( $registration['email'] ) ? $registration['email'] : '';
		$status = isset( $registration['status'] ) ? $registration['status'] : 'confirmed';
		if ( ! $email || ! is_email( $email ) ) {
			return;
		}

		$title = get_the_title( $event_id );
		$start = get_post_meta( $event_id, 'event_start', true );

		if ( 'rejected' === $status ) {
			$subject = sprintf(
				/* translators: %s: event title */
				__( 'Registration update: %s', 'wp-events' ),
				$title
			);
			$message = sprintf(
				/* translators: %s: event title */
				__( 'Your registration for %s was not approved.', 'wp-events' ),
				$title
			);
		} elseif ( 'pending' === $status ) {
			$subject = sprintf(
				/* translators: %s: event title */
				__( 'Registration received: %s', 'wp-events' ),
				$title
			);
			$message  = sprintf(
				/* translators: %s: event title */
				__( 'Thank you for registering for %s!', 'wp-events' ),
				$title
			);
			$message .= "\n\n" . __( 'Your registration is pending approval. You will receive another email when it is reviewed.', 'wp-events' );
		} else {
			$subject = sprintf(
				/* translators: %s: event title */
				__( 'Registration Confirmation: %s', 'wp-events' ),
				$title
			);
			$message = sprintf(
				/* translators: %s: event title */
				__( 'Thank you for registering for %s!', 'wp-events' ),
				$title
			);
		}

		$message .= "\n\n" . __( 'Event Details:', 'wp-events' ) . "\n";
		$message .= __( 'Event:', 'wp-events' ) . ' ' . $title . "\n";
		if ( $start ) {
			$message .= __( 'Date:', 'wp-events' ) . ' ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $start ) ) . "\n";
		}
		$message .= __( 'URL:', 'wp-events' ) . ' ' . get_permalink( $event_id ) . "\n";

		wp_mail( $email, $subject, $message );
	}

	/**
	 * HTML status badge for an event (empty when scheduled/unset).
	 *
	 * @param int $post_id Event post ID.
	 * @return string Escaped HTML.
	 */
	public static function get_status_badge_html( $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || 'event' !== get_post_type( $post_id ) ) {
			return '';
		}

		$status = get_post_meta( $post_id, 'event_status', true );
		if ( ! $status || 'scheduled' === $status ) {
			return '';
		}

		$badges = array(
			'cancelled'   => array( 'event-cancelled', __( 'CANCELLED', 'wp-events' ) ),
			'postponed'   => array( 'event-postponed', __( 'POSTPONED', 'wp-events' ) ),
			'rescheduled' => array( 'event-rescheduled', __( 'RESCHEDULED', 'wp-events' ) ),
			'sold_out'    => array( 'event-sold-out', __( 'SOLD OUT', 'wp-events' ) ),
			'completed'   => array( 'event-completed', __( 'COMPLETED', 'wp-events' ) ),
		);

		if ( ! isset( $badges[ $status ] ) ) {
			return '';
		}

		return sprintf(
			' <span class="event-badge %s">%s</span>',
			esc_attr( $badges[ $status ][0] ),
			esc_html( $badges[ $status ][1] )
		);
	}

	/**
	 * Add status column to admin
	 */
	public static function add_status_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['event_status_col'] = __( 'Status', 'wp-events' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display status column
	 */
	public static function display_status_column( $column, $post_id ) {
		if ( 'event_status_col' === $column ) {
			$status = get_post_meta( $post_id, 'event_status', true );
			if ( ! $status ) {
				$status = 'scheduled';
			}

			$statuses = array(
				'scheduled'   => __( 'Scheduled', 'wp-events' ),
				'cancelled'   => __( 'Cancelled', 'wp-events' ),
				'postponed'   => __( 'Postponed', 'wp-events' ),
				'rescheduled' => __( 'Rescheduled', 'wp-events' ),
				'sold_out'    => __( 'Sold Out', 'wp-events' ),
				'completed'   => __( 'Completed', 'wp-events' ),
			);

			echo isset( $statuses[ $status ] ) ? esc_html( $statuses[ $status ] ) : esc_html( $status );
		}
	}

	/**
	 * Enqueue frontend styles
	 */
	public static function enqueue_frontend_styles() {
		if ( is_singular( 'event' ) || is_post_type_archive( 'event' ) || is_tax( array( 'event_category', 'event_tag' ) ) ) {
			// Register a lightweight plugin-owned stylesheet handle to reliably attach inline styles.
			wp_register_style( 'wpevents-frontend-styles', false, array(), null );
			wp_enqueue_style( 'wpevents-frontend-styles' );

			wp_add_inline_style(
				'wpevents-frontend-styles',
				'
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
            '
			);
		}
	}
}
