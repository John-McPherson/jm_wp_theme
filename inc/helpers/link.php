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
 * Accepts absolute http(s) URLs, site-relative paths and telephone links, and
 * email links when `$allow_mailto` is set. Telephone links are normalised to
 * `tel:` followed by an optional leading `+` and digits only. Email links need
 * a single valid address and may carry a query such as `?subject=`. Any other
 * destination, including other schemes, protocol-relative URLs and values
 * containing whitespace or control characters, is rejected.
 *
 * The result is a validated value, not an escaped one: still pass it through
 * `esc_url()` (or `jmc_the_attributes()`) at output.
 *
 * @param mixed $url          Candidate destination.
 * @param bool  $allow_mailto Whether `mailto:` links are accepted.
 *
 * @return string The normalised destination, or an empty string when invalid.
 */
function jmc_safe_link_url( mixed $url, bool $allow_mailto = false ): string {
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

	if ( 0 === stripos( $url, 'mailto:' ) ) {
		if ( ! $allow_mailto ) {
			return '';
		}

		[ $address, $query ] = array_pad( explode( '?', substr( $url, 7 ), 2 ), 2, '' );

		if ( false === filter_var( $address, FILTER_VALIDATE_EMAIL ) ) {
			return '';
		}

		return 'mailto:' . $address . ( '' !== $query ? '?' . $query : '' );
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
