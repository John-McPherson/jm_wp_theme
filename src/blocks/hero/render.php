<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string
 * } $attributes
 * @var string $content
 */


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
    'classes' => [
        'jm-section',
        'jm-hero',
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