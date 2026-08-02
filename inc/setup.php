<?php

declare(strict_types=1);

add_action('after_setup_theme', function (): void {
    add_theme_support('editor-styles');
    add_editor_style('build/css/editor-style.css');
    add_theme_support('disable-layout-styles');
});
