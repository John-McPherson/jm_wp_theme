<?php
/**
 * WordPress test doubles for the services listing render tests.
 *
 * Extends the shared doubles in tests/phpunit/bootstrap.php (which read
 * `jmc_test_options`, `jmc_test_posts` and record `jmc_test_get_posts_queries`)
 * with the page lookups, meta, icon registry and request context the services
 * listing needs. Doubles are guarded so they never replace a definition
 * supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

require_once dirname( __DIR__, 3 ) . '/inc/helpers/services.php';
require_once __DIR__ . '/class-wp-icons-registry.php';

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

/**
 * Reset the WordPress fixture to an empty site.
 *
 * @param array<string, mixed> $overrides Fixture values to set.
 *
 * @return void
 */
function jmc_test_wp_fixture( array $overrides = [] ): void {
	$fixture = array_merge(
		[
			'options'       => [],
			'pages'         => [],
			'query_results' => [],
			'meta'          => [],
			'icons'         => [],
			'rest'          => false,
			'can_edit'      => false,
		],
		$overrides
	);

	// Pages and services share the bootstrap's post store, keyed by ID.
	$posts = [];

	foreach ( array_merge( $fixture['pages'], $fixture['query_results'] ) as $post ) {
		if ( is_object( $post ) && is_int( $post->ID ?? null ) ) {
			$posts[ $post->ID ] = $post;
		}
	}

	$GLOBALS['jmc_test_options']           = $fixture['options'];
	$GLOBALS['jmc_test_posts']             = $posts;
	$GLOBALS['jmc_test_get_posts_queries'] = [];
	$GLOBALS['jmc_test_wp']                = $fixture;
}

/**
 * Read a fixture value.
 *
 * @param string $key Fixture key.
 *
 * @return mixed Fixture value.
 */
function jmc_test_wp( string $key ): mixed {
	if ( 'last_query' === $key ) {
		$queries = $GLOBALS['jmc_test_get_posts_queries'] ?? [];

		return [] === $queries ? null : end( $queries );
	}

	return $GLOBALS['jmc_test_wp'][ $key ] ?? null;
}

if ( ! function_exists( 'get_page_by_path' ) ) {
	/**
	 * Find a page by slug in the fixture.
	 *
	 * @param string $page_path Page slug.
	 *
	 * @return object|null Page record.
	 */
	function get_page_by_path( string $page_path ): ?object {
		foreach ( (array) ( $GLOBALS['jmc_test_posts'] ?? [] ) as $page ) {
			if ( ( $page->post_name ?? '' ) === $page_path ) {
				return $page;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * Return the fixture permalink for a page.
	 *
	 * @param object $post Page record.
	 *
	 * @return string|false Permalink.
	 */
	function get_permalink( object $post ): string|false {
		return $post->permalink ?? false;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Read page meta from the fixture.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 *
	 * @return mixed Meta value.
	 */
	function get_post_meta( int $post_id, string $key ): mixed {
		return jmc_test_wp( 'meta' )[ $post_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'wp_is_serving_rest_request' ) ) {
	/**
	 * Whether the fixture request is a REST request.
	 *
	 * @return bool REST request state.
	 */
	function wp_is_serving_rest_request(): bool {
		return (bool) jmc_test_wp( 'rest' );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Whether the fixture user can edit content.
	 *
	 * @return bool Capability state.
	 */
	function current_user_can(): bool {
		return (bool) jmc_test_wp( 'can_edit' );
	}
}

if ( ! function_exists( 'render_block' ) ) {
	/**
	 * Render a core Icon block as a marker SVG for registered icons.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 *
	 * @return string Rendered markup.
	 */
	function render_block( array $block ): string {
		$icon = $block['attrs']['icon'] ?? '';

		if ( 'core/icon' !== $block['blockName']
			|| ! WP_Icons_Registry::get_instance()->is_registered( $icon )
		) {
			return '';
		}

		return sprintf(
			'<div class="wp-block-icon"><svg data-icon="%s" aria-hidden="true" focusable="false"></svg></div>',
			htmlspecialchars( $icon, ENT_QUOTES )
		);
	}
}
