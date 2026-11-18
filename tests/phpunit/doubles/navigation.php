<?php
/**
 * WordPress test doubles for the navigation tests.
 *
 * `do_action()` records each call in `$GLOBALS['jmc_test_fired_actions']` as a
 * `[ hook_name, args ]` pair; `wp_unique_id()` counts up from
 * `$GLOBALS['jmc_test_unique_id']`. Doubles are guarded so they never replace a
 * definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Record a fired action.
	 *
	 * @param string $hook_name Action name.
	 * @param mixed  ...$args   Arguments passed to the action.
	 *
	 * @return void
	 */
	function do_action( string $hook_name, mixed ...$args ): void {
		$GLOBALS['jmc_test_fired_actions'][] = [ $hook_name, $args ];
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	/**
	 * Return a unique ID for the request.
	 *
	 * Counts up from `$GLOBALS['jmc_test_unique_id']` so tests can reset it.
	 *
	 * @param string $prefix ID prefix.
	 *
	 * @return string
	 */
	function wp_unique_id( string $prefix = '' ): string {
		$GLOBALS['jmc_test_unique_id'] = ( $GLOBALS['jmc_test_unique_id'] ?? 0 ) + 1;

		return $prefix . $GLOBALS['jmc_test_unique_id'];
	}
}
