<?php
/**
 * Register the Services parent setting and page excerpt support.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

add_action(
	'init',
	function (): void {
		// Service cards use the page excerpt as their summary.
		add_post_type_support( 'page', 'excerpt' );

		register_setting(
			'reading',
			JMC_SERVICES_PAGE_OPTION,
			[
				'type'              => 'integer',
				'description'       => __( 'Page whose direct published children are listed as services.', 'jmc-theme' ),
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'show_in_rest'      => true,
			]
		);

		register_post_meta(
			'page',
			JMC_SERVICE_ICON_META,
			[
				'type'              => 'string',
				'description'       => __( 'Registered icon shown on the page’s service card.', 'jmc-theme' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'jmc_sanitize_service_icon',
				'auth_callback'     => function ( bool $allowed, string $meta_key, int $post_id ): bool {
					unset( $allowed, $meta_key );

					return current_user_can( 'edit_post', $post_id );
				},
				'show_in_rest'      => true,
			]
		);
	}
);

add_action(
	'admin_init',
	function (): void {
		add_settings_field(
			JMC_SERVICES_PAGE_OPTION,
			__( 'Services page', 'jmc-theme' ),
			function (): void {
				wp_dropdown_pages(
					[
						'name'              => esc_attr( JMC_SERVICES_PAGE_OPTION ),
						'id'                => esc_attr( JMC_SERVICES_PAGE_OPTION ),
						'selected'          => absint( get_option( JMC_SERVICES_PAGE_OPTION, 0 ) ),
						'show_option_none'  => esc_html__( '— Use the page with the “services” slug —', 'jmc-theme' ),
						'option_none_value' => '0',
					]
				);
				printf(
					'<p class="description">%s</p>',
					esc_html__( 'Direct published child pages of this page are listed in the Service Grid block.', 'jmc-theme' )
				);
			},
			'reading',
			'default',
			[ 'label_for' => JMC_SERVICES_PAGE_OPTION ]
		);
	}
);

add_action(
	'enqueue_block_editor_assets',
	function (): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// The service icon panel only applies when editing pages.
		if ( null === $screen || 'page' !== $screen->post_type ) {
			return;
		}

		$asset_path = get_theme_file_path( 'build/js/editor/service-icon.asset.php' );

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset = require $asset_path;

		wp_enqueue_script(
			'jmc-service-icon',
			get_theme_file_uri( 'build/js/editor/service-icon.js' ),
			$asset['dependencies'] ?? [],
			$asset['version'] ?? null,
			true
		);

		$style_path = get_theme_file_path( 'build/js/editor/service-icon.css' );

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'jmc-service-icon',
				get_theme_file_uri( 'build/js/editor/service-icon.css' ),
				[ 'wp-components' ],
				(string) filemtime( $style_path )
			);
		}

		wp_add_inline_script(
			'jmc-service-icon',
			'window.jmcServices = ' . wp_json_encode(
				[
					'pageId'  => jmc_get_services_page()['id'],
					'metaKey' => JMC_SERVICE_ICON_META,
				]
			) . ';',
			'before'
		);

		wp_set_script_translations( 'jmc-service-icon', 'jmc-theme' );
	}
);
