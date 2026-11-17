<?php
/**
 * Register theme blocks and configure available editor blocks.
 *
 * Loads theme block registrations from build artifacts, restricts available
 * block choices to theme-owned blocks, and registers a custom block category.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Register theme block types from the build directory.
 *
 * @return void
 */
add_action(
	'init',
	function (): void {
		$blocks_path = get_theme_file_path( 'build/js/blocks' );

		if ( ! is_dir( $blocks_path ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$blocks_path,
				FilesystemIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isDir() ) {
				continue;
			}

			$block_json = $file->getPathname() . '/block.json';

			if ( ! file_exists( $block_json ) ) {
				continue;
			}

			register_block_type( $file->getPathname() );
		}
	}
);

// only allow theme blocks.
add_filter(
	'allowed_block_types_all',
	function ( $allowed_blocks, $editor_context ) {
		// Leave FSE / Site Editor unchanged.
		if ( isset( $editor_context->name ) && 'core/edit-site' === $editor_context->name ) {
			return $allowed_blocks;
		}

		$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		// Respect any existing allowed-block configuration first.
		$blocks_to_filter = is_array( $allowed_blocks )
		? $allowed_blocks
		: array_keys( $registered_blocks );

		// In regular editors, allow only non-core blocks.
		return array_values(
			array_filter(
				$blocks_to_filter,
				function ( $block_name ) {
					return 0 !== strpos( $block_name, 'core/' );
				}
			)
		);
	},
	10,
	2
);


// register custom block categories.
add_filter(
	'block_categories_all',
	/**
	 * Register a custom block category for the theme.
	 *
	 * @param array<int, array<string,mixed>> $categories Existing block categories.
	 *
	 * @return array<int, array<string,mixed>> Updated block categories.
	 */
	function ( $categories ): array {
		$categories[] = [
			'slug'  => 'jmc-section',
			'title' => __( 'Sections', 'jmc-theme' ),
			'icon'  => 'customizer',

		];
		$categories[] = [
			'slug'  => 'jmc-site',
			'title' => __( 'Site', 'jmc-theme' ),
			'icon'  => 'admin-site-alt3',

		];
		return $categories;
	}
);
