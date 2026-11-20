<?php
/**
 * Template landmarks: one page-level heading, and a focusable skip-link target.
 *
 * `templates/page.html` and `templates/front-page.html` print the page title
 * as the H1. The hero section's heading is locked to level one, so when a page
 * opens with a hero the template's title would be a second H1. The title is
 * omitted on those pages and the hero's heading names the page instead.
 *
 * Every template wraps its content in one `<main>` group, which the skip link
 * targets and which can take focus from it.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Whether block content supplies its own page heading, through a hero section.
 *
 * @param string $content Serialised block content.
 *
 * @return bool
 */
function jmc_content_has_page_heading( string $content ): bool {
	return 1 === preg_match( '/<!--\s+wp:jmc\/hero\s/', $content );
}

/**
 * Omit a level-one post title when the post's content holds the page heading.
 *
 * Titles at other levels, such as post titles in a list, are left alone.
 *
 * @param string              $block_content Rendered post title.
 * @param array<string,mixed> $block         Parsed block, with `attrs`.
 * @param object|null         $instance      Block instance, with `context`.
 *
 * @return string
 */
function jmc_filter_page_title( string $block_content, array $block, ?object $instance = null ): string {
	$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];

	if ( 1 !== jmc_args_int( args: $attrs, key: 'level', fallback_value: 2 ) ) {
		return $block_content;
	}

	$context = is_array( $instance?->context ?? null ) ? $instance->context : [];
	$post_id = jmc_args_int( args: $context, key: 'postId', fallback_value: 0, min: 1 );

	if ( 0 === $post_id ) {
		return $block_content;
	}

	$post    = get_post( $post_id );
	$content = is_object( $post ) ? ( $post->post_content ?? null ) : null;

	if ( ! is_string( $content ) || ! jmc_content_has_page_heading( $content ) ) {
		return $block_content;
	}

	return '';
}

add_filter( 'render_block_core/post-title', 'jmc_filter_page_title', 10, 3 );

/**
 * Let the skip link move focus into the main landmark.
 *
 * WordPress adds a "Skip to content" link to block templates that target the
 * first `<main>` (`_block_template_add_skip_link()`). Following a link to an
 * element that cannot take focus only scrolls to it in some browsers and
 * screen readers, so the main group gets `tabindex="-1"`: it can then take
 * focus from the link but stays out of the Tab order. Other groups, and a
 * main that already sets a `tabindex`, are returned unchanged.
 *
 * @param string              $block_content Rendered Group block.
 * @param array<string,mixed> $block         Parsed block, with `attrs`.
 *
 * @return string
 */
function jmc_main_skip_link_target( string $block_content, array $block ): string {
	$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];

	if ( 'main' !== jmc_args_string( args: $attrs, key: 'tagName' ) ) {
		return $block_content;
	}

	if ( 1 !== preg_match( '/^(\s*<main\b)([^>]*)>/i', $block_content, $match ) ) {
		return $block_content;
	}

	if ( 1 === preg_match( '/\stabindex\s*=/i', $match[2] ) ) {
		return $block_content;
	}

	return $match[1] . ' tabindex="-1"' . substr( $block_content, strlen( $match[1] ) );
}

add_filter( 'render_block_core/group', 'jmc_main_skip_link_target', 10, 2 );
