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
	],
	fallback_value: 'link'
);

// Phone and email buttons use the site-wide business details, not their own link.
$url = 'link' === $link_type
	? jmc_safe_link_url(
		url: $button_link['url'] ?? null,
		allow_mailto: true
	)
	: jmc_business_contact_url( $link_type );

if ( '' === $url ) {
	return;
}

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
		// The component decides whether the stored link opens in a new tab.
		'link'    => [ 'url' => $url ] + $button_link,
		'classes' => [
			$attributes['className'] ?? null,
			$variant_classes[ $variant ],
		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
