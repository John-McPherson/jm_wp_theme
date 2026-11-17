<?php
/**
 * Render the navigation block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the navigation block.
 *
 * @var array{
 * } $attributes
 * @var string   $content Rendered inner blocks (the call to action).
 * @var WP_Block $block
 */

$navigation_id = absint( maybeint: $attributes['navigationId'] ?? 0 );

$navigation = get_post( post: $navigation_id );

if ( ! $navigation || 'wp_navigation' !== $navigation->post_type ) {
	return;
}

$menu_blocks = parse_blocks( content: $navigation->post_content );


$links = get_navigation_links( menu_blocks: $menu_blocks );

if ( [] === $links ) {
	return;
}

$list_id = wp_unique_id( 'jmc-navigation__list-' );

$navigation_attributes = [
	'classes'    => [
		'jmc-navigation',
		'no-js',
	],
	'aria-label' => esc_attr( $navigation->post_title ),
];

$navigation_button_attributes = [
	'classes'       => [
		'jmc-navigation__toggle',
	],
	'aria-expanded' => 'false',
	'aria-controls' => $list_id,
];

$navigation_list_attributes = [
	'id'      => $list_id,
	'classes' => [
		'jmc-navigation__list',
	],
];


?>

<nav <?php jmc_the_attributes( $navigation_attributes ); ?>>
	<button <?php jmc_the_attributes( $navigation_button_attributes ); ?>>
		<span class="jmc-navigation__toggle-open" aria-hidden="true">menu</span>
		<span class="jmc-navigation__toggle-close" aria-hidden="true">close</span>
		<span class="sro"><?php esc_html_e( 'Toggle navigation menu', 'jmc-theme' ); ?></span>
	</button>
	<ul <?php jmc_the_attributes( $navigation_list_attributes ); ?>>
		<?php
		foreach ( $links as $link_item ) :

			$link_attributes = [
				'href'  => esc_url( $link_item['url'] ),
				'class' => 'jmc-navigation__link',
			];

			// is_page( 0 ) matches every page, so custom links (no ID) must be skipped.
			if ( $link_item['id'] > 0 && is_page( $link_item['id'] ) ) {
				$link_attributes['aria-current'] = 'page';
			}
			?>
			
			<li class="jmc-navigation__item">
				<a <?php jmc_the_attributes( $link_attributes ); ?>>
					<?php echo esc_html( $link_item['title'] ); ?> 
				</a>
			</li>
		<?php endforeach; ?>

		<?php if ( '' !== trim( $content ) ) : ?>
			<li class="jmc-navigation__item jmc-navigation__item--cta">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
				echo $content;
				?>
			</li>
		<?php endif; ?>
	</ul>
</nav>