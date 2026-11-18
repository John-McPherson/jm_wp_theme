<?php
/**
 * Breadcrumbs: the hero trail starts with a home icon instead of "Home".
 *
 * The hero renders core Breadcrumbs without its home item (see
 * src/blocks/layouts/hero/render.php). This filter adds the home link back as
 * an icon, on the frontend and in the editor preview alike.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Class that marks the trail rendered by the hero.
 */
const JMC_HERO_BREADCRUMBS_CLASS = 'jmc-hero__breadcrumbs';

/**
 * Prepend a home icon link to the hero's breadcrumb trail.
 *
 * Other Breadcrumbs blocks, and empty trails, are returned unchanged.
 *
 * @param string               $block_content Rendered core Breadcrumbs markup.
 * @param array<string, mixed> $block         Parsed block.
 *
 * @return string Trail markup.
 */
function jmc_breadcrumbs_home_icon( string $block_content, array $block ): string {
	$class_name = $block['attrs']['className'] ?? '';
	$classes    = is_string( $class_name ) ? preg_split( '/\s+/', $class_name, -1, PREG_SPLIT_NO_EMPTY ) : [];

	if ( ! in_array( JMC_HERO_BREADCRUMBS_CLASS, (array) $classes, true )
		|| ! str_contains( $block_content, '<ol>' )
	) {
		return $block_content;
	}

	$home_item = sprintf(
		'<li class="%1$s-home"><a href="%2$s" aria-label="%3$s">%4$s</a></li>',
		esc_attr( JMC_HERO_BREADCRUMBS_CLASS ),
		esc_url( home_url( '/' ) ),
		esc_attr__( 'Home', 'jmc-theme' ),
		wp_get_icon( 'jmc/house', [ 'class' => JMC_HERO_BREADCRUMBS_CLASS . '-icon' ] )
	);

	// Core opens the list with a bare `<ol>`; add the home item first.
	$position = strpos( $block_content, '<ol>' ) + strlen( '<ol>' );

	return substr_replace( $block_content, $home_item, $position, 0 );
}

add_filter( 'render_block_core/breadcrumbs', 'jmc_breadcrumbs_home_icon', 10, 2 );
