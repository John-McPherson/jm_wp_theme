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
require_once get_theme_file_path( '/inc/patterns.php' );
require_once get_theme_file_path( '/inc/templates.php' );
require_once get_theme_file_path( '/inc/search.php' );
require_once get_theme_file_path( '/inc/icons.php' );
require_once get_theme_file_path( '/inc/navigation.php' );
require_once get_theme_file_path( '/inc/legal-pages.php' );
require_once get_theme_file_path( '/inc/legal-pages-settings.php' );
require_once get_theme_file_path( '/inc/services.php' );
require_once get_theme_file_path( '/inc/breadcrumbs.php' );
require_once get_theme_file_path( '/inc/contact.php' );
require_once get_theme_file_path( '/inc/favicons.php' );

// helpers.
require_once get_theme_file_path( '/inc/helpers/components.php' );
require_once get_theme_file_path( '/inc/helpers/component-args.php' );
require_once get_theme_file_path( '/inc/helpers/navigation.php' );
require_once get_theme_file_path( '/inc/helpers/link.php' );
require_once get_theme_file_path( '/inc/helpers/text.php' );
require_once get_theme_file_path( '/inc/helpers/services.php' );
require_once get_theme_file_path( '/inc/helpers/contact.php' );
require_once get_theme_file_path( '/inc/helpers/button.php' );
require_once get_theme_file_path( '/inc/helpers/enquiry.php' );
require_once get_theme_file_path( '/inc/helpers/svg.php' );
require_once get_theme_file_path( '/inc/helpers/blocks.php' );

require_once get_theme_file_path( '/inc/helpers/html/attributes.php' );

// features that use the helpers when loaded.
require_once get_theme_file_path( '/inc/enquiries.php' );
require_once get_theme_file_path( '/inc/enquiry-submission.php' );
