<?php

$args = $args ?? [];

$image_id = (int) $args['image_id'] ?? 0;
$classes = $args['classes'] ?? '';

if (!empty($image_id)) {
    $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

    echo wp_get_attachment_image(
        attachment_id: $image_id,
        size: 'large',
        icon: false,
        attr: [
            'class'   => trim("jm-image $classes"),
            'loading' => 'lazy',
            'alt'     => trim($alt) ?: '',
            'sizes'   => '(max-width: 768px) 100vw, 50vw',
        ]
    );
}
