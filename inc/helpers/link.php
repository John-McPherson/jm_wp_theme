<?php
/**
 * Helper functions for validating link destinations.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Validate and normalise a link destination.
 *
 * Accepts absolute http(s) URLs, site-relative paths and telephone links.
 * Telephone links are normalised to `tel:` followed by an optional leading
 * `+` and digits only. Any other destination, including other schemes,
 * protocol-relative URLs and values containing whitespace or control
 * characters, is rejected.
 *
 * The result is a validated value, not an escaped one: still pass it through
 * `esc_url()` (or `jmc_the_attributes()`) at output.
 *
 * @param mixed $url Candidate destination.
 *
 * @return string The normalised destination, or an empty string when invalid.
 */
function jmc_safe_link_url( mixed $url ): string {
	if ( ! is_string( $url ) ) {
		return '';
	}

	$url = trim( $url );

	if ( '' === $url ) {
		return '';
	}

	if ( 0 === stripos( $url, 'tel:' ) ) {
		// Allow common visual separators, then require an optional + and 3–15 digits (E.164 maximum).
		$number = str_replace( [ ' ', '-', '.', '(', ')' ], '', substr( $url, 4 ) );

		return 1 === preg_match( '/^\+?\d{3,15}$/', $number )
			? 'tel:' . $number
			: '';
	}

	if ( 1 === preg_match( '/[\s\x00-\x1F\x7F\\\\]/', $url ) ) {
		return '';
	}

	// Site-relative path; `//host` would be protocol-relative and leave the site.
	if ( '/' === $url[0] ) {
		return '/' === ( $url[1] ?? '' )
			? ''
			: $url;
	}

	$parts = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Keeps the helper free of WordPress so it can be unit tested in isolation.

	if (
		! is_array( $parts )
		|| ! in_array( strtolower( $parts['scheme'] ?? '' ), [ 'http', 'https' ], true )
		|| '' === ( $parts['host'] ?? '' )
	) {
		return '';
	}

	return $url;
}
