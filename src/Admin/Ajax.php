<?php
/**
 * Admin AJAX handlers.
 *
 * @package WPEvents
 */

namespace WPEvents\Admin;

/**
 * Featured image AJAX for venues/events.
 */
class Ajax {

	/**
	 * Register AJAX hooks.
	 */
	public static function register() {
		add_action( 'wp_ajax_set_venue_featured_image', array( __CLASS__, 'ajax_set_venue_featured_image' ) );
		add_action( 'wp_ajax_remove_venue_featured_image', array( __CLASS__, 'ajax_remove_venue_featured_image' ) );
		add_action( 'wp_ajax_wpevents_create_venue', array( __CLASS__, 'ajax_create_venue' ) );
		add_action( 'wp_ajax_wpevents_create_organizer', array( __CLASS__, 'ajax_create_organizer' ) );
	}

	/**
	 * Set venue/event featured image via AJAX.
	 */
	public static function ajax_set_venue_featured_image() {
		check_ajax_referer( 'wp_events_admin_nonce', 'nonce' );

		$post_id       = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'venue', 'event' ), true ) ) {
			wp_send_json_error( 'Invalid post', 400 );
		}

		if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
			wp_send_json_error( 'Invalid image attachment', 400 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Permission denied', 403 );
		}

		$result = set_post_thumbnail( $post_id, $attachment_id );

		if ( $result ) {
			wp_send_json_success();
		}

		wp_send_json_error( 'Failed to set featured image' );
	}

	/**
	 * Remove venue/event featured image via AJAX.
	 */
	public static function ajax_remove_venue_featured_image() {
		check_ajax_referer( 'wp_events_admin_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! in_array( get_post_type( $post_id ), array( 'venue', 'event' ), true ) ) {
			wp_send_json_error( 'Invalid post', 400 );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( 'Permission denied', 403 );
		}

		$result = delete_post_thumbnail( $post_id );

		if ( $result ) {
			wp_send_json_success();
		}

		wp_send_json_error( 'Failed to remove featured image' );
	}

	/**
	 * Create a venue from the event editor and return it for selection.
	 */
	public static function ajax_create_venue() {
		self::assert_can_create_related();

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			wp_send_json_error( __( 'Enter a name.', 'wp-events' ), 400 );
		}

		$meta = array(
			'venue_address'     => isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '',
			'venue_postal_code' => isset( $_POST['postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['postal_code'] ) ) : '',
			'venue_city'        => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
		);

		$post_id = self::insert_related_post( 'venue', $title, $meta );
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( $post_id->get_error_message(), 400 );
		}

		wp_send_json_success(
			array(
				'id'      => $post_id,
				'title'   => $title,
				'editUrl' => get_edit_post_link( $post_id, 'raw' ),
			)
		);
	}

	/**
	 * Create an organizer from the event editor and return it for selection.
	 */
	public static function ajax_create_organizer() {
		self::assert_can_create_related();

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			wp_send_json_error( __( 'Enter a name.', 'wp-events' ), 400 );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( '' !== $email && ! is_email( $email ) ) {
			wp_send_json_error( __( 'Enter a valid email address.', 'wp-events' ), 400 );
		}

		$meta = array(
			'organizer_email' => $email,
			'organizer_phone' => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		);

		$post_id = self::insert_related_post( 'organizer', $title, $meta );
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( $post_id->get_error_message(), 400 );
		}

		wp_send_json_success(
			array(
				'id'      => $post_id,
				'title'   => $title,
				'editUrl' => get_edit_post_link( $post_id, 'raw' ),
			)
		);
	}

	/**
	 * Require a logged-in user who may edit events or publish posts.
	 */
	private static function assert_can_create_related() {
		check_ajax_referer( 'wp_events_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_events' ) && ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( __( 'Permission denied', 'wp-events' ), 403 );
		}
	}

	/**
	 * Insert a published venue or organizer.
	 *
	 * Event editors do not have the generic post capabilities these types use,
	 * so those capabilities are granted only for the insert itself.
	 *
	 * @param string               $post_type venue|organizer.
	 * @param string               $title     Post title.
	 * @param array<string,string> $meta      Meta values. Empty values are skipped.
	 * @return int|\WP_Error
	 */
	private static function insert_related_post( $post_type, $title, array $meta ) {
		$needs_grant = ! current_user_can( 'edit_posts' ) || ! current_user_can( 'publish_posts' );
		if ( $needs_grant ) {
			add_filter( 'user_has_cap', array( __CLASS__, 'grant_related_post_caps' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);

		if ( $needs_grant ) {
			remove_filter( 'user_has_cap', array( __CLASS__, 'grant_related_post_caps' ) );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		foreach ( $meta as $key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( (int) $post_id, $key, $value );
			}
		}

		return (int) $post_id;
	}

	/**
	 * Temporarily allow the current event editor to create a related post.
	 *
	 * @param array<string,bool> $allcaps All capabilities for the user.
	 * @return array<string,bool>
	 */
	public static function grant_related_post_caps( $allcaps ) {
		$allcaps['edit_posts']    = true;
		$allcaps['publish_posts'] = true;
		return $allcaps;
	}
}
