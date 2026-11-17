<?php
/**
 * PHPUnit bootstrap.
 *
 * Provides test doubles for the theme and WordPress functions required by
 * isolated render tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// Load the real, WordPress-independent theme helpers so render tests exercise
// production attribute handling, argument parsing and link validation.
require_once dirname( __DIR__, 2 ) . '/inc/helpers/component-args.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/html/attributes.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/link.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/navigation.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/text.php';

/**
 * Render a theme component.
 *
 * Test double that includes the component template directly, with `$args`
 * in scope as `get_template_part()` would provide.
 *
 * @param string              $name Component name relative to the components directory.
 * @param array<string,mixed> $args Arguments passed into the component.
 *
 * @return void
 */
function jmc_component( string $name, array $args = [] ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $args is read by the required component template.
	require dirname( __DIR__, 2 ) . "/components/{$name}.php";
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WordPress test doubles must retain their core function names.

/**
 * Return the URL for a WordPress attachment image.
 *
 * Test double that prevents isolated render tests from querying WordPress
 * media data.
 *
 * @param int    $attachment_id ID of the requested attachment.
 * @param string $size          Registered image size to request.
 *
 * @return false Always returns false in isolated tests.
 */
function wp_get_attachment_image_url(
	int $attachment_id,
	string $size
): bool {
	unset( $attachment_id, $size );

	return false;
}

/**
 * Escape a URL for output.
 *
 * Test double that returns the supplied URL unchanged because URL escaping is
 * outside the scope of the isolated render tests.
 *
 * @param string $url URL supplied by the render callback.
 *
 * @return string The supplied URL without modification.
 */
function esc_url( string $url ): string {
	return $url;
}

/**
 * Sanitize a URL for storage.
 *
 * @param string $url URL to sanitize.
 * @return string The supplied URL.
 */
function esc_url_raw( string $url ): string {
	return $url;
}

/**
 * Convert a value to a non-negative integer.
 *
 * @param mixed $maybeint Value to normalize.
 * @return int Normalized integer.
 */
function absint( mixed $maybeint ): int {
	return abs( (int) $maybeint );
}

/**
 * Escape an HTML attribute value.
 *
 * Test double that HTML-encodes the value so tests can assert escaping.
 *
 * @param string $value Attribute value to escape.
 *
 * @return string The escaped attribute value.
 */
function esc_attr( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * Escape a value for HTML output.
 *
 * Test double that HTML-encodes the value so tests can assert escaping.
 *
 * @param string $text Text to escape.
 *
 * @return string The escaped text.
 */
function esc_html( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Output translated, escaped text.
 *
 * Test double that skips translation.
 *
 * @param string $text   Text to output.
 * @param string $domain Text domain.
 *
 * @return void
 */
function esc_html_e( string $text, string $domain = 'default' ): void {
	unset( $domain );

	echo esc_html( $text );
}

/**
 * Sanitize a CSS class name.
 *
 * Test double matching core's behaviour for ASCII class names.
 *
 * @param string $classname Class name to sanitize.
 *
 * @return string The sanitized class name.
 */
function sanitize_html_class( string $classname ): string {
	$classname = (string) preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $classname );

	return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $classname );
}

/**
 * Retrieve a post.
 *
 * Test double that reads fixtures from `$GLOBALS['jmc_test_posts']`, keyed by
 * post ID, so render tests control which posts exist.
 *
 * @param mixed $post Post ID.
 *
 * @return object|null The fixture post, or null when none is registered.
 */
function get_post( mixed $post = null ): ?object {
	$posts = $GLOBALS['jmc_test_posts'] ?? [];

	return is_int( $post ) && is_object( $posts[ $post ] ?? null )
		? $posts[ $post ]
		: null;
}

/**
 * Parse serialized block content.
 *
 * Test double: fixtures store `post_content` as JSON-encoded parsed blocks
 * rather than block markup, so tests avoid depending on core's parser.
 *
 * @param string $content JSON-encoded parsed blocks.
 *
 * @return array<int,array<string,mixed>> Parsed blocks.
 */
function parse_blocks( string $content ): array {
	$blocks = json_decode( $content, true );

	return is_array( $blocks ) ? $blocks : [];
}

/**
 * Determine whether the current request is for the given page.
 *
 * Test double that compares against `$GLOBALS['jmc_test_current_page']`.
 *
 * @param mixed $page Page ID.
 *
 * @return bool Whether the page is the current page.
 */
function is_page( mixed $page = '' ): bool {
	return ( $GLOBALS['jmc_test_current_page'] ?? null ) === $page;
}

/**
 * Retrieve translated text.
 *
 * Test double that skips translation.
 *
 * @param string $text   Text to translate.
 * @param string $domain Text domain.
 *
 * @return string The untranslated text.
 */
function __( string $text, string $domain = 'default' ): string {
	unset( $domain );

	return $text;
}

/**
 * Return the SVG markup for a registered icon.
 *
 * Test double for the core icon registry: renders a placeholder SVG that
 * records the icon name and classes for the icons the theme uses, and an
 * empty string for anything else, as core does for unregistered icons.
 *
 * @param string              $name Namespaced icon name.
 * @param array<string,mixed> $args Icon arguments; only `class` is used.
 *
 * @return string Placeholder SVG markup, or an empty string.
 */
function wp_get_icon( string $name, array $args = [] ): string {
	if ( ! in_array( $name, [ 'core/envelope', 'core/home', 'core/map-marker', 'jmc/phone' ], true ) ) {
		return '';
	}

	return sprintf(
		'<svg class="%s" data-icon="%s" aria-hidden="true" focusable="false"></svg>',
		esc_attr( (string) ( $args['class'] ?? '' ) ),
		esc_attr( $name )
	);
}

/**
 * Retrieve the URL for the site's front end.
 *
 * Test double using a fixed example host.
 *
 * @param string $path Path relative to the home URL.
 *
 * @return string The home URL with the path appended.
 */
function home_url( string $path = '' ): string {
	return 'https://example.com/' . ltrim( $path, '/' );
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
