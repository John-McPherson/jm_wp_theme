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

/**
 * Collect the IDs of the posts and pages a menu links to.
 *
 * Covers links and submenus at any depth; custom and taxonomy links are
 * ignored.
 *
 * @param array<int, array<string, mixed>> $menu_blocks The blocks to search.
 *
 * @return int[] Linked post IDs, in menu order.
 */
function jmc_get_navigation_post_ids( array $menu_blocks ): array {
	$ids = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( ! is_array( $menu_block ) ) {
			continue;
		}

		$attributes = is_array( $menu_block['attrs'] ?? null ) ? $menu_block['attrs'] : [];
		$id         = absint( $attributes['id'] ?? 0 );

		if (
			in_array( $menu_block['blockName'] ?? '', [ 'core/navigation-link', 'core/navigation-submenu' ], true )
			&& 'taxonomy' !== ( $attributes['kind'] ?? '' )
			&& 0 < $id
		) {
			$ids[] = $id;
		}

		$inner_blocks = $menu_block['innerBlocks'] ?? [];

		if ( is_array( $inner_blocks ) && [] !== $inner_blocks ) {
			$ids = array_merge( $ids, jmc_get_navigation_post_ids( $inner_blocks ) );
		}
	}

	return $ids;
}

/**
 * Drop links to unpublished posts and point the rest at their permalinks.
 *
 * A link or submenu to a post or page (`kind` of `post-type` with an `id`)
 * is removed, with anything inside it, when the post is missing or not
 * published, as core's Navigation Link block does. A published post's link
 * uses its current permalink, so slug changes are followed. Other links
 * are kept unchanged.
 *
 * @param array<int, array<string, mixed>> $menu_blocks The blocks to filter.
 *
 * @return array<int, array<string, mixed>> The filtered blocks.
 */
function jmc_get_published_navigation_blocks( array $menu_blocks ): array {
	$published = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( ! is_array( $menu_block ) ) {
			continue;
		}

		$attributes = is_array( $menu_block['attrs'] ?? null ) ? $menu_block['attrs'] : [];
		$id         = absint( $attributes['id'] ?? 0 );

		if (
			in_array( $menu_block['blockName'] ?? '', [ 'core/navigation-link', 'core/navigation-submenu' ], true )
			&& 'post-type' === ( $attributes['kind'] ?? '' )
			&& 0 < $id
		) {
			$post = get_post( $id );

			if ( null === $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$url = get_permalink( $post );

			if ( is_string( $url ) && '' !== $url ) {
				$menu_block['attrs']['url'] = $url;
			}
		}

		if ( is_array( $menu_block['innerBlocks'] ?? null ) ) {
			$menu_block['innerBlocks'] = jmc_get_published_navigation_blocks( $menu_block['innerBlocks'] );
		}

		$published[] = $menu_block;
	}

	return $published;
}

/**
 * Whether a parsed block is a Navigation link to one of the given posts.
 *
 * Taxonomy links are never matched, as their IDs are term IDs.
 *
 * @param mixed $menu_block The parsed block.
 * @param int[] $post_ids   Post IDs to match.
 *
 * @return bool Whether the block links to one of the posts.
 */
function jmc_is_navigation_post_link( mixed $menu_block, array $post_ids ): bool {
	if ( ! is_array( $menu_block ) || 'core/navigation-link' !== ( $menu_block['blockName'] ?? '' ) ) {
		return false;
	}

	$attributes = is_array( $menu_block['attrs'] ?? null ) ? $menu_block['attrs'] : [];

	return 'taxonomy' !== ( $attributes['kind'] ?? '' )
		&& in_array( absint( $attributes['id'] ?? 0 ), $post_ids, true );
}

/**
 * Remove the Navigation links to the given posts, at any depth.
 *
 * Only `core/navigation-link` blocks are removed. Submenus, other links and
 * everything else are kept in order, and a submenu's `innerContent` is kept
 * in step with its remaining links so the menu serialises cleanly.
 *
 * @param array<int, mixed> $menu_blocks The blocks to filter.
 * @param int[]             $post_ids    Post IDs whose links are removed.
 *
 * @return array<int, mixed> The remaining blocks.
 */
function jmc_remove_navigation_post_links( array $menu_blocks, array $post_ids ): array {
	$kept = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( jmc_is_navigation_post_link( menu_block: $menu_block, post_ids: $post_ids ) ) {
			continue;
		}

		$inner_blocks = is_array( $menu_block ) ? ( $menu_block['innerBlocks'] ?? null ) : null;

		if ( is_array( $inner_blocks ) && [] !== $inner_blocks ) {
			$keep = array_map(
				static fn ( mixed $inner_block ): bool => ! jmc_is_navigation_post_link( menu_block: $inner_block, post_ids: $post_ids ),
				array_values( $inner_blocks )
			);

			$menu_block['innerBlocks'] = jmc_remove_navigation_post_links( menu_blocks: $inner_blocks, post_ids: $post_ids );

			// Each null chunk in innerContent stands for the next inner block.
			if ( is_array( $menu_block['innerContent'] ?? null ) ) {
				$index = 0;

				$menu_block['innerContent'] = array_values(
					array_filter(
						$menu_block['innerContent'],
						static function ( mixed $chunk ) use ( &$index, $keep ): bool {
							return null !== $chunk || ( $keep[ $index++ ] ?? true );
						}
					)
				);
			}
		}

		$kept[] = $menu_block;
	}

	return $kept;
}
