<?php
/**
 * Helper functions for navigation.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Find a navigation menu (`wp_navigation` post) by its slug.
 *
 * Menus the theme registers (see `jmc_navigation_menus()`) have fixed slugs,
 * so templates can reference them in every environment, unlike post IDs.
 *
 * @param string   $slug        Menu slug, such as `legal`.
 * @param string[] $post_status Statuses to match; published menus by default.
 *
 * @return WP_Post|null The menu, or null when none matches.
 */
function jmc_get_navigation_by_slug(
	string $slug,
	array $post_status = [ 'publish' ]
): ?object {
	$slug = sanitize_key( $slug );

	if ( '' === $slug ) {
		return null;
	}

	$menus = get_posts(
		[
			'post_type'      => 'wp_navigation',
			'name'           => $slug,
			'post_status'    => $post_status,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		]
	);

	return $menus[0] ?? null;
}

/**
 * Normalise supported Navigation blocks into a flat link collection.
 *
 * @param array<int, array<string, mixed>> $menu_blocks The blocks to parse.
 *
 * @return array<int, array{id:int,title:string,url:string}> Navigation links.
 */
function get_navigation_links( array $menu_blocks ): array {
	$links = [];

	foreach ( $menu_blocks as $menu_block ) {
		$block_name = $menu_block['blockName'] ?? '';
		$attributes = $menu_block['attrs'] ?? [];

		if ( ! is_array( $attributes ) ) {
			$attributes = [];
		}

		if ( in_array( $block_name, [ 'core/navigation-link', 'core/navigation-submenu' ], true ) ) {
			$title = $attributes['label'] ?? '';
			$url   = $attributes['url'] ?? '';

			if ( is_string( $title ) && is_string( $url ) ) {
				$title = trim( $title );
				$url   = esc_url_raw( $url );

				if ( '' !== $title && '' !== $url ) {
					$links[] = [
						'id'    => absint( $attributes['id'] ?? 0 ),
						'title' => $title,
						'url'   => $url,
					];
				}
			}
		}

		$inner_blocks = $menu_block['innerBlocks'] ?? [];

		if ( is_array( $inner_blocks ) && [] !== $inner_blocks ) {
			$links = array_merge( $links, get_navigation_links( $inner_blocks ) );
		}
	}

	return $links;
}
