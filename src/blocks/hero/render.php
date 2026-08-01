<?php

declare(strict_types=1);

/**
 * @var array{
 *     label?: string,
 *     heading?: string,
 *     text?: string,
 *     imageId?: int|string,
 *     linkText?: string,
 *     link?: array{
 *         url?: string
 *     }
 * } $attributes
 */

$label = jm_args_string(
    args: $attributes,
    key: 'label'
);

$heading = jm_args_string(
    args: $attributes,
    key: 'heading'
);

$text = jm_args_string(
    args: $attributes,
    key: 'text'
);

$link_text = jm_args_string(
    args: $attributes,
    key: 'linkText'
);

$link = $attributes['link'] ?? [];

$link_url = is_array($link)
    ? jm_args_string(
        args: $link,
        key: 'url'
    )
    : '';

$image_id = jm_args_int(
    args: $attributes,
    key: 'imageId',
    default: 0,
    min: 1
);

if (
    $label === ''
    && $heading === ''
    && $text === ''
    && $link_text === ''
    && $image_id === 0
) {
    return;
}

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
        <?php if ($label !== '' || $heading !== '') : ?>
            <div class="jm-hero__headings">
                <?php
                if ($label !== '') {
                    jm_component(
                        name: 'paragraph',
                        args: [
                            'text' => $label,
                            'classes' => [
                                'jm-hero__label',
                            ],

                        ]
                    );
                }

                if ($heading !== '') {
                    jm_component(
                        name: 'heading',
                        args: [
                            'text' => $heading,
                            'classes' => [
                                'jm-hero__heading',
                            ],
                            'level' => 1,
                        ]
                    );
                }
                ?>
            </div>
        <?php endif; ?>

        <?php
        if ($text !== '') {
            jm_component(
                name: 'paragraph',
                args: [
                    'text' => $text,
                    'classes' => [
                        'jm-hero__text-content',
                        'p2',
                    ],
                ]
            );
        }

        if ($link_text !== '' && $link_url !== '') {
            jm_component(
                name: 'button-link',
                args: [
                    'text' => $link_text,
                    'url' => $link_url,
                    'classes' => [
                        'jm-button--secondary',
                    ],
                ]
            );
        }
        ?>
    </div>

    <?php if (is_string($image_url) && $image_url !== '') : ?>
        <div <?php jm_the_attributes($image_attributes); ?>></div>
    <?php endif; ?>
</section>