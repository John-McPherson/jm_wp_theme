<?php
/**
 * Register the site-wide business contact settings.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Business contact settings: option name => [ label, input type, description, sanitiser, REST schema ].
 *
 * Input types are HTML input types, plus `hours` (a time pair per day) and
 * `lines` (a textarea holding one entry per line).
 *
 * @return array<string, array{label: string, type: string, description: string, sanitize: callable-string, schema: array<string, mixed>}>
 */
function jmc_business_contact_settings(): array {
	$time = [
		'type'    => 'string',
		'pattern' => '^(?:[01]\d|2[0-3]):[0-5]\d$',
	];

	return [
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
			'description' => __( 'Used by Email buttons, and where enquiries from the enquiry form are sent.', 'jmc-theme' ),
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
			// Exposed on the REST `settings` endpoint so the block editor can read and edit it.
			register_setting(
				'general',
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
		printf( '<p><strong>%s</strong> ', esc_html( $day_label ) );

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

add_action(
	'admin_init',
	function (): void {
		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			$field = match ( $setting['type'] ) {
				'hours' => function () use ( $option, $setting ): void {
					jmc_business_hours_field( option: $option, setting: $setting );
				},
				'lines' => function () use ( $option, $setting ): void {
					printf(
						'<textarea name="%1$s" id="%1$s" rows="5" class="regular-text" aria-describedby="%1$s-description">%2$s</textarea>',
						esc_attr( $option ),
						esc_textarea(
							implode( "\n", jmc_sanitize_business_service_area( get_option( $option, [] ) ) )
						)
					);
					printf(
						'<p class="description" id="%s-description">%s</p>',
						esc_attr( $option ),
						esc_html( $setting['description'] )
					);
				},
				default => function () use ( $option, $setting ): void {
					printf(
						'<input name="%1$s" id="%1$s" type="%2$s" value="%3$s" class="regular-text" aria-describedby="%1$s-description" />',
						esc_attr( $option ),
						esc_attr( $setting['type'] ),
						esc_attr( (string) get_option( $option, '' ) )
					);
					printf(
						'<p class="description" id="%s-description">%s</p>',
						esc_attr( $option ),
						esc_html( $setting['description'] )
					);
				},
			};

			add_settings_field(
				$option,
				$setting['label'],
				$field,
				'general',
				'default',
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
 * Copy the footer business block's old phone, email and service area into the settings.
 *
 * Before #115 the footer stored these as block attributes. This runs once,
 * recorded in the `jmc_footer_contact_migrated` option. It reads the
 * customised footer template part for the active theme (the theme's own
 * `parts/footer.html` has no values) and fills only settings that are still
 * empty, so details already entered in the settings win. Values are sanitised
 * as if submitted; a value the settings reject (such as a phone number that
 * can't be dialled) is not copied, but stays in the block's attributes, so no
 * content is deleted.
 *
 * @return void
 */
function jmc_migrate_footer_contact_details(): void {
	if ( '1' === get_option( JMC_FOOTER_CONTACT_MIGRATED_OPTION ) ) {
		return;
	}

	$parts = get_posts(
		[
			'post_type'      => 'wp_template_part',
			'name'           => 'footer',
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One indexed lookup, run once.
			'tax_query'      => [
				[
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => get_stylesheet(),
				],
			],
		]
	);

	$attributes = [] === $parts
		? null
		: jmc_find_block_attributes( parse_blocks( (string) $parts[0]->post_content ), 'jmc/footer-business' );

	if ( null !== $attributes ) {
		$legacy = [
			JMC_BUSINESS_PHONE_OPTION        => jmc_sanitize_business_phone( jmc_plain_text( $attributes['phone'] ?? null ) ),
			JMC_BUSINESS_EMAIL_OPTION        => jmc_sanitize_business_email( jmc_plain_text( $attributes['email'] ?? null ) ),
			JMC_BUSINESS_SERVICE_AREA_OPTION => jmc_split_service_area_text( jmc_plain_text( $attributes['serviceArea'] ?? null ) ),
		];

		$settings = jmc_business_contact_settings();

		foreach ( $legacy as $option => $value ) {
			$current = call_user_func( $settings[ $option ]['sanitize'], get_option( $option, $settings[ $option ]['schema']['default'] ) );

			if ( '' !== $value && [] !== $value && ( '' === $current || [] === $current ) ) {
				update_option( $option, $value );
			}
		}
	}

	// Autoloaded, so later requests check the flag without a query.
	update_option( JMC_FOOTER_CONTACT_MIGRATED_OPTION, '1', true );
}

add_action( 'init', 'jmc_migrate_footer_contact_details', 20 );
