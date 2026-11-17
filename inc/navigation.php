<?php
/**
 * Register the theme's navigation menus.
 *
 * Block themes have no menu locations: menus are `wp_navigation` posts. The
 * theme instead guarantees a menu exists for each registered slug, so blocks
 * and template parts can reference it by slug in every environment. Editors
 * manage the links in Appearance → Editor → Navigation.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * The navigation menus the theme provides, keyed by slug.
 *
 * Each title is used when the menu is first created and labels its `<nav>`
 * landmark until an editor renames it.
 *
 * @return array<string,string> Menu titles keyed by slug.
 */
function jmc_navigation_menus(): array {
	return [
		'main'   => __( 'Main', 'jmc-theme' ),
		'footer' => __( 'Footer', 'jmc-theme' ),
		'legal'  => __( 'Legal', 'jmc-theme' ),
	];
}

/**
 * Create any registered navigation menu that does not exist yet.
 *
 * Runs once per change to the registered slugs, recorded in the
 * `jmc_navigation_menus` option, so it costs one option read on later
 * requests. Existing menus, including drafts and trashed menus, are never
 * changed or duplicated, and a menu an editor deletes is not recreated until
 * the registered slugs change.
 *
 * @return void
 */
function jmc_ensure_navigation_menus(): void {
	$menus   = jmc_navigation_menus();
	$version = implode( ',', array_keys( $menus ) );

	if ( get_option( 'jmc_navigation_menus' ) === $version ) {
		return;
	}

	foreach ( $menus as $slug => $title ) {
		$existing = jmc_get_navigation_by_slug(
			slug: $slug,
			post_status: [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ]
		);

		if ( null !== $existing ) {
			continue;
		}

		wp_insert_post(
			[
				'post_type'    => 'wp_navigation',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_content' => '',
			]
		);
	}

	update_option( 'jmc_navigation_menus', $version, false );
}

add_action( 'after_switch_theme', 'jmc_ensure_navigation_menus' );
add_action( 'init', 'jmc_ensure_navigation_menus', 20 );
