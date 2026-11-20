<?php
/**
 * Create the site's legal pages and link them from the Legal menu.
 *
 * Every environment starts with the same legal pages, as drafts with
 * placeholder content, linked from the theme-registered `legal` menu (see
 * inc/navigation.php) that `jmc/footer-legal` renders. Editors write and
 * publish the pages; the footer only links published ones. Administrators
 * choose which pages are linked in Settings → Legal pages (see
 * inc/legal-pages-settings.php).
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * The legal pages the theme provides, keyed by slug, in menu order.
 *
 * Each title is used when the page is first created and as its menu link's
 * label. The privacy policy is the page set in Settings → Privacy when there
 * is one.
 *
 * @return array<string,string> Page titles keyed by slug.
 */
function jmc_legal_pages(): array {
	return [
		'privacy-policy'          => __( 'Privacy Policy', 'jmc-theme' ),
		'cookie-policy'           => __( 'Cookie Policy', 'jmc-theme' ),
		'terms-and-conditions'    => __( 'Terms and Conditions', 'jmc-theme' ),
		'accessibility-statement' => __( 'Accessibility Statement', 'jmc-theme' ),
	];
}

/**
 * Option recording the legal page slugs the automatic setup has handled.
 */
const JMC_LEGAL_PAGES_HANDLED_OPTION = 'jmc_legal_pages';

/**
 * Option listing the legal page slugs an administrator has unticked in
 * Settings → Legal pages. Every other registered page is ticked.
 */
const JMC_LEGAL_PAGES_DISABLED_OPTION = 'jmc_legal_pages_disabled';

/**
 * The registered legal page slugs an administrator has unticked.
 *
 * @return string[] Unticked slugs, in registered order.
 */
function jmc_disabled_legal_pages(): array {
	$disabled = get_option( JMC_LEGAL_PAGES_DISABLED_OPTION, [] );

	return jmc_sanitize_legal_page_slugs( slugs: is_array( $disabled ) ? $disabled : [] );
}

/**
 * Keep only registered legal page slugs, without duplicates, in registered
 * order.
 *
 * @param mixed[] $slugs Submitted or stored slugs.
 *
 * @return string[] Registered slugs.
 */
function jmc_sanitize_legal_page_slugs( array $slugs ): array {
	return array_values(
		array_intersect( array_keys( jmc_legal_pages() ), array_filter( $slugs, 'is_string' ) )
	);
}

/**
 * Create any new legal page that does not exist yet and link it from the
 * Legal menu.
 *
 * Runs once per change to the registered slugs. The slugs already handled
 * are recorded in the `jmc_legal_pages` option, and only slugs missing from
 * it are handled, so later requests cost one option read and a page or menu
 * link an editor deletes is never restored. Pages unticked in Settings →
 * Legal pages are skipped. Existing pages, in any status, are reused rather
 * than duplicated; trashed pages are left alone.
 *
 * @return void
 */
function jmc_ensure_legal_pages(): void {
	$slugs   = array_keys( jmc_legal_pages() );
	$version = implode( ',', $slugs );
	$handled = (string) get_option( JMC_LEGAL_PAGES_HANDLED_OPTION, '' );

	if ( $handled === $version ) {
		return;
	}

	jmc_link_legal_pages(
		slugs: array_diff( $slugs, explode( ',', $handled ), jmc_disabled_legal_pages() )
	);

	update_option( JMC_LEGAL_PAGES_HANDLED_OPTION, $version, false );
}

/**
 * Save the legal pages an administrator has ticked, and apply the choice.
 *
 * A ticked page is created as a draft when no page with its slug exists,
 * then linked from the Legal menu unless it already is. An unticked page
 * only loses its Legal menu links; the page itself is never changed. Saving
 * the same choice again changes nothing. Every registered slug is recorded
 * as handled, so the automatic setup does not act on it afterwards.
 *
 * @param mixed[] $ticked Ticked slugs; unregistered values are ignored.
 *
 * @return void
 */
function jmc_save_legal_pages( array $ticked ): void {
	$slugs    = array_keys( jmc_legal_pages() );
	$ticked   = jmc_sanitize_legal_page_slugs( slugs: $ticked );
	$unticked = array_values( array_diff( $slugs, $ticked ) );

	update_option( JMC_LEGAL_PAGES_DISABLED_OPTION, $unticked, false );

	jmc_unlink_legal_pages( slugs: $unticked );
	jmc_link_legal_pages( slugs: $ticked );

	update_option( JMC_LEGAL_PAGES_HANDLED_OPTION, implode( ',', $slugs ), false );
}

/**
 * Find or create legal pages and link them from the Legal menu.
 *
 * Trashed pages are found, so never duplicated, but not linked.
 *
 * @param string[] $slugs Registered slugs, in menu order.
 *
 * @return void
 */
function jmc_link_legal_pages( array $slugs ): void {
	$titles = jmc_legal_pages();
	$pages  = [];

	foreach ( $slugs as $slug ) {
		if ( ! isset( $titles[ $slug ] ) ) {
			continue;
		}

		$page = jmc_find_legal_page( slug: $slug ) ?? jmc_create_legal_page( slug: $slug, title: $titles[ $slug ] );

		if ( null !== $page && 'trash' !== $page->post_status ) {
			$pages[] = $page;
		}
	}

	jmc_add_legal_menu_links( pages: $pages );
}

/**
 * Remove the Legal menu links to legal pages, leaving the pages unchanged.
 *
 * Missing pages are not created.
 *
 * @param string[] $slugs Registered slugs.
 *
 * @return void
 */
function jmc_unlink_legal_pages( array $slugs ): void {
	$page_ids = [];

	foreach ( $slugs as $slug ) {
		$page = jmc_find_legal_page( slug: $slug );

		if ( null !== $page ) {
			$page_ids[] = (int) $page->ID;
		}
	}

	$menu = jmc_get_navigation_by_slug( slug: 'legal' );

	if ( null === $menu || [] === $page_ids ) {
		return;
	}

	$menu_blocks = parse_blocks( $menu->post_content );
	$kept_blocks = jmc_remove_navigation_post_links( menu_blocks: $menu_blocks, post_ids: $page_ids );

	if ( $kept_blocks === $menu_blocks ) {
		return;
	}

	wp_update_post(
		wp_slash(
			[
				'ID'           => $menu->ID,
				'post_content' => serialize_blocks( $kept_blocks ),
			]
		)
	);
}

/**
 * Find an existing legal page, in any status.
 *
 * The privacy policy is the page set in Settings → Privacy when that page
 * still exists. Otherwise a page is matched by slug, including a trashed
 * page, whose slug WordPress suffixes with `__trashed`.
 *
 * @param string $slug Legal page slug.
 *
 * @return WP_Post|null The page, or null when there is none.
 */
function jmc_find_legal_page( string $slug ): ?object {
	if ( 'privacy-policy' === $slug ) {
		$privacy_page_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
		$privacy_page    = 0 < $privacy_page_id ? get_post( $privacy_page_id ) : null;

		if ( null !== $privacy_page && 'page' === $privacy_page->post_type ) {
			return $privacy_page;
		}
	}

	$pages = get_posts(
		[
			'post_type'      => 'page',
			'post_name__in'  => [ $slug, $slug . '__trashed' ],
			'post_status'    => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
			'orderby'        => 'post_name__in',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		]
	);

	return $pages[0] ?? null;
}

/**
 * Create a legal page as a draft with placeholder content.
 *
 * A new privacy policy is also set as the privacy policy page.
 *
 * @param string $slug  Page slug.
 * @param string $title Page title.
 *
 * @return WP_Post|null The new page, or null when it could not be created.
 */
function jmc_create_legal_page( string $slug, string $title ): ?object {
	$placeholder = sprintf(
		/* translators: %s: Legal page title, such as "Cookie Policy". */
		__( 'Placeholder: replace this text with the site’s %s. Legal text must be written or reviewed by someone qualified before this page is published.', 'jmc-theme' ),
		$title
	);

	$page_id = wp_insert_post(
		wp_slash(
			[
				'post_type'    => 'page',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_status'  => 'draft',
				'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html( $placeholder ) . "</p>\n<!-- /wp:paragraph -->",
			]
		)
	);

	if ( ! is_int( $page_id ) || 0 >= $page_id ) {
		return null;
	}

	if ( 'privacy-policy' === $slug ) {
		update_option( 'wp_page_for_privacy_policy', $page_id );
	}

	return get_post( $page_id );
}

/**
 * Append page links to the published Legal menu, in the order given.
 *
 * Pages the menu already links to are skipped, and the existing links are
 * kept as they are. Nothing changes when the menu is missing or unpublished.
 *
 * @param WP_Post[] $pages Pages to link.
 *
 * @return void
 */
function jmc_add_legal_menu_links( array $pages ): void {
	$menu = jmc_get_navigation_by_slug( slug: 'legal' );

	if ( null === $menu || [] === $pages ) {
		return;
	}

	$menu_blocks = parse_blocks( $menu->post_content );
	$linked_ids  = jmc_get_navigation_post_ids( menu_blocks: $menu_blocks );
	$new_links   = [];

	foreach ( $pages as $page ) {
		if ( in_array( (int) $page->ID, $linked_ids, true ) ) {
			continue;
		}

		$new_links[] = [
			'blockName'    => 'core/navigation-link',
			'attrs'        => [
				'label' => (string) $page->post_title,
				'type'  => 'page',
				'id'    => (int) $page->ID,
				'url'   => (string) get_permalink( $page ),
				'kind'  => 'post-type',
			],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		];
	}

	if ( [] === $new_links ) {
		return;
	}

	wp_update_post(
		wp_slash(
			[
				'ID'           => $menu->ID,
				'post_content' => serialize_blocks( array_merge( $menu_blocks, $new_links ) ),
			]
		)
	);
}

add_action( 'after_switch_theme', 'jmc_ensure_legal_pages', 20 );
add_action( 'init', 'jmc_ensure_legal_pages', 21 );
