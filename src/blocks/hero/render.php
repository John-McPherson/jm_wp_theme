<?php

$heading = 'site heading to go here2';
$background_image = "https://placehold.net/800x600.png";
$text = "Lorem ipsum dolor sit amet consectetur adipisicing elit. Sunt tempore, sint nulla eaque similique a ex assumenda minima dicta illo totam ullam vitae iure cupiditate id officiis accusantium rerum ipsum.";

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