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
}
