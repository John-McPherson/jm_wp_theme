<?php
/**
 * Render the site logo block.
 *
 * Prints the theme's logo artwork inline, linked to the homepage. The SVG is
 * decorative; the link is named by a visually hidden site name.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the site logo block.
 *
 * The colour variant is a block style, so it arrives as an `is-style-*`
 * class in `className`; the stylesheet maps each style to logo tokens.
 *
 * @var array{
 *     className?: string
 * } $attributes
 */

$svg = jmc_get_inline_svg( get_theme_file_path( 'build/assets/svgs/logo.svg' ) );

// Without the artwork (a broken build), render nothing rather than an empty link.
if ( '' === $svg ) {
	return;
}

$site_name = trim( wp_strip_all_tags( get_bloginfo( 'name' ) ) );

if ( '' === $site_name ) {
	$site_name = __( 'Home', 'jmc-theme' );
}

$link_attributes = [
	'href'    => home_url( '/' ),
	'rel'     => 'home',
	'classes' => [
		'jmc-site-logo',
		$attributes['className'] ?? null,
	],
];

?>
<a<?php jmc_the_attributes( $link_attributes ); ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered through wp_kses() with the SVG allow-list in jmc_get_inline_svg().
	echo $svg;
	?>
	<span class="jmc-site-logo__name"><?php echo esc_html( $site_name ); ?></span>
</a>
