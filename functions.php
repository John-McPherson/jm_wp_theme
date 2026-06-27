<?php

wp_enqueue_style(
    'my-theme',
    get_theme_file_uri('/build/style.css'),
    [],
    filemtime(get_theme_file_path('/build/style.css'))
);

// register all theme blocks
add_action('init', function () {

    foreach (glob(get_stylesheet_directory() . '/build/blocks/*') as $block_dir) {

        if (file_exists($block_dir . '/block.json')) {
            register_block_type($block_dir);
        }
    }
});
// only allow theme blocks
add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {

    $blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

    $allowed = [];

    foreach ($blocks as $block_name => $block) {
        if (str_starts_with($block_name, 'jm/')) {
            $allowed[] = $block_name;
        }
    }

    return $allowed;
}, 10, 2);


add_filter('block_categories_all', function ($categories) {
    return array_merge($categories, [
        [
            'slug'  => 'jm-blocks',
            'title' => __('Custom Blocks', 'jm'),
            'icon'  => 'customizer',
        ],
    ]);
});
