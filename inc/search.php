<?php
/**
 * Turn off the front-end site search.
 *
 * The site is small enough that its navigation, the Services page and the
 * 404 page's recovery links cover what search would. A search request
 * (`?s=`) is answered as not found, with a 404 status and the theme's 404
 * template, so no results page is ever shown or indexed. Searches in the
 * admin and the REST API, such as the editor's link search, are unaffected.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Answer a front-end search request as not found.
 */
function jmc_disable_search(): void {
	global $wp_query;

	if ( ! is_search() || ! is_object( $wp_query ) ) {
		return;
	}

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

add_action( 'template_redirect', 'jmc_disable_search' );
