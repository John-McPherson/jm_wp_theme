<?php
/**
 * Enqueue theme styles for blocks and editor.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Register the cascade-layer order as an inline-only style.
 *
 * Layer priority is fixed by the first stylesheet that mentions each layer, so
 * the order must be declared before any layered CSS. It is compiled from
 * `src/scss/layers/index.scss` to `build/css/layers.css`, keeping that SCSS
 * file the single source of truth.
 *
 * @return void
 */
add_action(
	'init',
	function (): void {
		$absolute_path = get_theme_file_path( 'build/css/layers.css' );

		if ( ! file_exists( $absolute_path ) ) {
			return;
		}

		// Compressed Sass drops the final semicolon; restore it so the statement
		// stays terminated if more inline CSS is ever added to this handle.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a local build file, not a remote URL.
		$layer_order = rtrim( trim( (string) file_get_contents( $absolute_path ) ), ';' );

		if ( '' === $layer_order ) {
			return;
		}

		wp_register_style( 'jmc-layers', false, [], (string) filemtime( $absolute_path ) );
		wp_add_inline_style( 'jmc-layers', $layer_order . ';' );
	}
);

/**
 * Print the layer order at the top of the frontend `<head>`.
 *
 * Block styles are enqueued while the template renders, so WordPress prints
 * them before the theme stylesheet. Printing the order first keeps it
 * independent of which blocks are on the page.
 *
 * @return void
 */
add_action(
	'wp_head',
	function (): void {
		if ( wp_style_is( 'jmc-layers', 'registered' ) ) {
			wp_print_styles( 'jmc-layers' );
		}
	},
	0
);

add_action(
	'enqueue_block_assets',
	function (): void {
		$relative_path = 'build/css/style.css';
		$absolute_path = get_theme_file_path( $relative_path );

		if ( ! file_exists( $absolute_path ) ) {
			return;
		}

		// The editor canvas has no `wp_head`; as a dependency the layer order prints
		// before this stylesheet, which is enqueued ahead of block styles (priority 10).
		wp_enqueue_style(
			'jmc-theme',
			get_theme_file_uri( $relative_path ),
			wp_style_is( 'jmc-layers', 'registered' ) ? [ 'jmc-layers' ] : [],
			(string) filemtime( $absolute_path )
		);
	},
	5
);
