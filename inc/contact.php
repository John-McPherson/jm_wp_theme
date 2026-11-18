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
