<?php

add_action('init', function () {

    foreach (glob(get_theme_file_path('build/blocks/*')) as $block_dir) {

        if (file_exists($block_dir . '/block.json')) {
            register_block_type($block_dir);
        }
    }

});