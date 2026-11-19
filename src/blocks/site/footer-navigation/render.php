<?php
/**
 * Render the footer navigation block.
 *
 * Top-level links render as a list. A top-level submenu becomes a group: its
 * parent is a heading (linked when it has a URL) and its links a list named
 * after that heading. Runs of plain links between groups stay in menu order.
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

$items = jmc_get_navigation_tree(
	menu_blocks: parse_blocks( $navigation->post_content )
);

if ( [] === $items ) {
	return;
}

/*
 * Split the menu into sections, in menu order: each submenu is a group, and
 * each run of plain links between groups is one list. A submenu parent with
 * no usable links already arrives as a plain link (or not at all).
 */
$sections = [];

foreach ( $items as $item ) {
	if ( [] !== $item['children'] ) {
		$sections[] = $item;
		continue;
	}

	$last = array_key_last( $sections );

	if ( null !== $last && isset( $sections[ $last ]['links'] ) ) {
		$sections[ $last ]['links'][] = $item;
	} else {
		$sections[] = [ 'links' => [ $item ] ];
	}
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
	<?php foreach ( $sections as $section ) : ?>
		<?php if ( isset( $section['links'] ) ) : ?>
			<?php
			jmc_component(
				name: 'link-list',
				args: [
					'links'       => $section['links'],
					'block_class' => 'jmc-footer-navigation',
				]
			);
			?>
		<?php else : ?>
			<?php
			$heading_id = wp_unique_id( 'jmc-footer-navigation__heading-' );

			$heading_link_attributes = [
				'href'         => $section['url'],
				'class'        => 'jmc-footer-navigation__heading-link',
				// is_page( 0 ) matches every page, so custom links (no ID) are never current.
				'aria-current' => 0 < $section['id'] && is_page( $section['id'] ) ? 'page' : null,
			];
			?>
			<div class="jmc-footer-navigation__group">
				<h2 id="<?php echo esc_attr( $heading_id ); ?>" class="jmc-footer-navigation__heading">
					<?php if ( '' !== $section['url'] ) : ?>
						<a<?php jmc_the_attributes( $heading_link_attributes ); ?>><?php echo esc_html( $section['title'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $section['title'] ); ?>
					<?php endif; ?>
				</h2>
				<?php
				jmc_component(
					name: 'link-list',
					args: [
						'links'       => $section['children'],
						'block_class' => 'jmc-footer-navigation',
						'labelled_by' => $heading_id,
					]
				);
				?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
