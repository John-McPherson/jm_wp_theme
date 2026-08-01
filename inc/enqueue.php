<?php

declare(strict_types=1);

add_action('wp_enqueue_scripts', function (): void {
    $path = get_theme_file_path('build/css/style.css');

    wp_enqueue_style(
        'jm-theme',
        get_theme_file_uri('build/css/style.css'),
        [],
        file_exists($path) ? (string) filemtime($path) : null
    );
});
