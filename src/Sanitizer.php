<?php
/**
 * Sanitize helpers for event meta.
 *
 * @package WPEvents
 */

namespace WPEvents;

/**
 * Shared sanitization callbacks.
 */
class Sanitizer {

	/**
	 * Sanitize to ISO 8601 datetime.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_iso8601( $value ) {
		$v  = (string) $value;
		$ts = strtotime( $v );
		if ( false === $ts ) {
			return '';
		}
		return wp_date( DATE_ATOM, $ts, wp_timezone() );
	}

	/**
	 * Sanitize to Y-m-d date.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_date( $value ) {
		$v  = (string) $value;
		$ts = strtotime( $v );
		if ( false === $ts ) {
			return '';
		}
		return wp_date( 'Y-m-d', $ts, wp_timezone() );
	}

	/**
	 * Sanitize phone number.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_phone( $value ) {
		$v = preg_replace( '/[^0-9+\-()\s]/', '', (string) $value );
		return trim( $v );
	}

	/**
	 * Sanitize array of IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	public static function sanitize_ids_array( $value ) {
		if ( is_string( $value ) ) {
			$maybe = json_decode( $value, true );
			if ( is_array( $maybe ) ) {
				$value = $maybe;
			}
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $value ) ) );
	}

	/**
	 * Sanitize array of URLs.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	public static function sanitize_urls_array( $value ) {
		if ( is_string( $value ) ) {
			$maybe = json_decode( $value, true );
			if ( is_array( $maybe ) ) {
				$value = $maybe;
			}
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'esc_url_raw', $value ) ) );
	}

	/**
	 * Sanitize recurrence type against allowlist.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_recurrence_type( $value ) {
		$allowed = array( 'daily', 'weekly', 'monthly', 'yearly', 'custom', '' );
		$v       = sanitize_text_field( (string) $value );
		return in_array( $v, $allowed, true ) ? $v : '';
	}

	/**
	 * Sanitize event price.
	 *
	 * @param mixed $value Raw value.
	 * @return float|string
	 */
	public static function sanitize_price( $value ) {
		return is_numeric( $value ) ? (float) $value : '';
	}

	/**
	 * Sanitize currency code (3-letter).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_currency( $value ) {
		$v = strtoupper( preg_replace( '/[^A-Z]/', '', (string) $value ) );
		return substr( $v, 0, 3 );
	}
}
