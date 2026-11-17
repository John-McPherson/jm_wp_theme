<?php
/**
 * Register theme icons with the core icon registry.
 *
 * Theme icons live in the `jmc` collection, so they render through
 * `wp_get_icon( 'jmc/<name>' )` like core icons and appear in the core Icon
 * block's picker.
 *
 * Theme icons are Lucide SVGs (ISC licence) generated into
 * build/assets/icons/lucide by scripts/icons/build.js from the list in
 * src/icons/lucide.json.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		wp_register_icon_collection(
			'jmc',
			[
				'label'       => __( 'JMC Theme', 'jmc-theme' ),
				'description' => __( 'Icons supplied by the theme.', 'jmc-theme' ),
			]
		);

		$lucide_directory = get_theme_file_path( 'build/assets/icons/lucide' );
		$lucide_manifest  = $lucide_directory . '/manifest.json';

		if ( ! is_readable( $lucide_manifest ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build file.
		$lucide_icons = json_decode( (string) file_get_contents( $lucide_manifest ), true );

		if ( ! is_array( $lucide_icons ) ) {
			return;
		}

		foreach ( $lucide_icons as $name => $label ) {
			if ( ! is_string( $name ) || ! is_string( $label ) ) {
				continue;
			}

			wp_register_icon(
				"jmc/{$name}",
				[
					'label'     => $label,
					'file_path' => "{$lucide_directory}/{$name}.svg",
				]
			);
		}
	}
);
