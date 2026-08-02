<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette?: string,
 *     order?: string
 * } $attributes
 * @var string $content
 */

$palette = jm_html_allowed_value(
    value: $attributes['palette'] ?? null,
    allowed: [
        'default',
        'secondary',
        'inverse',
    ],
    default: 'default'
);

$order = jm_html_allowed_value(
    value: $attributes['order'] ?? null,
    allowed: [
        'left',
        'right',
    ],
    default: 'right'
);

$palette_classes = [
    'default'   => '',
    'secondary' => 'jm-palette--secondary',
    'inverse'   => 'jm-palette--inverse',
];

$order_classes = [
    'left'  => 'jm-column-left',
    'right' => 'jm-column-right',
];


$custom_classes = isset($attributes['className'])
    ? preg_split('/\s+/', trim($attributes['className']))
    : [];

$section_attributes = [
    'classes' => array_filter([
        'jm-section',
        'jm-cta',
        $palette_classes[$palette],
        $order_classes[$order],
        ...$custom_classes,
    ]),
];

if (!empty($attributes['anchor'])) {
    $section_attributes['id'] = sanitize_title(
        $attributes['anchor']
    );
}


?>

<section <?php jm_the_attributes($section_attributes); ?>>
    <div class="jm-section__container">
        <?php echo $content; ?>

    </div>
</section>