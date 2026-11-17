<?php
/**
 * Register the site-wide business contact settings.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Business contact settings: option name => [ label, input type, description, sanitiser ].
 *
 * @return array<string, array{label: string, type: string, description: string, sanitize: callable-string}>
 */
function jmc_business_contact_settings(): array {
	return [
		JMC_BUSINESS_PHONE_OPTION => [
			'label'       => __( 'Business phone number', 'jmc-theme' ),
			'type'        => 'tel',
			'description' => __( 'Used by Phone buttons. Write it as it should be read, e.g. 01234 567890.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_phone',
		],
		JMC_BUSINESS_EMAIL_OPTION => [
			'label'       => __( 'Business email address', 'jmc-theme' ),
			'type'        => 'email',
			'description' => __( 'Used by Email buttons.', 'jmc-theme' ),
			'sanitize'    => 'jmc_sanitize_business_email',
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
					'type'              => 'string',
					'description'       => $setting['label'],
					'sanitize_callback' => $setting['sanitize'],
					'default'           => '',
					'show_in_rest'      => true,
				]
			);
		}
	}
);

add_action(
	'admin_init',
	function (): void {
		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			add_settings_field(
				$option,
				$setting['label'],
				function () use ( $option, $setting ): void {
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
				'general',
				'default',
				[ 'label_for' => $option ]
			);
		}
	}
);
