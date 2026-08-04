<?php
/**
 * Helper functions for loading theme components.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Locate and render a reusable theme component.
 *
 * @param string              $name Component name relative to the components directory.
 * @param array<string,mixed> $args Arguments passed into the component.
 *
 * @return void
 */
function jmc_component( string $name, array $args = [] ): void {
	$slug = "components/{$name}";
	$path = locate_template( "{$slug}.php", false, false );

	if ( '' === $path ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Intentional warning when a component template is missing.
		trigger_error(
			sprintf(
				'Component "%s" could not be found at "%s.php".',
				$name,  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$slug // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			),
			E_USER_WARNING
		);

		return;
	}

	get_template_part( $slug, null, $args );
}
