<?php
/**
 * Print the theme's favicons.
 *
 * The theme ships the AMC square mark as a fallback site icon. A Site Icon
 * chosen in WordPress takes over, because core prints its own icon links.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Build the theme's favicon link tags.
 *
 * @return string Escaped link tags, or an empty string when a Site Icon is set.
 */
function jmc_get_favicon_links(): string {
	if ( has_site_icon() ) {
		return '';
	}

	$links = [
		[
			'rel'   => 'icon',
			'file'  => 'favicon.ico',
			'sizes' => '32x32',
		],
		[
			'rel'  => 'icon',
			'file' => 'favicon.svg',
			'type' => 'image/svg+xml',
		],
		[
			'rel'  => 'apple-touch-icon',
			'file' => 'apple-touch-icon.png',
		],
	];

	$html = '';

	foreach ( $links as $link ) {
		$html .= sprintf(
			'<link %s>' . "\n",
			jmc_get_the_attributes(
				[
					'rel'   => $link['rel'],
					'href'  => get_theme_file_uri( 'build/assets/favicons/' . $link['file'] ),
					'sizes' => $link['sizes'] ?? null,
					'type'  => $link['type'] ?? null,
				]
			)
		);
	}

	return $html;
}

/**
 * Print the theme's favicon link tags.
 *
 * @return void
 */
function jmc_print_favicons(): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped by jmc_get_the_attributes().
	echo jmc_get_favicon_links();
}

// Match the screens where core prints the Site Icon.
add_action( 'wp_head', 'jmc_print_favicons' );
add_action( 'admin_head', 'jmc_print_favicons' );
add_action( 'login_head', 'jmc_print_favicons' );
