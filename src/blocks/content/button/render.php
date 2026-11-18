<?php
/**
 * Render the button content block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the button block.
 *
 * @var array{
 *     text?: string,
 *     buttonType?: string,
 *     linkType?: string,
 *     emailSubject?: string,
 *     emailBody?: string,
 *     whatsappMessage?: string,
 *     link?: array{
 *         url?: string,
 *         opensInNewTab?: bool,
 *         target?: string,
 *         rel?: string
 *     },
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

$button_link = is_array( $attributes['link'] ?? null )
	? $attributes['link']
	: [];

$link_type = jmc_html_allowed_value(
	value: $attributes['linkType'] ?? null,
	allowed: [
		'link',
		'phone',
		'email',
		'whatsapp',
	],
	fallback_value: 'link'
);

// Phone, email and WhatsApp buttons use the site-wide business details, not their own link.
// Email and WhatsApp buttons can add their own pre-filled message.
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

if ( '' === $url ) {
	return;
}

// `opensInNewTab` is saved by the editor; `target` is kept for content saved before it.
// Telephone and email links hand off to the dialler or mail app, so never open them in a new tab.
$opens_in_new_tab = (
	true === ( $button_link['opensInNewTab'] ?? false )
	|| '_blank' === ( $button_link['target'] ?? '' )
) && ! str_starts_with( $url, 'tel:' )
	&& ! str_starts_with( $url, 'mailto:' );

$variant = jmc_html_allowed_value(
	value: $attributes['buttonType'] ?? null,
	allowed: [
		'primary',
		'secondary',
	],
	fallback_value: 'primary'
);

$variant_classes = [
	'primary'   => null,
	'secondary' => 'jmc-button__secondary',
];

jmc_component(
	name: 'button-link',
	args: [
		'text'    => $attributes['text'] ?? '',
		'link'    => [
			'url'    => $url,
			'target' => $opens_in_new_tab ? '_blank' : '',
		],
		'classes' => [
			$attributes['className'] ?? null,
			$variant_classes[ $variant ],
		],
		'id'      => $attributes['anchor'] ?? '',
		'icon'    => 'whatsapp' === $link_type ? 'jmc/whatsapp' : '',
	]
);
