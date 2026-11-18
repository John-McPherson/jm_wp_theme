<?php
/**
 * WordPress test doubles for template filters and routing.
 *
 * Record added filters in `$GLOBALS['jmc_test_filters']` and return the
 * queried object from `$GLOBALS['jmc_test_queried_object']`. Doubles are
 * guarded so they never replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Record an added filter.
	 *
	 * @param string   $hook_name     Filter name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Number of arguments the callback accepts.
	 *
	 * @return bool Always true.
	 */
	function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
		$GLOBALS['jmc_test_filters'][ $hook_name ][] = [ $callback, $priority, $accepted_args ];

		return true;
	}
}

if ( ! function_exists( 'get_queried_object' ) ) {
	/**
	 * Return the queried object from `$GLOBALS['jmc_test_queried_object']`.
	 *
	 * @return object|null The queried object.
	 */
	function get_queried_object(): ?object {
		return $GLOBALS['jmc_test_queried_object'] ?? null;
	}
}
