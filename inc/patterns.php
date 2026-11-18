<?php
/**
 * Register the theme's block pattern categories.
 *
 * Patterns themselves live one per file under `patterns/` and are registered
 * by WordPress from their file headers; see
 * docs/architecture/pattern-taxonomy-and-registration.md.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Pattern categories in use, keyed by slug.
 *
 * Add a category from the documented taxonomy only when a pattern needs it.
 *
 * @return array<string, string> Editor labels keyed by category slug.
 */
function jmc_pattern_categories(): array {
	return [
		'jmc-hero'     => __( 'Heroes', 'jmc-theme' ),
		'jmc-services' => __( 'Services', 'jmc-theme' ),
		'jmc-trust'    => __( 'Trust', 'jmc-theme' ),
	];
}

add_action(
	'init',
	function (): void {
		foreach ( jmc_pattern_categories() as $slug => $label ) {
			register_block_pattern_category(
				$slug,
				[
					'label' => $label,
				]
			);
		}
	}
);
