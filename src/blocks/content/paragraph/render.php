<?php
/**
 * Render the paragraph block with optional variant and anchor settings.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the paragraph block.
 *
 * @var array{
 *     text?: string,
 *     variant?: string,
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

$context_variant = jmc_html_allowed_value(
	value: $block->context['jm/variant'] ?? null,
	allowed: [
		'default',
		'hero',
	],
	fallback_value: 'default'
);

$paragraph_variant = jmc_html_allowed_value(
	value: $attributes['variant'] ?? null,
	allowed: [
		'default',
		'label',
	],
	fallback_value: 'default'
);

$context_classes = [
	'default' => null,
	'hero'    => 'jmc-hero__text-para',
];

$paragraph_classes = [
	'default' => null,
	'label'   => 'jmc-label',
];

$context_class = 'label' === $paragraph_variant
	? null
	: $context_classes[ $context_variant ];

jmc_component(
	name: 'paragraph',
	args: [
		'text'    => $attributes['text'] ?? '',
		'classes' => [
			$attributes['className'] ?? null,
			$paragraph_classes[ $paragraph_variant ],
			$context_class,
		],
		'id'      => $attributes['anchor'] ?? '',
	]
);
