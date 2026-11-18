<?php
/**
 * Register the site-wide business contact settings.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Slug of the Settings → Business admin page, also its settings group and section page.
 */
const JMC_BUSINESS_SETTINGS_PAGE = 'jmc-business-settings';

/**
 * Business settings: option name => [ label, input type, description, sanitiser, REST schema ].
 *
 * Input types are HTML input types, plus `hours` (a time pair per day),
 * `textarea` (multi-line text) and `lines` (a textarea holding one list entry
 * per line).
 *
 * @return array<string, array{label: string, type: string, description: string, sanitize: callable-string, schema: array<string, mixed>}>
 */
function jmc_business_contact_settings(): array {
	$time = [
		'type'    => 'string',
		'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$',
	];

	return [
		JMC_BUSINESS_NAME_OPTION         => [
			'label'       => __( 'Business name', 'jmc-theme' ),
			'type'        => 'text',
			'description' => __( 'Shown at the top of the site footer.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_name',
			'schema'      => [
				'type'    => 'string',
				'default' => '',
			],
		],
		JMC_BUSINESS_ADDRESS_OPTION      => [
			'label'       => __( 'Business address', 'jmc-theme' ),
			'type'        => 'textarea',
			'description' => __( 'Shown in the site footer. Put each line of the address on its own line.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_address',
			'schema'      => [
				'type'    => 'string',
				'default' => '',
			],
		],
		JMC_BUSINESS_PHONE_OPTION        => [
			'label'       => __( 'Business phone number', 'jmc-theme' ),
			'type'        => 'tel',
			'description' => __( 'Used by Phone buttons. Write it as it should be read, e.g. 01234 567890.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_phone',
			'schema'      => [
				'type'    => 'string',
				'default' => '',
			],
		],
		JMC_BUSINESS_EMAIL_OPTION        => [
			'label'       => __( 'Business email address', 'jmc-theme' ),
			'type'        => 'email',
			'description' => __( 'Used by Email buttons.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_email',
			'schema'      => [
				'type'    => 'string',
				'default' => '',
			],
		],
		JMC_BUSINESS_WHATSAPP_OPTION     => [
			'label'       => __( 'WhatsApp number', 'jmc-theme' ),
			'type'        => 'tel',
			'description' => __( 'Used by WhatsApp buttons. Include the country code, e.g. +44 7700 900123.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_whatsapp',
			'schema'      => [
				'type'    => 'string',
				'default' => '',
			],
		],
		JMC_BUSINESS_HOURS_OPTION        => [
			'label'       => __( 'Opening hours', 'jmc-theme' ),
			'type'        => 'hours',
			'description' => __( 'Leave both times empty on days you are closed. Closing time must be after opening time.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_hours',
			'schema'      => [
				'type'                 => 'object',
				'default'              => [],
				'properties'           => array_fill_keys(
					JMC_BUSINESS_DAYS,
					[
						'type'                 => 'object',
						'properties'           => [
							'opens'  => $time,
							'closes' => $time,
						],
						'additionalProperties' => false,
					]
				),
				'additionalProperties' => false,
			],
		],
		JMC_BUSINESS_SERVICE_AREA_OPTION => [
			'label'       => __( 'Service area', 'jmc-theme' ),
			'type'        => 'lines',
			'description' => __( 'The towns and areas you cover, one per line.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_service_area',
			'schema'      => [
				'type'    => 'array',
				'default' => [],
				'items'   => [ 'type' => 'string' ],
			],
		],
	];
}

add_action(
	'init',
	function (): void {
		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			// Saved from the Business settings page (options.php requires `manage_options`
			// for its group) and exposed on the REST `settings` endpoint so the block
			// editor can read and edit it.
			register_setting(
				JMC_BUSINESS_SETTINGS_PAGE,
				$option,
				[
					'type'              => $setting['schema']['type'],
					'description'       => $setting['label'],
					'sanitize_callback' => $setting['sanitize'],
					'default'           => $setting['schema']['default'],
					'show_in_rest'      => [ 'schema' => $setting['schema'] ],
				]
			);
		}
	}
);

/**
 * Print the opening-hours settings field: an opening and closing time per day.
 *
 * @param string                                    $option  Option name.
 * @param array{label: string, description: string} $setting Setting definition.
 *
 * @return void
 */
function jmc_business_hours_field( string $option, array $setting ): void {
	$hours = jmc_sanitize_business_hours( get_option( $option, [] ) );

	printf(
		'<fieldset aria-describedby="%1$s-description"><legend class="screen-reader-text">%2$s</legend>',
		esc_attr( $option ),
		esc_html( $setting['label'] )
	);

	$times = [
		'opens'  => __( 'opens', 'jmc-theme' ),
		'closes' => __( 'closes', 'jmc-theme' ),
	];

	foreach ( jmc_business_day_labels() as $day => $day_label ) {
		// A fixed-width day name lines the time inputs up in columns.
		printf( '<p><strong style="display: inline-block; min-width: 7.5em;">%s</strong> ', esc_html( $day_label ) );

		foreach ( $times as $key => $time_label ) {
			$id = "{$option}-{$day}-{$key}";

			printf(
				'<label for="%1$s"><span class="screen-reader-text">%7$s </span>%2$s</label> <input name="%3$s[%4$s][%5$s]" id="%1$s" type="time" value="%6$s" /> ',
				esc_attr( $id ),
				esc_html( $time_label ),
				esc_attr( $option ),
				esc_attr( $day ),
				esc_attr( $key ),
				esc_attr( $hours[ $day ][ $key ] ?? '' ),
				esc_html( $day_label )
			);
		}

		echo '</p>';
	}

	printf(
		'<p class="description" id="%s-description">%s</p></fieldset>',
		esc_attr( $option ),
		esc_html( $setting['description'] )
	);
}

/**
 * Print a business setting's form control and description.
 *
 * @param string                                                                                                           $option  Option name.
 * @param array{label: string, type: string, description: string, sanitize: callable-string, schema: array<string, mixed>} $setting Setting definition.
 *
 * @return void
 */
function jmc_business_setting_field( string $option, array $setting ): void {
	if ( 'hours' === $setting['type'] ) {
		jmc_business_hours_field( option: $option, setting: $setting );
		return;
	}

	if ( 'lines' === $setting['type'] || 'textarea' === $setting['type'] ) {
		$value = call_user_func( $setting['sanitize'], get_option( $option, $setting['schema']['default'] ) );

		printf(
			'<textarea name="%1$s" id="%1$s" rows="5" class="regular-text" aria-describedby="%1$s-description">%2$s</textarea>',
			esc_attr( $option ),
			esc_textarea( is_array( $value ) ? implode( "\n", $value ) : $value )
		);
	} else {
		printf(
			'<input name="%1$s" id="%1$s" type="%2$s" value="%3$s" class="regular-text" aria-describedby="%1$s-description" />',
			esc_attr( $option ),
			esc_attr( $setting['type'] ),
			esc_attr( (string) get_option( $option, '' ) )
		);
	}

	printf(
		'<p class="description" id="%s-description">%s</p>',
		esc_attr( $option ),
		esc_html( $setting['description'] )
	);
}

/**
 * Print the Settings → Business admin page.
 *
 * WordPress only routes users with `manage_options` here (see the `admin_menu`
 * hook) and prints the "Settings saved" notice for Settings submenu pages.
 *
 * @return void
 */
function jmc_business_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( JMC_BUSINESS_SETTINGS_PAGE );
			do_settings_sections( JMC_BUSINESS_SETTINGS_PAGE );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_menu',
	function (): void {
		add_options_page(
			__( 'Business settings', 'jmc-theme' ),
			__( 'Business', 'jmc-theme' ),
			'manage_options',
			JMC_BUSINESS_SETTINGS_PAGE,
			'jmc_business_settings_page'
		);
	}
);

add_action(
	'admin_init',
	function (): void {
		add_settings_section(
			'jmc_business_contact',
			__( 'Business details', 'jmc-theme' ),
			function (): void {
				printf(
					'<p>%s</p>',
					esc_html__( 'Shared across the site: shown in the footer and Contact Details blocks and used by phone, email and WhatsApp buttons. They can also be edited from those blocks\' sidebars.', 'jmc-theme' )
				);
			},
			JMC_BUSINESS_SETTINGS_PAGE
		);

		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			add_settings_field(
				$option,
				$setting['label'],
				function () use ( $option, $setting ): void {
					jmc_business_setting_field( option: $option, setting: $setting );
				},
				JMC_BUSINESS_SETTINGS_PAGE,
				'jmc_business_contact',
				// The hours fieldset has its own legend, so its row heading isn't a <label>.
				'hours' === $setting['type'] ? [] : [ 'label_for' => $option ]
			);
		}
	}
);

/**
 * Option recording that the footer's contact details were copied into the settings.
 */
const JMC_FOOTER_CONTACT_MIGRATED_OPTION = 'jmc_footer_contact_migrated';

/**
 * Split the footer's old free-text service area into places.
 *
 * Places are separated by new lines, commas, semicolons, `&` or the word
 * "and", so "Pudsey, Leeds and Bradford" becomes three places. The result is
 * sanitised like any submitted service area (a failed split counts as none).
 *
 * @param string $text Plain-text service area.
 *
 * @return list<string> Place names in the order given.
 */
function jmc_split_service_area_text( string $text ): array {
	return jmc_sanitize_business_service_area(
		preg_split( '/\s*(?:[\n,;&]|\band\b)\s*/iu', $text )
	);
}

/**
 * Find the attributes of the first block with a given name.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed blocks, searched depth first.
 * @param string                         $name   Block name, e.g. `jmc/footer-business`.
 *
 * @return array<string,mixed>|null The block's attributes, or null when it isn't found.
 */
function jmc_find_block_attributes( array $blocks, string $name ): ?array {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		if ( ( $block['blockName'] ?? null ) === $name ) {
			return is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
		}

		$inner = jmc_find_block_attributes( is_array( $block['innerBlocks'] ?? null ) ? $block['innerBlocks'] : [], $name );

		if ( null !== $inner ) {
			return $inner;
		}
	}

	return null;
}

/**
 * Find the attributes of the active theme's customised footer business block.
 *
 * Reads the customised footer template part (the theme's own
 * `parts/footer.html` has no values).
 *
 * @return array<string,mixed>|null The block's attributes, or null when there is no customised footer or block.
 */
function jmc_footer_business_attributes(): ?array {
	$parts = get_posts(
		[
			'post_type'      => 'wp_template_part',
			'name'           => 'footer',
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One indexed lookup, run once per migration.
			'tax_query'      => [
				[
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => get_stylesheet(),
				],
			],
		]
	);

	return [] === $parts
		? null
		: jmc_find_block_attributes( parse_blocks( (string) $parts[0]->post_content ), 'jmc/footer-business' );
}

/**
 * Store already-sanitised values in business settings that are still empty.
 *
 * A stored value the setting's sanitiser rejects counts as empty, and empty
 * values are never stored, so details already entered in the settings win.
 *
 * @param array<string, string|list<string>> $values Sanitised values keyed by option name.
 *
 * @return void
 */
function jmc_fill_empty_business_settings( array $values ): void {
	$settings = jmc_business_contact_settings();

	foreach ( $values as $option => $value ) {
		$current = call_user_func( $settings[ $option ]['sanitize'], get_option( $option, $settings[ $option ]['schema']['default'] ) );

		if ( '' !== $value && [] !== $value && ( '' === $current || [] === $current ) ) {
			update_option( $option, $value );
		}
	}
}

/**
 * Copy the footer business block's old phone, email and service area into the settings.
 *
 * Before #115 the footer stored these as block attributes. This runs once,
 * recorded in the `jmc_footer_contact_migrated` option, and fills only
 * settings that are still empty. Values are sanitised as if submitted; a value
 * the settings reject (such as a phone number that can't be dialled) is not
 * copied, but stays in the block's attributes, so no content is deleted.
 *
 * @return void
 */
function jmc_migrate_footer_contact_details(): void {
	if ( '1' === get_option( JMC_FOOTER_CONTACT_MIGRATED_OPTION ) ) {
		return;
	}

	$attributes = jmc_footer_business_attributes();

	if ( null !== $attributes ) {
		jmc_fill_empty_business_settings(
			[
				JMC_BUSINESS_PHONE_OPTION        => jmc_sanitize_business_phone( jmc_plain_text( $attributes['phone'] ?? null ) ),
				JMC_BUSINESS_EMAIL_OPTION        => jmc_sanitize_business_email( jmc_plain_text( $attributes['email'] ?? null ) ),
				JMC_BUSINESS_SERVICE_AREA_OPTION => jmc_split_service_area_text( jmc_plain_text( $attributes['serviceArea'] ?? null ) ),
			]
		);
	}

	// Autoloaded, so later requests check the flag without a query.
	update_option( JMC_FOOTER_CONTACT_MIGRATED_OPTION, '1', true );
}

add_action( 'init', 'jmc_migrate_footer_contact_details', 20 );

/**
 * Option recording that the footer's business name and address were copied into the settings.
 */
const JMC_FOOTER_IDENTITY_MIGRATED_OPTION = 'jmc_footer_identity_migrated';

/**
 * Copy the footer business block's old `companyName` and `address` into the settings.
 *
 * Before #121 the footer stored these as block attributes. Like
 * jmc_migrate_footer_contact_details(), this runs once (recorded in the
 * `jmc_footer_identity_migrated` option), fills only settings that are still
 * empty, and leaves the attributes in the block.
 *
 * @return void
 */
function jmc_migrate_footer_business_identity(): void {
	if ( '1' === get_option( JMC_FOOTER_IDENTITY_MIGRATED_OPTION ) ) {
		return;
	}

	$attributes = jmc_footer_business_attributes();

	if ( null !== $attributes ) {
		jmc_fill_empty_business_settings(
			[
				JMC_BUSINESS_NAME_OPTION    => jmc_sanitize_business_name( $attributes['companyName'] ?? null ),
				JMC_BUSINESS_ADDRESS_OPTION => jmc_sanitize_business_address( $attributes['address'] ?? null ),
			]
		);
	}

	// Autoloaded, so later requests check the flag without a query.
	update_option( JMC_FOOTER_IDENTITY_MIGRATED_OPTION, '1', true );
}

add_action( 'init', 'jmc_migrate_footer_business_identity', 20 );
