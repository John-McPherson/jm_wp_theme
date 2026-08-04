<?php
/**
 * Theme bootstrap file.
 *
 * Loads theme initialization and helper files.
 *
 * @package JMC_Theme
 */

// initialize theme.
require_once get_theme_file_path( '/inc/enqueue.php' );
require_once get_theme_file_path( '/inc/setup.php' );
require_once get_theme_file_path( '/inc/blocks.php' );

// helpers.
require_once get_theme_file_path( '/inc/helpers/components.php' );
require_once get_theme_file_path( '/inc/helpers/component_args.php' );
require_once get_theme_file_path( '/inc/helpers/html/attributes.php' );
