<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

$variant = jm_html_allowed_value(
    value: $block->context['jm/variant'] ?? null,
    allowed: [
        'default',
        'hero',
    ],
    default: 'default'
);


$variants = [
    'default' => null,
    'hero' => "jm-hero__text-para"
];

jm_component(
    name: 'paragraph',
    args: [
        'text' => $attributes['text'] ?? '',
        'classes' => [
            $attributes['className'] ?? null,
            $variants[$variant] ?? null,

        ],
        'id' => $attributes['anchor'] ?? ''
    ]
);
