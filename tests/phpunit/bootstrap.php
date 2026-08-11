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

/**
 * Return an allowed string value or a fallback value.
 *
 * Test double for the production allowed-value helper.
 *
 * @param mixed         $value          Candidate value to validate.
 * @param array<string> $allowed        List of permitted string values.
 * @param string        $fallback_value Value returned when the candidate is invalid.
 *
 * @return string The validated value or the supplied fallback.
 */
function jmc_html_allowed_value(
	mixed $value,
	array $allowed,
	string $fallback_value
): string {
	return is_string( $value ) && in_array( $value, $allowed, true )
		? $value
		: $fallback_value;
}

/**
 * Retrieve and validate an integer argument.
 *
 * Test double for the production integer-argument helper.
 *
 * @param array<string, mixed> $args           Arguments containing the candidate value.
 * @param string               $key            Key used to retrieve the candidate value.
 * @param int                  $fallback_value Value returned when the candidate is invalid.
 * @param int                  $min            Minimum permitted integer value.
 *
 * @return int The validated integer or the supplied fallback.
 */
function jmc_args_int(
	array $args,
	string $key,
	int $fallback_value,
	int $min
): int {
	$value = $args[ $key ] ?? $fallback_value;

	return is_int( $value ) && $value >= $min
		? $value
		: $fallback_value;
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
 * Escape an HTML attribute value.
 *
 * Test double that returns the supplied value unchanged because escaping
 * behaviour is outside the scope of the isolated render tests.
 *
 * @param string $value Attribute value to escape.
 *
 * @return string The supplied attribute value without modification.
 */
function esc_attr( string $value ): string {
	return $value;
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

/**
 * Render an HTML attribute collection.
 *
 * The test double supports only the class and ID attributes required by the
 * isolated block-render tests.
 *
 * @param array<string, mixed> $attributes Attribute definitions to render.
 *
 * @return void
 */
function jmc_the_attributes( array $attributes ): void {
	$classes = array_filter(
		$attributes['classes'] ?? [],
		'is_string'
	);

	if ( [] !== $classes ) {
		printf(
			'class="%s"',
			esc_attr( implode( ' ', $classes ) )
		);
	}

	if (
		isset( $attributes['id'] )
		&& is_string( $attributes['id'] )
	) {
		printf(
			' id="%s"',
			esc_attr( $attributes['id'] )
		);
	}
}
