<?php
$args = $args ?? [];
$text = $args['text'] ?? '';
$classes = $args['classes'] ?? '';

if ($text) : ?>
    <p class="<?php echo esc_attr($classes); ?>"><?php echo esc_html($text); ?></p>
<?php endif; ?>