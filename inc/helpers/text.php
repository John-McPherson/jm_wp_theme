<?php
/**
 * Helper functions for normalising text values.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Convert a rich-text attribute value to plain text.
 *
 * Editor `RichText` fields save HTML, even with formatting disabled: line
 * breaks become `<br>` and characters such as `&` become entities. This turns
 * line breaks into newlines, removes any other markup, decodes entities and
 * trims each line, dropping empty ones.
 *
 * The result is plain text, not escaped text: still escape it at output.
 *
 * @param mixed $value Candidate rich-text value.
 *
 * @return string Plain text with lines separated by "\n", or an empty string.
 */
function jmc_plain_text( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$text = (string) preg_replace( '/<br\s*\/?>/i', "\n", $value );
	$text = strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Keeps the helper free of WordPress so it can be unit tested in isolation; <script>/<style> are not produced by formatless RichText.
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\u{00A0}", ' ', $text );

	$lines = array_filter(
		array_map( 'trim', explode( "\n", $text ) ),
		static fn ( string $line ): bool => '' !== $line
	);

	return implode( "\n", $lines );
}
