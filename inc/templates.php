<?php
/**
 * Keep one page-level heading in the page templates.
 *
 * `templates/page.html` and `templates/front-page.html` print the page title
 * as the H1. The hero section's heading is locked to level one, so when a page
 * opens with a hero the template's title would be a second H1. The title is
 * omitted on those pages and the hero's heading names the page instead.
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
