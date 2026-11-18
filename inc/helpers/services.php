<?php
/**
 * Helper functions for the services content model.
 *
 * Services are direct published child pages of one canonical Services page.
 * See docs/architecture/services-content-model.md.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Option storing the configured Services parent page ID.
 */
const JMC_SERVICES_PAGE_OPTION = 'jmc_services_page_id';

/**
 * Slug used to resolve the Services page when no page is configured.
 */
const JMC_SERVICES_PAGE_SLUG = 'services';

/**
 * Determine whether a page record can act as the Services parent.
 *
 * @param object|null $page Candidate page record (WP_Post or compatible).
 *
 * @return bool True when the record is a published page with a valid ID.
 */
function jmc_is_services_parent( ?object $page ): bool {
	if ( null === $page ) {
		return false;
	}

	$id     = $page->ID ?? null;
	$type   = $page->post_type ?? null;
	$status = $page->post_status ?? null;

	return is_int( $id )
		&& $id > 0
		&& 'page' === $type
		&& 'publish' === $status;
}

/**
 * Resolve the Services parent page from configuration and the canonical slug.
 *
 * A configured page always takes precedence. When a page is configured but no
 * longer valid (deleted, unpublished or not a page) the result is `invalid`
 * rather than silently falling back, so the misconfiguration stays visible.
 * The canonical slug is used only when nothing is configured.
 *
 * @param int         $configured_id   Configured page ID; 0 when unset.
 * @param object|null $configured_page Page record for the configured ID.
 * @param object|null $fallback_page   Page record found at the canonical slug.
 *
 * @return array{id:int,status:'configured'|'resolved'|'invalid'|'missing'} Resolved page ID (0 when unresolved) and how it was resolved.
 */
function jmc_resolve_services_page(
	int $configured_id,
	?object $configured_page,
	?object $fallback_page
): array {
	if ( $configured_id > 0 ) {
		return jmc_is_services_parent( $configured_page )
			&& $configured_page->ID === $configured_id
			? [
				'id'     => $configured_id,
				'status' => 'configured',
			]
			: [
				'id'     => 0,
				'status' => 'invalid',
			];
	}

	if ( jmc_is_services_parent( $fallback_page ) ) {
		return [
			'id'     => $fallback_page->ID,
			'status' => 'resolved',
		];
	}

	return [
		'id'     => 0,
		'status' => 'missing',
	];
}

/**
 * Resolve the Services parent page for the current site.
 *
 * @return array{id:int,status:'configured'|'resolved'|'invalid'|'missing'} Resolved page ID (0 when unresolved) and how it was resolved.
 */
function jmc_get_services_page(): array {
	$configured_id = absint( get_option( JMC_SERVICES_PAGE_OPTION, 0 ) );

	return jmc_resolve_services_page(
		configured_id: $configured_id,
		configured_page: $configured_id > 0 ? get_post( $configured_id ) : null,
		fallback_page: 0 === $configured_id
			? get_page_by_path( JMC_SERVICES_PAGE_SLUG, OBJECT, 'page' )
			: null
	);
}

/**
 * Page meta key storing a service's icon name.
 */
const JMC_SERVICE_ICON_META = 'jmc_service_icon';

/**
 * Build the query arguments for the services listing.
 *
 * Returns direct published, unprotected child pages of the Services page in
 * `menu_order ASC, post_title ASC` order with no item limit. An unresolved
 * parent (0) yields no arguments so top-level pages are never listed.
 *
 * @param int $parent_id Resolved Services page ID.
 *
 * @return array<string, mixed> WP_Query arguments, or an empty array when there is no parent.
 */
function jmc_services_query_args( int $parent_id ): array {
	if ( $parent_id <= 0 ) {
		return [];
	}

	return [
		'post_type'           => 'page',
		'post_status'         => 'publish',
		'post_parent'         => $parent_id,
		'has_password'        => false,
		'orderby'             => [
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		],
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	];
}

/**
 * Determine whether a value is a well-formed namespaced icon name.
 *
 * Mirrors the naming rules of the core Icons API ("collection/icon-name").
 *
 * @param mixed $value Candidate icon name.
 *
 * @return bool True when the value is a well-formed icon name.
 */
function jmc_is_icon_name( mixed $value ): bool {
	return is_string( $value )
		&& 1 === preg_match(
			'/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/',
			$value
		);
}

/**
 * Normalise a service page record into card data.
 *
 * Legacy records missing an excerpt or icon still produce a card with those
 * parts empty; records without a title or URL produce no card because they
 * cannot yield a meaningful link.
 *
 * @param object $page Service page record (WP_Post or compatible).
 * @param string $url  Canonical permalink for the page.
 * @param mixed  $icon Stored icon name.
 *
 * @return array{id:int,title:string,url:string,summary:string,icon:string}|null Card data, or null when the record is unusable.
 */
function jmc_service_card_data(
	object $page,
	string $url,
	mixed $icon
): ?array {
	$id      = $page->ID ?? 0;
	$title   = $page->post_title ?? '';
	$summary = $page->post_excerpt ?? '';

	$title   = is_string( $title ) ? trim( $title ) : '';
	$summary = is_string( $summary ) ? trim( $summary ) : '';
	$url     = trim( $url );

	if ( ! is_int( $id ) || $id <= 0 || '' === $title || '' === $url ) {
		return null;
	}

	return [
		'id'      => $id,
		'title'   => $title,
		'url'     => $url,
		'summary' => $summary,
		'icon'    => jmc_is_icon_name( $icon ) ? $icon : '',
	];
}

/**
 * Sanitise a service icon name against the core icon registry.
 *
 * @param mixed $value Submitted icon name.
 *
 * @return string The registered icon name, or an empty string.
 */
function jmc_sanitize_service_icon( mixed $value ): string {
	return jmc_is_icon_name( $value )
		&& WP_Icons_Registry::get_instance()->is_registered( $value )
		? $value
		: '';
}

/**
 * Retrieve card data for every service beneath a Services page.
 *
 * @param int $parent_id Resolved Services page ID.
 *
 * @return array<int, array{id:int,title:string,url:string,summary:string,icon:string}> Service cards in display order.
 */
function jmc_get_services( int $parent_id ): array {
	return jmc_service_cards( jmc_services_query_args( $parent_id ) );
}

/**
 * Query service pages and normalise them into card data.
 *
 * @param array<string, mixed> $args WP_Query arguments; empty yields no cards.
 *
 * @return array<int, array{id:int,title:string,url:string,summary:string,icon:string}> Service cards in query order.
 */
function jmc_service_cards( array $args ): array {
	if ( [] === $args ) {
		return [];
	}

	$cards = [];

	foreach ( get_posts( $args ) as $page ) {
		$card = jmc_service_card_data(
			page: $page,
			url: (string) get_permalink( $page ),
			icon: jmc_sanitize_service_icon(
				get_post_meta( $page->ID, JMC_SERVICE_ICON_META, true )
			)
		);

		if ( null !== $card ) {
			$cards[] = $card;
		}
	}

	return $cards;
}

/**
 * Normalise chosen page IDs: positive integers, de-duplicated, in the order given.
 *
 * Whole-number floats are accepted because the REST block renderer, which
 * draws the editor preview, casts `number` attributes to floats.
 *
 * @param mixed $value Stored page IDs.
 *
 * @return array<int, int> Page IDs.
 */
function jmc_service_page_ids( mixed $value ): array {
	if ( ! is_array( $value ) ) {
		return [];
	}

	$ids = [];

	foreach ( $value as $id ) {
		$id = is_int( $id )
			|| ( is_float( $id ) && floor( $id ) === $id )
			|| ( is_string( $id ) && ctype_digit( $id ) )
			? (int) $id
			: 0;

		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}

	return $ids;
}

/**
 * Build the query arguments for a list of chosen service pages.
 *
 * Returns the published, unprotected pages among the IDs, in the order chosen.
 * No IDs yields no arguments, so an empty choice never lists every page.
 *
 * @param array<int, int> $page_ids Chosen page IDs, from jmc_service_page_ids().
 *
 * @return array<string, mixed> WP_Query arguments, or an empty array when nothing is chosen.
 */
function jmc_chosen_services_query_args( array $page_ids ): array {
	if ( [] === $page_ids ) {
		return [];
	}

	return [
		'post_type'           => 'page',
		'post_status'         => 'publish',
		'post__in'            => $page_ids,
		'has_password'        => false,
		'orderby'             => 'post__in',
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	];
}

/**
 * Retrieve card data for chosen service pages.
 *
 * @param mixed $page_ids Stored page IDs.
 *
 * @return array<int, array{id:int,title:string,url:string,summary:string,icon:string}> Service cards in the chosen order.
 */
function jmc_get_chosen_services( mixed $page_ids ): array {
	return jmc_service_cards( jmc_chosen_services_query_args( jmc_service_page_ids( $page_ids ) ) );
}

/**
 * Resolve the page whose children a service list shows.
 *
 * An explicit parent page wins, and an invalid one resolves to nothing rather
 * than falling back, so the misconfiguration stays visible. Otherwise the page
 * the list is rendered on is the parent; without one, the Services page is.
 *
 * @param int                                                              $parent_id     Parent chosen on the block; 0 when unset.
 * @param object|null                                                      $parent_page   Page record for the chosen parent.
 * @param object|null                                                      $current_page  Page record the list is rendered on.
 * @param array{id:int,status:'configured'|'resolved'|'invalid'|'missing'} $services_page Resolved Services page.
 *
 * @return array{id:int,status:'parent'|'invalid-parent'|'current'|'configured'|'resolved'|'invalid'|'missing'} Resolved parent ID (0 when unresolved) and how it was resolved.
 */
function jmc_resolve_service_list_parent(
	int $parent_id,
	?object $parent_page,
	?object $current_page,
	array $services_page
): array {
	if ( $parent_id > 0 ) {
		return jmc_is_services_parent( $parent_page ) && $parent_page->ID === $parent_id
			? [
				'id'     => $parent_id,
				'status' => 'parent',
			]
			: [
				'id'     => 0,
				'status' => 'invalid-parent',
			];
	}

	$current_id = $current_page->ID ?? null;

	if ( is_int( $current_id ) && $current_id > 0 && 'page' === ( $current_page->post_type ?? null ) ) {
		return [
			'id'     => $current_id,
			'status' => 'current',
		];
	}

	return $services_page;
}

/**
 * Resolve the parent page for a service list on the current site.
 *
 * @param int $parent_id       Parent chosen on the block; 0 when unset.
 * @param int $current_page_id Page the list is rendered on; 0 when there is no page context.
 *
 * @return array{id:int,status:'parent'|'invalid-parent'|'current'|'configured'|'resolved'|'invalid'|'missing'} Resolved parent ID (0 when unresolved) and how it was resolved.
 */
function jmc_get_service_list_parent( int $parent_id, int $current_page_id ): array {
	return jmc_resolve_service_list_parent(
		parent_id: $parent_id,
		parent_page: $parent_id > 0 ? get_post( $parent_id ) : null,
		current_page: $parent_id <= 0 && $current_page_id > 0 ? get_post( $current_page_id ) : null,
		services_page: jmc_get_services_page()
	);
}

/**
 * Determine whether the current request is an editor block preview.
 *
 * Server-side block previews are REST requests made by users who can edit
 * content; they may show configuration guidance that visitors never see.
 *
 * @return bool True for editor preview requests.
 */
function jmc_is_block_preview_request(): bool {
	return wp_is_serving_rest_request() && current_user_can( 'edit_posts' );
}

/**
 * Template that lays out the Services landing page.
 */
const JMC_SERVICES_LANDING_TEMPLATE = 'services-landing';

/**
 * Template that lays out every direct child service page.
 */
const JMC_SERVICE_TEMPLATE = 'service';

/**
 * Determine a page's role in the services content model.
 *
 * Only the resolved Services page is the landing page, and only its direct
 * children are services. Deeper descendants, unrelated pages and other post
 * types have no role. Child status is not checked so drafts preview with the
 * template they will publish with.
 *
 * @param object|null $page             Page record (WP_Post or compatible).
 * @param int         $services_page_id Resolved Services page ID; 0 when unresolved.
 *
 * @return 'landing'|'service'|'none' The page's role.
 */
function jmc_service_page_role( ?object $page, int $services_page_id ): string {
	if ( null === $page || $services_page_id <= 0 ) {
		return 'none';
	}

	$id     = $page->ID ?? null;
	$type   = $page->post_type ?? null;
	$parent = $page->post_parent ?? null;

	if ( ! is_int( $id ) || $id <= 0 || 'page' !== $type ) {
		return 'none';
	}

	if ( $id === $services_page_id ) {
		return 'landing';
	}

	return (int) $parent === $services_page_id ? 'service' : 'none';
}

/**
 * Map each services role to its template slug.
 *
 * @return array{landing:string,service:string} Template slugs keyed by role.
 */
function jmc_service_templates(): array {
	return [
		'landing' => JMC_SERVICES_LANDING_TEMPLATE,
		'service' => JMC_SERVICE_TEMPLATE,
	];
}

/**
 * Apply the governed services template to a page template hierarchy.
 *
 * The services templates are removed wherever they appear, including when an
 * editor assigned one to a page it does not govern, and the template for the
 * page's role is put first so it also wins over any other assigned template.
 *
 * @param array<int, string> $templates Template candidates, most specific first.
 * @param string             $role      Page role from jmc_service_page_role().
 *
 * @return array<int, string> Template candidates.
 */
function jmc_service_template_hierarchy( array $templates, string $role ): array {
	$service_templates = jmc_service_templates();

	$templates = array_values(
		array_filter(
			$templates,
			static fn ( mixed $template ): bool => ! in_array(
				preg_replace( '/\.(php|html)$/', '', (string) $template ),
				$service_templates,
				true
			)
		)
	);

	if ( isset( $service_templates[ $role ] ) ) {
		array_unshift( $templates, $service_templates[ $role ] );
	}

	return $templates;
}

/**
 * Limit the selectable page templates to those that govern the page.
 *
 * @param array<string, string> $templates Template names keyed by slug.
 * @param string                $role      Page role from jmc_service_page_role().
 *
 * @return array<string, string> Selectable templates.
 */
function jmc_service_selectable_templates( array $templates, string $role ): array {
	$service_templates = jmc_service_templates();
	$own_template      = $service_templates[ $role ] ?? null;

	return array_filter(
		$templates,
		static fn ( string|int $slug ): bool => (string) $slug === $own_template
			|| ! in_array( (string) $slug, $service_templates, true ),
		ARRAY_FILTER_USE_KEY
	);
}
