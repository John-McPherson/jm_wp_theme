<?php

declare(strict_types=1);

// register all theme blocks
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

// only allow theme blocks
add_filter(
	'allowed_block_types_all',
	function ( $_allowed_blocks, $_editor_context ): array {

		$registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

		$allowed = [];

		foreach ( $registered_blocks as $block ) {

			if ( str_starts_with( $block->name, 'jm/' ) ) {
				$allowed[] = $block->name;
			}
		}

		return $allowed;
	},
	10,
	2
);


// register custom block categories
add_filter(
	'block_categories_all',
	function ( $categories ): array {
		return $categories[] = [
			[
				'slug'  => 'jm-section',
				'title' => __( 'Sections', 'jm' ),
				'icon'  => 'customizer',
			],
		];
	}
);
