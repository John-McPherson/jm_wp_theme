<?php

declare(strict_types=1);

/**
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

$variant = jmchtml_allowed_value(
	value: $attributes['buttonType'] ?? null,
	allowed: [
		'primary',
		'secondary',
	],
	default: 'primary'
);

$variant_classes = [
	'primary'   => null,
	'secondary' => 'jm-button__secondary',
];

jmccomponent(
	name: 'button-link',
	args: [
		'text'    => $attributes['text'] ?? '',
		'link'    => $attributes['link'] ?? [],
		'classes' => [
			$attributes['className'] ?? null,
			$variant_classes[$variant],
		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
