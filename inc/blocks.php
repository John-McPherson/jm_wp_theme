<?php
/**
 * Register theme blocks and configure available editor blocks.
 *
 * Loads theme block registrations from build artifacts, limits each editor
 * context to the blocks the theme supports, and registers a custom block category.
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

/**
 * Limit each editor context to the blocks the theme supports.
 *
 * See `jmc_allowed_blocks_for_context()` for the per-context lists. An
 * earlier filter's list narrows the candidates; `false` (no blocks) is kept.
 */
add_filter(
	'allowed_block_types_all',
	/**
	 * Filter the blocks an editor context may insert.
	 *
	 * @param bool|array<int, string>  $allowed_blocks Allowed block names, or true/false for all/none.
	 * @param WP_Block_Editor_Context $editor_context Current editor context.
	 *
	 * @return bool|list<string>
	 */
	function ( bool|array $allowed_blocks, WP_Block_Editor_Context $editor_context ): bool|array {
		if ( false === $allowed_blocks ) {
			return false;
		}

		$context    = is_string( $editor_context->name ) ? $editor_context->name : '';
		$candidates = is_array( $allowed_blocks )
			? $allowed_blocks
			: array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );

		/**
		 * Filter the blocks the theme allows in an editor context.
		 *
		 * Use it to opt plugin blocks in, e.g. `$blocks[] = 'yoast/faq-block';`.
		 *
		 * @param list<string> $blocks  Allowed block names.
		 * @param string       $context Editor context name, e.g. `core/edit-post` or `core/edit-site`.
		 */
		$blocks = apply_filters(
			'jmc_allowed_blocks',
			jmc_allowed_blocks_for_context( context: $context, registered: $candidates ),
			$context
		);

		return is_array( $blocks ) ? array_values( array_unique( array_filter( $blocks, 'is_string' ) ) ) : [];
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
			'slug'  => 'jmc-content',
			'title' => __( 'Content', 'jmc-theme' ),
			'icon'  => 'text',

		];
		$categories[] = [
			'slug'  => 'jmc-site',
			'title' => __( 'Site', 'jmc-theme' ),
			'icon'  => 'admin-site-alt3',

		];
		return $categories;
	}
);
