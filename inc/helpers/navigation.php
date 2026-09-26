<?php
/**
 * Helper functions for navigation.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);


/**
 * Locate and render a reusable theme component.
 *
 * @param array $menu_blocks The menu blocks to parse for navigation links.
 *
 * @return array<int,array{id:int,title:string,url:string}> An array of navigation links.]
 */
function get_navigation_links( $menu_blocks ) {
	$links = [];

	foreach ( $menu_blocks as $menu_block ) {
		if ( 'core/navigation-link' === $menu_block['blockName'] || 'core/navigation-submenu' === $menu_block['blockName'] ) {
			$links[]      = [
				'id'    => absint( $menu_block['attrs']['id'] ?? 0 ),
				'title' => $menu_block['attrs']['label'] ?? '',
				'url'   => $menu_block['attrs']['url'] ?? '',
			];
			$inner_blocks = $menu_block['innerBlocks'] ?? [];
			if ( is_array( $inner_blocks ) && [] !== $inner_blocks ) {
				$links = array_merge( $links, get_navigation_links( $inner_blocks ) );
			}
		}
	}

	return $links;
}
