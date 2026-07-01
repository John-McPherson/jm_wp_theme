<?php

$heading = $attributes['heading'] ?? '';
$text = $attributes['text'] ?? '';
$background_image_id = $attributes['imageId'] ?? '';
$background_image_url = esc_url($background_image_id ? wp_get_attachment_image_url($background_image_id, 'full') : '');

if (empty($heading) && empty($text) && empty($background_image_id)) {
    return;
}

$background_style = $background_image_url
    ? "--background-image: url('{$background_image_url}');"
    : '';
?>

<section class="jm-section jm-hero">
    <div class="jm-hero__text">
        <?php get_template_part(
            slug: 'template-parts/heading',
            name: null,
            args: [
                'text'    => $heading,
                'classes' => 'heading',
                'level' => '1',
            ]
        );
        ?>

        <?php
        get_template_part(
            slug: 'template-parts/paragraph',
            name: null,
            args: [
                'text'    => $text,
                'classes' => 'text',
            ]
        ); ?>
    </div>
    <div class="jm-hero__img" style="<?php echo esc_attr($background_style); ?>">

    </div>
</section>