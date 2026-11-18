<?php
/**
 * WordPress test doubles for block pattern registration.
 *
 * Record registered pattern categories in `$GLOBALS['jmc_test_pattern_categories']`.
 * Doubles are guarded so they never replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'register_block_pattern_category' ) ) {
	/**
	 * Record a registered pattern category.
	 *
	 * @param string              $category_name       Category slug.
	 * @param array<string,mixed> $category_properties Category properties.
	 *
	 * @return bool Always true.
	 */
	function register_block_pattern_category( string $category_name, array $category_properties ): bool {
		$GLOBALS['jmc_test_pattern_categories'][ $category_name ] = $category_properties;

		return true;
	}
}
