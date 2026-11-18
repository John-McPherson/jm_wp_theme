<?php
/**
 * Render the footer navigation block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the footer navigation block.
 *
 * @var array{
 *     navigationId?: int,
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

$navigation_id = jmc_args_int(
	args: $attributes,
	key: 'navigationId',
	fallback_value: 0,
	min: 1
);

$navigation = 0 < $navigation_id
	? get_post( $navigation_id )
	: null;

if ( ! $navigation || 'wp_navigation' !== $navigation->post_type ) {
	return;
}

$links = get_navigation_links(
	menu_blocks: parse_blocks( $navigation->post_content )
);

if ( [] === $links ) {
	return;
}

$menu_title = trim( (string) $navigation->post_title );

$navigation_attributes = [
	'classes'    => [
		'jmc-footer-navigation',
		$attributes['className'] ?? null,
	],
	'aria-label' => '' !== $menu_title
		? $menu_title
		: __( 'Footer', 'jmc-theme' ),
];
?>
<nav<?php jmc_the_attributes( $navigation_attributes ); ?>>
	<?php
	jmc_component(
		name: 'link-list',
		args: [
			'links'       => $links,
			'block_class' => 'jmc-footer-navigation',
		]
	);
	?>
</nav>
