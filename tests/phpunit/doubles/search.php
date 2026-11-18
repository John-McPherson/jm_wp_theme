<?php
/**
 * WordPress test doubles for the search tests.
 *
 * `is_search()` reads `$GLOBALS['jmc_test_is_search']`; `status_header()` and
 * `nocache_headers()` record their calls in `$GLOBALS['jmc_test_headers']`.
 * Doubles are guarded so they never replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'is_search' ) ) {
	/**
	 * Whether the request is a search.
	 *
	 * @return bool
	 */
	function is_search(): bool {
		return (bool) ( $GLOBALS['jmc_test_is_search'] ?? false );
	}
}

if ( ! function_exists( 'status_header' ) ) {
	/**
	 * Record the response status.
	 *
	 * @param int $code HTTP status code.
	 */
	function status_header( int $code ): void {
		$GLOBALS['jmc_test_headers'][] = "status {$code}";
	}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	/**
	 * Record that caching was turned off.
	 */
	function nocache_headers(): void {
		$GLOBALS['jmc_test_headers'][] = 'nocache';
	}
}
