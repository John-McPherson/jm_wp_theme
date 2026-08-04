<?php

declare(strict_types=1);

/**
 * Retrieve an integer value from an array.
 *
 * Returns the default value if the key is missing, non-numeric, or outside
 * the optional minimum and maximum bounds.
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

	if ( $min !== null && $value < $min ) {
		return $default;
	}

	if ( $max !== null && $value > $max ) {
		return $default;
	}

	return $value;
}

function jmc_args_string( array $args, string $key, string $default = '' ): string {
	return is_string( $args[ $key ] ?? null )
		? trim( $args[ $key ] )
		: $default;
}
