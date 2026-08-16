<?php
/**
 * Lightweight bootstrap for unit tests that do not load WordPress.
 *
 * @package WPEvents
 */

if ( ! defined( 'DATE_ATOM' ) ) {
	define( 'DATE_ATOM', 'Y-m-d\TH:i:sP' );
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * @return DateTimeZone
	 */
	function wp_timezone() {
		return new DateTimeZone( 'UTC' );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * @param string             $format    Date format.
	 * @param int|null           $timestamp Unix timestamp.
	 * @param DateTimeZone|null  $timezone  Timezone.
	 * @return string
	 */
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		$dt = new DateTime( '@' . ( null === $timestamp ? time() : $timestamp ) );
		$dt->setTimezone( $timezone instanceof DateTimeZone ? $timezone : wp_timezone() );
		return $dt->format( $format );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param mixed $str Raw value.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param mixed $key Raw key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $maybeint Raw value.
	 * @return int
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param mixed $url Raw URL.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		$filtered = filter_var( (string) $url, FILTER_SANITIZE_URL );
		return is_string( $filtered ) ? $filtered : '';
	}
}

require_once dirname( __DIR__ ) . '/src/Sanitizer.php';
require_once dirname( __DIR__ ) . '/src/ICal.php';
require_once dirname( __DIR__ ) . '/src/AdditionalFeatures.php';
