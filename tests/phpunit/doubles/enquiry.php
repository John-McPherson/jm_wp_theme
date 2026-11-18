<?php
/**
 * WordPress test doubles for the enquiry form render tests.
 *
 * Loads the services doubles (the form lists the Services pages) and the
 * submission code that supplies the form's tokens, and adds the URL, nonce
 * and JSON functions the form needs. Doubles are guarded so they never
 * replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

require_once __DIR__ . '/services.php';
require_once dirname( __DIR__, 3 ) . '/inc/enquiry-submission.php';

if ( ! function_exists( 'wp_create_nonce' ) ) {
	/**
	 * Create a nonce.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return string A predictable test nonce.
	 */
	function wp_create_nonce( string $action ): string {
		return 'nonce-' . $action;
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	/**
	 * Retrieve a salt.
	 *
	 * @param string $scheme Salt scheme.
	 *
	 * @return string A fixed test salt.
	 */
	function wp_salt( string $scheme = 'auth' ): string {
		return 'salt-' . $scheme;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Retrieve an admin URL.
	 *
	 * @param string $path Path relative to the admin URL.
	 *
	 * @return string Admin URL on the example host.
	 */
	function admin_url( string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	/**
	 * Retrieve a REST API URL.
	 *
	 * @param string $path REST route.
	 *
	 * @return string REST URL on the example host.
	 */
	function rest_url( string $path = '' ): string {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'get_privacy_policy_url' ) ) {
	/**
	 * Retrieve the privacy policy URL.
	 *
	 * @return string The `privacy_url` fixture, or an empty string.
	 */
	function get_privacy_policy_url(): string {
		return (string) ( jmc_test_wp( 'privacy_url' ) ?? '' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Encode a value as JSON.
	 *
	 * @param mixed $value Value to encode.
	 *
	 * @return string|false JSON, or false on failure.
	 */
	function wp_json_encode( mixed $value ): string|false {
		return json_encode( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test double for wp_json_encode().
	}
}
