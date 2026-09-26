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

$naivigation_button_attributes = [
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
	<button <?php jmc_the_attributes( $naivigation_button_attributes ); ?>>
		<span class="jmc-navigation__toggle-open" aria-hidden="true">menu</span>
		<span class="jmc-navigation__toggle-close" aria-hidden="true">close</span>
		<span class="sro"><?php esc_html_e( 'Toggle navigation menu', 'jmc-theme' ); ?></span>
	</button>
	<ul <?php jmc_the_attributes( $navigation_list_attributes ); ?>>
		<?php foreach ( $links as $link_item ) : ?>
			
			<li class="jmc-navigation__item">
				<a href="<?php echo esc_url( $link_item['url'] ); ?>" class="jmc-navigation__link" aria-current="<?php echo esc_attr( is_page( $link_item['id'] ) ? 'page' : 'false' ); ?>">
					<?php echo esc_html( $link_item['title'] ); ?> 
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>