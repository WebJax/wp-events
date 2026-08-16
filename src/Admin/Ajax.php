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
		add_action( 'wp_ajax_wpevents_search_products', array( __CLASS__, 'ajax_search_products' ) );
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
	 * Search WooCommerce products for the ticket picker.
	 */
	public static function ajax_search_products() {
		check_ajax_referer( 'wp_events_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_events' ) ) {
			wp_send_json_error( 'Permission denied', 403 );
		}

		$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
		if ( strlen( $q ) < 2 ) {
			wp_send_json_success( array() );
		}

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => 20,
				's'              => $q,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => array( 'publish', 'private' ),
				'no_found_rows'  => true,
			)
		);

		$results = array();
		foreach ( $products as $product ) {
			$results[] = array(
				'id'    => (int) $product->ID,
				'title' => $product->post_title,
			);
		}

		wp_send_json_success( $results );
	}
}
