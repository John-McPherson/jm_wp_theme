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
	/**
	 * Filter block types to only include theme-owned blocks.
	 *
	 * @param array<string> $allowed_blocks   Currently allowed blocks.
	 * @param array<string, mixed> $_editor_context Editor context data.
	 *
	 * @return array<string> Updated allowed blocks.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Parameters are required by the WordPress filter callback signature.
	function ( $_allowed_blocks, $_editor_context ): array {

		$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		$allowed = [];

		foreach ( $registered_blocks as $block ) {

			if ( str_starts_with( $block->name, 'jmc/' ) ) {
				$allowed[] = $block->name;
			}
		}

		return $allowed;
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
		return $categories;
	}
);
