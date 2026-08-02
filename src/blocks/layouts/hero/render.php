<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette : string,
 * } $attributes
 * @var string $content
 */


$palette = jm_html_allowed_value(
    value: $attributes['palette'] ?? null,
    allowed: [
        'primary',
        'secondary',
        'inverse'
    ],
    default: 'primary'
);

$palette_classes = [
    'primary' =>   "jm-palette--default",
    'secondary' => "jm-palette--secondary",
    'inverse' =>    "jm-palette--inverse",
];


$image_id = jm_args_int(
    args: $attributes,
    key: 'imageId',
    default: 0,
    min: 1
);


$image_url = $image_id > 0
    ? wp_get_attachment_image_url($image_id, 'full')
    : false;

$section_attributes = [
    'id' => $attributes['anchor'] ?? null,
    'classes' => [
        'jm-section',
        'jm-hero',
        $palette_classes[$palette],
        $attributes['className'] ?? null,
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

<section <?php jm_the_attributes($section_attributes); ?>>
    <div class="jm-hero__text">
        <?php echo $content ?>
    </div>

    <?php if (is_string($image_url) && $image_url !== '') : ?>
        <div <?php jm_the_attributes($image_attributes); ?>></div>
    <?php endif; ?>
</section>