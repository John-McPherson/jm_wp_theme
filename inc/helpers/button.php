<?php
/**
 * Helpers for blocks that render a button: its style and where it links to.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Button link types. `link` uses the button's own link; the others use the
 * site-wide business details from Settings → Business.
 */
const JMC_BUTTON_LINK_TYPES = [ 'link', 'phone', 'email', 'whatsapp' ];

/**
 * Button style classes, keyed by button type. Primary is the default style.
 */
const JMC_BUTTON_VARIANT_CLASSES = [
	'primary'   => null,
	'secondary' => 'jmc-button__secondary',
];

/**
 * Turn button block attributes into `button-link` component arguments.
 *
 * Phone, email and WhatsApp buttons link to the business details, not their
 * own link, and email and WhatsApp buttons can add a pre-filled message. An
 * unknown link type falls back to `link` and an unknown button type to
 * `primary`. When the destination is missing or invalid the URL is empty, and
 * the component renders nothing.
 *
 * @param array<string, mixed> $attributes Block attributes: `text`, `buttonType`, `linkType`,
 *                                         `link` (`{ url, opensInNewTab }`), `emailSubject`,
 *                                         `emailBody` and `whatsappMessage`.
 *
 * @return array{
 *     text: string,
 *     link: array<string, mixed>,
 *     classes: string|null,
 *     icon: string
 * } Arguments for the `button-link` component; `classes` is the style class.
 */
function jmc_button_link_args( array $attributes ): array {
	$button_link = is_array( $attributes['link'] ?? null )
		? $attributes['link']
		: [];

	$link_type = jmc_html_allowed_value(
		value: $attributes['linkType'] ?? null,
		allowed: JMC_BUTTON_LINK_TYPES,
		fallback_value: 'link'
	);

	$variant = jmc_html_allowed_value(
		value: $attributes['buttonType'] ?? null,
		allowed: array_keys( JMC_BUTTON_VARIANT_CLASSES ),
		fallback_value: 'primary'
	);

	$url = 'link' === $link_type
		? jmc_safe_link_url(
			url: $button_link['url'] ?? null,
			allow_mailto: true
		)
		: jmc_contact_url_with_message(
			url: jmc_business_contact_url( $link_type ),
			message: [
				'subject' => $attributes['emailSubject'] ?? null,
				'body'    => $attributes['emailBody'] ?? null,
				'text'    => $attributes['whatsappMessage'] ?? null,
			]
		);

	return [
		'text'    => jmc_args_string(
			args: $attributes,
			key: 'text'
		),
		// The component decides whether the stored link opens in a new tab.
		'link'    => [ 'url' => $url ] + $button_link,
		'classes' => JMC_BUTTON_VARIANT_CLASSES[ $variant ],
		'icon'    => 'whatsapp' === $link_type ? 'jmc/whatsapp' : '',
	];
}
