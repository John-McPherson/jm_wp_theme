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
 *     link?: array{
 *         url?: string,
 *         target?: string,
 *         rel?: string
 *     },
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

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
		'link'    => $attributes['link'] ?? [],
		'classes' => [
			$attributes['className'] ?? null,
			$variant_classes[ $variant ],
		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
