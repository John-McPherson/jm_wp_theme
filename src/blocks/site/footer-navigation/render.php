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
	<ul class="jmc-footer-navigation__list">
		<?php
		foreach ( $links as $link_item ) :
			$link_attributes = [
				'href'  => $link_item['url'],
				'class' => 'jmc-footer-navigation__link',
			];

			// is_page( 0 ) matches every page, so custom links (no ID) must be skipped.
			if ( 0 < $link_item['id'] && is_page( $link_item['id'] ) ) {
				$link_attributes['aria-current'] = 'page';
			}
			?>
			<li class="jmc-footer-navigation__item">
				<a<?php jmc_the_attributes( $link_attributes ); ?>><?php echo esc_html( $link_item['title'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
