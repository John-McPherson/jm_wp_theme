<?php
/**
 * Helper functions for rendering and sanitizing HTML attributes.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Build a string of escaped HTML attributes.
 *
 * Currently supports:
 * - classes → class
 * - href
 * - target
 * - rel
 *
 * @param array<string,mixed> $attributes Attribute values to render.
 *
 * @return string Escaped HTML-safe attribute string.
 */
function jmc_get_the_attributes( array $attributes ): string {
	$html = [];

	foreach ( $attributes as $name => $value ) {
		if ( null === $value || '' === $value || false === $value ) {
			continue;
		}

		switch ( $name ) {
			case 'classes':
				$name  = 'class';
				$value = jmc_html_classes( $value );
				break;

			case 'style':
				if ( ! is_array( $value ) ) {
					continue 2;
				}

				$value = jmc_html_styles( $value );
				break;

			case 'href':
				$value = esc_url( (string) $value );
				break;

			case 'target':
				$value = jmc_html_allowed_value(
					value: $value,
					allowed: [ '_self', '_blank', '_parent', '_top' ]
				);
				break;

			case 'rel':
				$value = jmc_html_allowed_tokens(
					value: $value,
					allowed: [
						'alternate',
						'author',
						'bookmark',
						'external',
						'help',
						'license',
						'next',
						'nofollow',
						'noopener',
						'noreferrer',
						'prev',
						'search',
						'tag',
					]
				);
				break;

			default:
				$value = (string) $value;
				break;
		}

		if ( '' === $value ) {
			continue;
		}

		$html[] = sprintf(
			'%s="%s"',
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	return implode( ' ', $html );
}

/**
 * Output escaped HTML attributes.
 *
 * @param array<string,mixed> $attributes Attribute values to render.
 *
 * @return void
 */
function jmc_the_attributes( array $attributes ): void {
	$html = jmc_get_the_attributes( $attributes );

	if ( '' !== $html ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- validated in get_the_attributes.
		echo ' ' . $html;
	}
}

/**
 * Normalise and sanitise one or more CSS class names.
 *
 * @param mixed $value CSS class string or array of class values.
 *
 * @return string Sanitized class list.
 */
function jmc_html_classes( mixed $value ): string {
	if ( is_array( $value ) ) {
		$classes = array_filter(
			array_map( jmc_html_classes( ... ), $value )
		);

		return implode( ' ', array_unique( $classes ) );
	}

	$classes = preg_split( '/\s+/', trim( (string) $value ) );

	if ( false === $classes ) {
		return '';
	}

	$classes = array_filter(
		array_map( 'sanitize_html_class', $classes )
	);

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Validate a value against an allowlist.
 *
 * Returns the default when the value is not a string or is not allowed.
 *
 * @param mixed    $value   Value to validate.
 * @param string[] $allowed Allowed values.
 * @param string   $default Fallback value.
 *
 * @return string
 */
function jmc_html_allowed_value(
	mixed $value,
	array $allowed,
	string $default = ''
): string {
	if ( ! is_string( $value ) ) {
		return $default;
	}

	return in_array( $value, $allowed, true )
		? $value
		: $default;
}

/**
 * Filter a token list against an allowlist.
 *
 * Accepts either a space-separated string or an array of tokens.
 *
 * @param mixed    $value   Value to validate.
 * @param string[] $allowed Allowed tokens.
 *
 * @return string Sanitized list of allowed tokens.
 */
function jmc_html_allowed_tokens(
	mixed $value,
	array $allowed
): string {
	$tokens = is_array( $value )
		? $value
		: preg_split( '/\s+/', trim( (string) $value ) );

	if ( false === $tokens ) {
		return '';
	}

	$tokens = array_filter(
		$tokens,
		static fn( mixed $token ): bool =>
		is_string( $token )
			&& in_array( $token, $allowed, true )
	);

	return implode( ' ', array_unique( $tokens ) );
}


/**
 * Build an inline style attribute from CSS custom properties.
 *
 * @param array<string, string> $styles CSS custom properties as key/value pairs.
 *
 * @return string Inline style declaration.
 */
function jmc_html_styles( array $styles ): string {
	$declarations = [];

	foreach ( $styles as $property => $value ) {
		if (
			'' === $value
			|| ! str_starts_with( $property, '--' )
		) {
			continue;
		}

		$declarations[] = sprintf(
			'%s: %s',
			$property,
			$value
		);
	}

	return implode( '; ', $declarations );
}
