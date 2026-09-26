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
 * @var WP_Block $block
 */

$navigation_id = absint( $attributes['navigationId'] ?? 0 );

$navigation = get_post( $navigation_id );

if ( ! $navigation || 'wp_navigation' !== $navigation->post_type ) {
	return;
}

$menu_blocks = parse_blocks( $navigation->post_content );


$links = [];

foreach ( $menu_blocks as $menu_block ) {
	if ( 'core/navigation-link' === $menu_block['blockName'] || 'core/navigation-submenu' === $menu_block['blockName'] ) {
		$links[] = [
			'id'    => absint( $menu_block['attrs']['id'] ?? 0 ),
			'title' => $menu_block['attrs']['label'] ?? '',
			'url'   => $menu_block['attrs']['url'] ?? '',
		];


	}
}

?>

<nav class="jmc-navigation no-js" aria-label="<?php echo esc_attr( $navigation->post_title ); ?>">
	<button class="jmc-navigation__toggle" aria-expanded="true" aria-controls="jmc-navigation__list">
		<span class="jmc-navigation__toggle-open" aria-hidden="true">menu</span>
		<span class="jmc-navigation__toggle-close" aria-hidden="true">close</span>
		<span class="sro"><?php esc_html_e( 'Toggle navigation menu', 'jmc-theme' ); ?></span>
	</button>
	<ul  id="jmc-navigation__list" class="jmc-navigation__list">
		<?php foreach ( $links as $link_item ) : ?>
			<li class="jmc-navigation__item">
				<a href="<?php echo esc_url( $link_item['url'] ); ?>" class="jmc-navigation__link">
					<?php echo esc_html( $link_item['title'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>