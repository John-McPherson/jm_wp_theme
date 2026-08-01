<?php

//todo  allow for reading order to be reversed + palette controls


$args = [
    'label'   => ['text' => $attributes['label'] ?? '', 'classes' => 'label'],
    'heading' => ['text' => $attributes['heading'] ?? '', 'classes' => 'heading'],
    'text'    => ['text' => $attributes['text'] ?? '', 'classes' => 'text'],
    'button'  => ['text' => $attributes['linkText'] ?? '', 'url' => $attributes['link']['url'] ?? ''],
    'image'   => ['image_id' => !empty($attributes['imageId']) ? (int) $attributes['imageId'] : 0, 'classes' => 'jm-text-with-image__image'],
];

$palette = $attributes['palette'] ?? 'jm-palette--default';
$order = $attributes['order'] ?? 'jm-text-with-image--image-left';
?>

<section class="jm-section jm-text-with-image <?php echo esc_attr("$palette $order");  ?>">
    <div class="jm-section__container">
        <div class="jm-section__column">

            <?php jm_component(name: 'paragraph', args: $args['label']); ?>

            <div class="jm-text-with-image__text">

                <?php


                jm_component(name: 'heading', args: $args['heading']);
                jm_component(name: 'paragraph', args: $args['text']);

                ?>

            </div>

            <?php jm_component(name: 'button-link', args: $args['button']); ?>

        </div>

        <div class="jm-section__column">
            <?php
            jm_component(name: 'image', args: $args['image']);
            ?>
        </div>
    </div>
</section>