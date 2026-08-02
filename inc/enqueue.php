<?php

declare(strict_types=1);

add_action('enqueue_block_assets', function (): void {
    $relative_path = 'build/css/style.css';
    $absolute_path = get_theme_file_path($relative_path);

    if (! file_exists($absolute_path)) {
        return;
    }

    wp_enqueue_style(
        'jm-theme',
        get_theme_file_uri($relative_path),
        [],
        (string) filemtime($absolute_path)
    );
}, 5);
