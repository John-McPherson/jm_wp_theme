<?php

//todo  allow for reading order to be reversed + pallete controls


$args = [
    'label'   => ['text' => $attributes['label'] ?? '', 'classes' => 'label'],
    'heading' => ['text' => $attributes['heading'] ?? '', 'classes' => 'heading'],
    'text'    => ['text' => $attributes['text'] ?? '', 'classes' => 'text'],
    'button'  => ['text' => $attributes['linkText'] ?? '', 'url' => $attributes['link']['url'] ?? ''],
    'image'   => ['image_id' => !empty($attributes['imageId']) ? (int) $attributes['imageId'] : 0, 'classes' => 'jm-text-with-image__image'],
];

?>

<section class="jm-section jm-text-with-image">
    <div class="jm-text-with-image__container">
        <div class="column">

            <?php get_template_part(slug: 'template-parts/paragraph', name: null, args: $args['label']); ?>

            <div class="jm-text-with-image__text">

                <?php
                get_template_part(slug: 'template-parts/heading', name: null, args: $args['heading']);
                get_template_part(slug: 'template-parts/paragraph', name: null, args: $args['text']);
                ?>

            </div>

            <?php get_template_part(slug: 'template-parts/button', name: 'link',  args: $args['button']); ?>

        </div>

        <div class="column">
            <?php
            get_template_part(slug: 'template-parts/image', name: null, args: $args['image']);
            ?>
        </div>
    </div>
</section>