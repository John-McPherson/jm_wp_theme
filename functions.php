<?php

// register all amc blocks
add_action('init', function () {

    foreach (glob(get_theme_file_path('build/blocks/*')) as $block_dir) {

        if (file_exists($block_dir . '/block.json')) {
            register_block_type($block_dir);
        }
    }

});

// only allow amc blocks
add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {

    $blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

    $allowed = [];

    foreach ($blocks as $block_name => $block) {
        if (str_starts_with($block_name, 'amc/')) {
            $allowed[] = $block_name;
        }
    }

    return $allowed;

}, 10, 2);
