<?php
/**
 * Render the heading block with optional hero variant styling.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the heading block.
 *
 * @var array{
 *     text?: string,
 *     level?: int,
 *     classes?: string|string[]
 * } $attributes
 */


$variant = jmc_html_allowed_value(
	value: $block->context['jm/variant'] ?? null,
	allowed: [
		'default',
		'hero',
	],
	fallback_value: 'default'
);

$variants = [
	'default' => null,
	'hero'    => 'jmc-hero__text-heading',
];

jmc_component(
	name: 'heading',
	args: [
		'text'    => $attributes['text'] ?? '',
		'level'   => $attributes['level'] ?? 2,
		'classes' => [
			$attributes['className'] ?? null,
			$variants[ $variant ] ?? null,

		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
