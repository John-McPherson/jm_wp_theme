<?php

declare(strict_types=1);

/**
 * @var array{
 *     label?: string,
 *     heading?: string,
 *     text?: string,
 *     linkText?: string,
 *     link?: array{url?: string},
 *     imageId?: int|string,
 *     palette?: string
 * } $attributes
 */

$palette = jm_html_allowed_value(
    value: $attributes['palette'] ?? null,
    allowed: [
        'jm-palette--default',
        'jm-palette--inverse',
        'jm-palette--accent',
    ],
    default: 'jm-palette--default'
);

$image_id = jm_args_int(
    args: $attributes,
    key: 'imageId',
    default: 0,
    min: 1
);

$image_url = $image_id > 0
    ? wp_get_attachment_image_url($image_id, 'full')
    : false;

$components = [
    'label' => [
        'text'    => $attributes['label'] ?? '',
        'classes' => 'jm-hero__label',
    ],
    'heading' => [
        'text'    => $attributes['heading'] ?? '',
        'classes' => 'jm-hero__heading',
        'level'   => 1,
    ],
    'text' => [
        'text'    => $attributes['text'] ?? '',
        'classes' => [
            'jm-hero__text-content',
            'p2',
        ],
    ],
    'button' => [
        'text' => $attributes['linkText'] ?? '',
        'url'  => $attributes['link']['url'] ?? '',
    ],
];

$section_attributes = [
    'classes' => [
        'jm-section',
        'jm-hero',
        $palette,
    ],
];

$image_attributes = [
    'classes' => [
        'jm-hero__img',
    ],
    'aria-hidden' => 'true',
];

if (is_string($image_url) && $image_url !== '') {
    $image_attributes['style'] = [
        '--background-image' => sprintf(
            "url('%s')",
            esc_url($image_url)
        ),
    ];
}
?>

<section<?php jm_the_attributes($section_attributes); ?>>
    <div class="jm-hero__text">
        <?php jm_component('paragraph', $components['label']); ?>

        <div class="jm-hero__headings">
            <?php jm_component('heading', $components['heading']); ?>
        </div>

        <?php jm_component('paragraph', $components['text']); ?>

        <?php jm_component('button-link', $components['button']); ?>
    </div>

    <?php if (is_string($image_url) && $image_url !== '') : ?>
        <div<?php jm_the_attributes($image_attributes); ?>>
            </div>
        <?php endif; ?>

        </section>