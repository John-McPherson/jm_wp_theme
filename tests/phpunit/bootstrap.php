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
require_once dirname( __DIR__, 2 ) . '/inc/helpers/contact.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/button.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/enquiry.php';
require_once dirname( __DIR__, 2 ) . '/inc/helpers/svg.php';

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
 * Test double that strips encoded line breaks, as core does, and otherwise
 * returns the URL unchanged because the rest of URL escaping is outside the
 * scope of the isolated render tests.
 *
 * @param string $url URL supplied by the render callback.
 *
 * @return string The URL without encoded line breaks.
 */
function esc_url( string $url ): string {
	// Like core, strip encoded line breaks.
	return str_ireplace( [ '%0d', '%0a' ], '', $url );
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
	$icons = [
		'jmc/arrow-right',
		'jmc/check',
		'jmc/circle-check',
		'jmc/clock',
		'jmc/house',
		'jmc/mail',
		'jmc/map-pin',
		'jmc/phone',
		'jmc/plus',
		'jmc/shield-check',
		'jmc/whatsapp',
		'jmc/zap',
	];

	if ( ! in_array( $name, $icons, true ) ) {
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

/**
 * Sanitize a string key.
 *
 * Test double matching core: lowercase, keeping only a–z, 0–9, `_` and `-`.
 *
 * @param string $key String key.
 *
 * @return string Sanitized key.
 */
function sanitize_key( string $key ): string {
	return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
}

/**
 * Retrieve posts.
 *
 * Test double that filters the `$GLOBALS['jmc_test_posts']` fixtures by
 * `post_type`, `name`, `post_parent`, `post_status`, `post__in` and
 * `has_password`, and records each query's arguments in
 * `$GLOBALS['jmc_test_get_posts_queries']`. An `orderby` of `post__in` keeps
 * the given ID order, and a `posts_per_page` of -1 returns every match, as in
 * core.
 *
 * @param array<string,mixed> $args Query arguments.
 *
 * @return object[] Matching fixture posts.
 */
function get_posts( array $args = [] ): array {
	$GLOBALS['jmc_test_get_posts_queries'][] = $args;

	$statuses = (array) ( $args['post_status'] ?? [ 'publish' ] );

	$posts = array_filter(
		$GLOBALS['jmc_test_posts'] ?? [],
		static fn ( mixed $post ): bool => is_object( $post )
			&& ( $post->post_type ?? '' ) === ( $args['post_type'] ?? 'post' )
			&& ( ! isset( $args['name'] ) || ( $post->post_name ?? '' ) === $args['name'] )
			&& ( ! isset( $args['post_parent'] ) || ( $post->post_parent ?? 0 ) === $args['post_parent'] )
			&& in_array( $post->post_status ?? 'publish', $statuses, true )
			&& ( ! isset( $args['post__in'] ) || in_array( $post->ID ?? 0, (array) $args['post__in'], true ) )
			&& ( ! isset( $args['has_password'] ) || ( '' !== ( $post->post_password ?? '' ) ) === $args['has_password'] )
	);

	if ( 'post__in' === ( $args['orderby'] ?? null ) ) {
		$order = array_flip( array_values( (array) $args['post__in'] ) );

		usort(
			$posts,
			static fn ( object $a, object $b ): int => $order[ $a->ID ] <=> $order[ $b->ID ]
		);
	}

	$limit = (int) ( $args['posts_per_page'] ?? 5 );

	return -1 === $limit
		? array_values( $posts )
		: array_slice( array_values( $posts ), 0, $limit );
}

/**
 * Retrieve information about the site.
 *
 * Test double that reads `$GLOBALS['jmc_test_bloginfo']`, keyed by `$show`.
 *
 * @param string $show Site information to retrieve.
 *
 * @return string The value, or an empty string.
 */
function get_bloginfo( string $show = '' ): string {
	return (string) ( $GLOBALS['jmc_test_bloginfo'][ $show ] ?? '' );
}

/**
 * Format a date in the site's timezone.
 *
 * Test double that formats a fixed time (1 January 2030, UTC) so output is
 * stable regardless of when the tests run.
 *
 * @param string $format PHP date format.
 *
 * @return string Formatted date.
 */
function wp_date( string $format ): string {
	return gmdate( $format, 1893456000 );
}

/**
 * Retrieve translated, escaped text.
 *
 * Test double that skips translation.
 *
 * @param string $text   Text to translate.
 * @param string $domain Text domain.
 *
 * @return string The escaped, untranslated text.
 */
function esc_html__( string $text, string $domain = 'default' ): string {
	unset( $domain );

	return esc_html( $text );
}

/**
 * Retrieve an option.
 *
 * Test double that reads `$GLOBALS['jmc_test_options']`.
 *
 * @param string $option         Option name.
 * @param mixed  $default_value Value returned when the option is not set.
 *
 * @return mixed The option value.
 */
function get_option( string $option, mixed $default_value = false ): mixed {
	return $GLOBALS['jmc_test_options'][ $option ] ?? $default_value;
}

/**
 * Update an option.
 *
 * Test double that writes `$GLOBALS['jmc_test_options']`.
 *
 * @param string    $option   Option name.
 * @param mixed     $value    Option value.
 * @param bool|null $autoload Whether to autoload the option (ignored).
 *
 * @return bool Always true.
 */
function update_option( string $option, mixed $value, ?bool $autoload = null ): bool {
	unset( $autoload );

	$GLOBALS['jmc_test_options'][ $option ] = $value;

	return true;
}

/**
 * Insert a post.
 *
 * Test double that records the post data in
 * `$GLOBALS['jmc_test_inserted_posts']`.
 *
 * @param array<string,mixed> $postarr Post data.
 *
 * @return int The new post's (fake) ID.
 */
function wp_insert_post( array $postarr ): int {
	$GLOBALS['jmc_test_inserted_posts'][] = $postarr;

	return 1000 + count( $GLOBALS['jmc_test_inserted_posts'] );
}

/**
 * Hook a callback onto an action.
 *
 * Test double that records the hook in `$GLOBALS['jmc_test_actions']`.
 *
 * @param string   $hook_name Action name.
 * @param callable $callback  Callback.
 * @param int      $priority  Priority.
 *
 * @return true Always true.
 */
function add_action( string $hook_name, callable $callback, int $priority = 10 ): bool {
	$GLOBALS['jmc_test_actions'][ $hook_name ][] = [ $callback, $priority ];

	return true;
}

/**
 * Determine whether an attachment is an image.
 *
 * Test double that reads fixtures from `$GLOBALS['jmc_test_attachments']`,
 * keyed by attachment ID, each an array with optional `alt` and `title`.
 *
 * @param mixed $post Attachment ID.
 *
 * @return bool Whether an image fixture is registered for the ID.
 */
function wp_attachment_is_image( mixed $post = null ): bool {
	return is_int( $post ) && isset( $GLOBALS['jmc_test_attachments'][ $post ] );
}

/**
 * Retrieve a post meta field.
 *
 * Test double that returns an attachment fixture's `alt` for
 * `_wp_attachment_image_alt`; other keys read the services fixture's meta
 * (see `doubles/services.php`) when it is loaded.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param bool   $single  Whether to return a single value (ignored).
 *
 * @return mixed The meta value.
 */
function get_post_meta( int $post_id, string $key = '', bool $single = false ): mixed {
	unset( $single );

	if ( '_wp_attachment_image_alt' === $key ) {
		return $GLOBALS['jmc_test_attachments'][ $post_id ]['alt'] ?? '';
	}

	return function_exists( 'jmc_test_wp' )
		? ( jmc_test_wp( 'meta' )[ $post_id ][ $key ] ?? '' )
		: '';
}

/**
 * Retrieve a post title.
 *
 * Test double that returns an attachment fixture's `title`.
 *
 * @param mixed $post Post ID.
 *
 * @return string The title, or an empty string.
 */
function get_the_title( mixed $post = 0 ): string {
	return is_int( $post )
		? (string) ( $GLOBALS['jmc_test_attachments'][ $post ]['title'] ?? '' )
		: '';
}

/**
 * Return the registered intermediate image size names.
 *
 * Test double listing core's default sizes.
 *
 * @return string[] Image size names.
 */
function get_intermediate_image_sizes(): array {
	return [ 'thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048' ];
}

/**
 * Render an attachment image.
 *
 * Test double for registered attachment fixtures. The size is written into
 * the `src` so tests can assert it; `alt` comes from `$attr`, falling back to
 * the fixture's Media Library alternative text as core does.
 *
 * @param int                 $attachment_id Attachment ID.
 * @param string              $size          Image size.
 * @param bool                $icon          Whether to use a media icon (ignored).
 * @param array<string,mixed> $attr          Image attributes.
 *
 * @return string Image markup, or an empty string for unknown attachments.
 */
function wp_get_attachment_image( int $attachment_id, string $size = 'thumbnail', bool $icon = false, array $attr = [] ): string {
	unset( $icon );

	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}

	$attr = array_merge(
		[
			'src' => "https://example.test/uploads/{$attachment_id}-{$size}.png",
			'alt' => trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
		],
		$attr
	);

	$html = '<img';

	foreach ( $attr as $name => $value ) {
		// Core omits a `false` loading attribute.
		if ( false === $value ) {
			continue;
		}

		$html .= sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
	}

	return $html . ' />';
}

/**
 * Filter HTML to the allowed elements and attributes.
 *
 * Test double approximating core's kses: disallowed elements are unwrapped
 * (their text is kept), disallowed attributes are dropped, and `href` values
 * with a protocol other than http(s), mailto or tel are removed. Core strips
 * the bad protocol instead, but either way no script URL survives.
 *
 * @param string                             $content      HTML to filter.
 * @param array<string, array<string, bool>> $allowed_html Allowed elements and their attributes.
 *
 * @return string Filtered HTML.
 */
function wp_kses( string $content, array $allowed_html ): string {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM extension property names.
	if ( '' === trim( $content ) ) {
		return $content;
	}

	$document = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$document->loadHTML(
		'<?xml encoding="utf-8"?><body>' . $content . '</body>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$root = $document->getElementsByTagName( 'body' )->item( 0 );

	$filter = static function ( DOMNode $node ) use ( &$filter, $allowed_html ): void {
		foreach ( iterator_to_array( $node->childNodes ) as $child ) {
			if ( ! $child instanceof DOMElement ) {
				continue;
			}

			$filter( $child );

			$tag = strtolower( $child->tagName );

			if ( ! isset( $allowed_html[ $tag ] ) ) {
				while ( $child->firstChild ) {
					$node->insertBefore( $child->firstChild, $child );
				}

				$node->removeChild( $child );
				continue;
			}

			foreach ( iterator_to_array( $child->attributes ) as $attribute ) {
				$name    = strtolower( $attribute->name );
				$allowed = ! empty( $allowed_html[ $tag ][ $name ] );

				if ( $allowed && 'href' === $name ) {
					$allowed = 1 !== preg_match( '/^\s*(?!https?:|mailto:|tel:)[a-z][a-z0-9+.-]*:/i', $attribute->value );
				}

				if ( ! $allowed ) {
					$child->removeAttribute( $attribute->name );
				}
			}
		}
	};

	$filter( $root );

	$html = '';

	foreach ( $root->childNodes as $child ) {
		$html .= $document->saveHTML( $child );
	}

	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	return $html;
}

/**
 * Strip all HTML tags, including the contents of script and style elements.
 *
 * Test double for the core function.
 *
 * @param string $text Text to strip.
 *
 * @return string Plain text.
 */
function wp_strip_all_tags( string $text ): string {
	$text = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );

	return trim( strip_tags( $text ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- This is the wp_strip_all_tags() double.
}

/**
 * Escape an HTML tag name.
 *
 * Test double for the core function.
 *
 * @param string $tag_name Tag name.
 *
 * @return string Lowercase tag name with only safe characters.
 */
function tag_escape( string $tag_name ): string {
	return strtolower( (string) preg_replace( '/[^a-zA-Z0-9-_:]/', '', $tag_name ) );
}

/**
 * Retrieve the path of a file in the theme.
 *
 * Test double that resolves against `$GLOBALS['jmc_test_theme_root']`,
 * defaulting to the theme's own directory, so tests can supply build files.
 *
 * @param string $file File to search for in the theme directory.
 *
 * @return string The file path.
 */
function get_theme_file_path( string $file = '' ): string {
	$root = (string) ( $GLOBALS['jmc_test_theme_root'] ?? dirname( __DIR__, 2 ) );

	return rtrim( $root, '/' ) . '/' . ltrim( $file, '/' );
}

/**
 * Retrieve the URL of a file in the theme.
 *
 * Test double that resolves against a fixed theme URL.
 *
 * @param string $file File to search for in the theme directory.
 *
 * @return string The file URL.
 */
function get_theme_file_uri( string $file = '' ): string {
	return 'https://example.com/wp-content/themes/amc_electrical/' . ltrim( $file, '/' );
}

/**
 * Determine whether the site has a Site Icon.
 *
 * Test double that reads `$GLOBALS['jmc_test_has_site_icon']`, defaulting to false.
 *
 * @return bool Whether a Site Icon is set.
 */
function has_site_icon(): bool {
	return (bool) ( $GLOBALS['jmc_test_has_site_icon'] ?? false );
}

/**
 * Retrieve the active theme's directory name.
 *
 * Test double that reads `$GLOBALS['jmc_test_stylesheet']`, defaulting to the theme's own.
 *
 * @return string Theme directory name.
 */
function get_stylesheet(): string {
	return (string) ( $GLOBALS['jmc_test_stylesheet'] ?? 'amc_electrical' );
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
