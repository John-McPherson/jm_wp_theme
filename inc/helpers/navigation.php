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

/**
 * Normalise one Navigation link or submenu block into a navigation item.
 *
 * @param array<string, mixed> $menu_block The parsed block.
 *
 * @return array{id:int,title:string,url:string}|null The item, or null when the
 *                                                    block is unsupported or has no label.
 */
function jmc_get_navigation_item( array $menu_block ): ?array {
	$block_name = $menu_block['blockName'] ?? '';
	$attributes = $menu_block['attrs'] ?? [];

	if ( ! in_array( $block_name, [ 'core/navigation-link', 'core/navigation-submenu' ], true ) ) {
		return null;
	}

	if ( ! is_array( $attributes ) ) {
		$attributes = [];
	}

	$title = $attributes['label'] ?? '';
	$url   = $attributes['url'] ?? '';

	if ( ! is_string( $title ) || '' === trim( $title ) ) {
		return null;
	}

	return [
		'id'    => absint( $attributes['id'] ?? 0 ),
		'title' => trim( $title ),
		'url'   => is_string( $url ) ? esc_url_raw( $url ) : '',
	];
}

/**
 * Collect the links inside a submenu, flattening anything nested deeper.
 *
 * Only one submenu level is supported. Links from deeper branches are kept,
 * in menu order, as siblings in the submenu so they stay reachable, and
 * `jmc_navigation_unsupported_depth` fires so the problem can be logged.
 *
 * @param array<int, array<string, mixed>> $menu_blocks The submenu's inner blocks.
 *
 * @return array<int, array{id:int,title:string,url:string}> Submenu links.
 */
function jmc_get_navigation_children( array $menu_blocks ): array {
	$children = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( ! is_array( $menu_block ) ) {
			continue;
		}

		$item = jmc_get_navigation_item( $menu_block );

		if ( null !== $item && '' !== $item['url'] ) {
			$children[] = $item;
		}

		$inner_blocks = $menu_block['innerBlocks'] ?? [];

		if ( null === $item || ! is_array( $inner_blocks ) || [] === $inner_blocks ) {
			continue;
		}

		/**
		 * Fires when a navigation menu nests deeper than the theme supports.
		 *
		 * The deeper links are flattened into the parent submenu.
		 *
		 * @param array<string, mixed> $menu_block The block whose children are too deep.
		 */
		do_action( 'jmc_navigation_unsupported_depth', $menu_block );

		$children = array_merge( $children, get_navigation_links( $inner_blocks ) );
	}

	return $children;
}

/**
 * Normalise Navigation blocks into primary items with one optional submenu level.
 *
 * A primary item needs a label, and a URL unless it has a submenu: a submenu
 * parent without a URL is a toggle only. Submenu links always need a URL.
 * Unsupported blocks are skipped.
 *
 * @param array<int, array<string, mixed>> $menu_blocks The blocks to parse.
 *
 * @return array<int, array{
 *     id:int,
 *     title:string,
 *     url:string,
 *     children:array<int, array{id:int,title:string,url:string}>
 * }> Navigation items.
 */
function jmc_get_navigation_tree( array $menu_blocks ): array {
	$items = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( ! is_array( $menu_block ) ) {
			continue;
		}

		$item = jmc_get_navigation_item( $menu_block );

		if ( null === $item ) {
			continue;
		}

		$inner_blocks     = $menu_block['innerBlocks'] ?? [];
		$item['children'] = is_array( $inner_blocks )
			? jmc_get_navigation_children( $inner_blocks )
			: [];

		if ( '' === $item['url'] && [] === $item['children'] ) {
			continue;
		}

		$items[] = $item;
	}

	return $items;
}
