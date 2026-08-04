<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     level?: int,
 *     classes?: string|string[]
 * } $attributes
 */


$variant = jmchtml_allowed_value(
	value: $block->context['jm/variant'] ?? null,
	allowed: [
		'default',
		'hero',
	],
	default: 'default'
);

$variants = [
	'default' => null,
	'hero'    => 'jm-hero__text-heading',
];

jmccomponent(
	name: 'heading',
	args: [
		'text'    => $attributes['text'] ?? '',
		'level'   => $attributes['level'] ?? 2,
		'classes' => [
			$attributes['className'] ?? null,
			$variants[$variant] ?? null,

		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
