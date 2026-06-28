<?php

$heading = $attributes['heading'] ?? '';
$text = $attributes['text'] ?? '';
$background_image = $attributes['imageUrl'] ?? '';


if (empty($heading) || empty($text)) {
    return;
}

?>

<section class="jm-section jm-hero">
    <div class="hero-text">
        <h1><?php echo esc_html($heading) ?></h1>
        <p>
            <?php echo esc_html($text) ?>
        </p>
    </div>
    <div class="hero-img" style="--background-image: url(<?php echo esc_attr($background_image) ?>);">

    </div>
</section>