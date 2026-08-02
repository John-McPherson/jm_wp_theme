<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     variant?: string,
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

$context_variant = jm_html_allowed_value(
    value: $block->context['jm/variant'] ?? null,
    allowed: [
        'default',
        'hero',
    ],
    default: 'default'
);

$paragraph_variant = jm_html_allowed_value(
    value: $attributes['variant'] ?? null,
    allowed: [
        'default',
        'label',
    ],
    default: 'default'
);

$context_classes = [
    'default' => null,
    'hero' => 'jm-hero__text-para',
];

$paragraph_classes = [
    'default' => null,
    'label' => 'jm-label',
];

$context_class = $paragraph_variant === 'label'
    ? null
    : $context_classes[$context_variant];

jm_component(
    name: 'paragraph',
    args: [
        'text' => $attributes['text'] ?? '',
        'classes' => [
            $attributes['className'] ?? null,
            $paragraph_classes[$paragraph_variant],
            $context_class,
        ],
        'id' => $attributes['anchor'] ?? '',
    ]
);
