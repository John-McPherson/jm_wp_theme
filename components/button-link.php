<?php
$args = $args ?? [];

$url     = $args['url'] ?? '';
$text    = $args['text'] ?? '';
$classes = $args['classes'] ?? '';

if (! $text || ! $url) {
    return;
}

$site_host = wp_parse_url(home_url(), PHP_URL_HOST);
$link_host = wp_parse_url($url, PHP_URL_HOST);

$is_external = $link_host && $site_host && $link_host !== $site_host;

$attr_target = $is_external ? ' target="_blank"' : '';
$attr_rel    = $is_external ? ' rel="noopener noreferrer"' : '';
?>

<a
    href="<?php echo esc_url($url); ?>"
    class="<?php echo esc_attr(trim($classes . ' jm-button')); ?>"
    <?php echo $attr_target; ?>
    <?php echo $attr_rel; ?>>
    <?php echo esc_html($text); ?>

    <?php if ($is_external) : ?>
        <span class="screen-reader-text">
            <?php esc_html_e(' (opens in a new tab)', 'jm-theme'); ?>
        </span>
    <?php endif; ?>
</a>