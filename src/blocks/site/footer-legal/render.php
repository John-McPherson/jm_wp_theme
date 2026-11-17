<?php
/**
 * Render the footer legal block: the copyright notice and the Legal menu.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the footer legal block.
 *
 * @var array{
 *     copyrightHolder?: string,
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

// Falls back to the site title, so the notice always names someone.
$copyright_holder = jmc_plain_text( $attributes['copyrightHolder'] ?? null );

if ( '' === $copyright_holder ) {
	$copyright_holder = jmc_plain_text( get_bloginfo( 'name' ) );
}

// The year is worked out on each render, so the notice never needs updating.
$copyright = trim( '© ' . wp_date( 'Y' ) . ' ' . $copyright_holder );

// The theme registers the `legal` menu (see inc/navigation.php).
$navigation = jmc_get_navigation_by_slug( slug: 'legal' );

$links = $navigation
	? get_navigation_links(
		menu_blocks: parse_blocks( $navigation->post_content )
	)
	: [];

$menu_title = $navigation
	? trim( (string) $navigation->post_title )
	: '';

// The theme author's credit; not editable, as it is not site content.
$credit_name  = 'John McPherson';
$credit_email = 'mcpherso@pm.me';

$wrapper_attributes = [
	'classes' => [
		'jmc-footer-legal',
		$attributes['className'] ?? null,
	],
];

$navigation_attributes = [
	'class'      => 'jmc-footer-legal__navigation',
	'aria-label' => '' !== $menu_title
		? $menu_title
		: __( 'Legal', 'jmc-theme' ),
];
?>
<div<?php jmc_the_attributes( $wrapper_attributes ); ?>>
	<div class="jmc-footer-legal__notices">
		<p class="jmc-footer-legal__credit">
			<?php
			printf(
				/* translators: %s: Linked name of the person who built the site. */
				esc_html__( 'Site created by %s', 'jmc-theme' ),
				'<a href="' . esc_url( 'mailto:' . $credit_email ) . '">' . esc_html( $credit_name ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL and name are escaped; only the anchor is markup.
			);
			?>
		</p>
		<p class="jmc-footer-legal__copyright"><?php echo esc_html( $copyright ); ?></p>
	</div>

	<?php if ( [] !== $links ) : ?>
		<nav<?php jmc_the_attributes( $navigation_attributes ); ?>>
			<?php
			jmc_component(
				name: 'link-list',
				args: [
					'links'       => $links,
					'block_class' => 'jmc-footer-legal',
				]
			);
			?>
		</nav>
	<?php endif; ?>
</div>
