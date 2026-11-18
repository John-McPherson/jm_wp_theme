<?php
/**
 * Register theme icons with the core icon registry.
 *
 * Theme icons live in the `jmc` collection, so they render through
 * `wp_get_icon( 'jmc/<name>' )` like core icons and appear in the core Icon
 * block's picker.
 *
 * Theme icons are Lucide SVGs (ISC licence) and Simple Icons brand logos
 * (CC0, subject to each brand's guidelines), generated into
 * build/assets/icons/<set> by scripts/icons/build.js from the lists in
 * src/icons/lucide.json and src/icons/simple-icons.json.
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

		// Lucide outline icons and Simple Icons brand logos, generated at build time.
		foreach ( [ 'lucide', 'simple-icons' ] as $icon_set ) {
			$directory = get_theme_file_path( "build/assets/icons/{$icon_set}" );
			$manifest  = $directory . '/manifest.json';

			if ( ! is_readable( $manifest ) ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build file.
			$icons = json_decode( (string) file_get_contents( $manifest ), true );

			if ( ! is_array( $icons ) ) {
				continue;
			}

			foreach ( $icons as $name => $label ) {
				if ( ! is_string( $name ) || ! is_string( $label ) ) {
					continue;
				}

				wp_register_icon(
					"jmc/{$name}",
					[
						'label'     => $label,
						'file_path' => "{$directory}/{$name}.svg",
					]
				);
			}
		}
	}
);
