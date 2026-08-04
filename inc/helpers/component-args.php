<?php
/**
 * Helper utilities for component argument handling.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Retrieve an integer value from an array.
 *
 * Returns the default value if the key is missing, non-numeric, or outside
 * the optional minimum and maximum bounds.
 *
 * @param array<string,mixed> $args    Input arguments.
 * @param string              $key     Argument key to read.
 * @param int                 $default Default fallback value.
 * @param int|null            $min     Optional minimum bound.
 * @param int|null            $max     Optional maximum bound.
 *
 * @return int
 */
function jmc_args_int(
	array $args,
	string $key,
	int $default = 0,
	?int $min = null,
	?int $max = null
): int {
	$value = is_numeric( $args[ $key ] ?? null )
		? (int) $args[ $key ]
		: $default;

	if ( null !== $min && $value < $min ) {
		return $default;
	}

	if ( null !== $max && $value > $max ) {
		return $default;
	}

	return $value;
}

/**
 * Retrieve a trimmed string value from an array.
 *
 * Returns the default value when the key is missing or not a string.
 *
 * @param array<string,mixed> $args    Input arguments.
 * @param string              $key     Argument key to read.
 * @param string              $default Default fallback value.
 *
 * @return string
 */
function jmc_args_string( array $args, string $key, string $default = '' ): string {
	return is_string( $args[ $key ] ?? null )
		? trim( $args[ $key ] )
		: $default;
}
