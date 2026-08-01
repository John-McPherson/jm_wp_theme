<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     url?: string,
 *     classes?: string|string[]
 * } $args
 */


$args ??= [];

$text = jm_args_string(
    args: $args,
    key: 'text'
);

$url = jm_args_string(
    args: $args,
    key: 'url'
);

if ($text === '' || $url === '') {
    return;
}

$site_host = wp_parse_url(home_url(), PHP_URL_HOST);
$link_host = wp_parse_url($url, PHP_URL_HOST);

$is_external = is_string($link_host)
    && is_string($site_host)
    && strcasecmp($link_host, $site_host) !== 0;

$attributes = [
    'href' => $url,
    'classes' => [
        'jm-button',
        $args['classes'] ?? null,
    ],
];

if ($is_external) {
    $attributes['target'] = '_blank';
    $attributes['rel'] = ['noopener', 'noreferrer'];
}
?>

<a<?php jm_the_attributes($attributes); ?>>
    <?php echo esc_html($text); ?>

    <?php if ($is_external) : ?>
        <span class="screen-reader-text">
            <?php esc_html_e('(opens in a new tab)', 'jm-theme'); ?>
        </span>
    <?php endif; ?>
    </a>