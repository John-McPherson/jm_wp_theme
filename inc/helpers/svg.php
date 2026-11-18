<?php
/**
 * Helpers for printing theme SVG files inline.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Elements and attributes allowed in inline theme SVGs.
 *
 * Covers flat vector artwork drawn with grouped paths and coloured through
 * CSS classes. Scripts, styles, event handlers, links, `<use>` references and
 * presentation attributes such as `fill` and `style` are not allowed, so
 * colours always come from theme tokens.
 *
 * @return array<string, array<string, bool>> Allowed elements and attributes for `wp_kses()`.
 */
function jmc_svg_allowed_html(): array {
	return [
		'svg'  => [
			'xmlns'       => true,
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'class'       => true,
			'aria-hidden' => true,
			'focusable'   => true,
		],
		'g'    => [
			'class' => true,
		],
		'path' => [
			'class'     => true,
			'd'         => true,
			'fill-rule' => true,
		],
	];
}

/**
 * Read an SVG file and return its markup filtered for inline output.
 *
 * @param string $path Absolute path to the SVG file.
 *
 * @return string Filtered `<svg>` markup, or an empty string when the file is
 *                missing, unreadable or contains no allowed SVG.
 */
function jmc_get_inline_svg( string $path ): string {
	if ( '' === $path || ! is_file( $path ) || ! is_readable( $path ) ) {
		return '';
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a local theme file, not a remote URL.
	$markup = file_get_contents( $path );

	if ( ! is_string( $markup ) ) {
		return '';
	}

	$svg = trim( wp_kses( $markup, jmc_svg_allowed_html() ) );

	return 1 === preg_match( '/^<svg\b/i', $svg ) ? $svg : '';
}
