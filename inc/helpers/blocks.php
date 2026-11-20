<?php
/**
 * Helper functions for the editor block allow-list.
 *
 * The allow-list decides which blocks each editor context can insert. Theme
 * blocks (`jmc/`) are allowed everywhere; core blocks are allowed only where
 * the theme's templates or menus need them; anything else is opt-in through
 * the `jmc_allowed_blocks` filter applied in `inc/blocks.php`.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Core blocks the theme's templates and template parts are built from.
 */
const JMC_TEMPLATE_CORE_BLOCKS = [
	'core/template-part',
	'core/group',
	'core/post-content',
	'core/post-title',
	'core/site-title',
];

/**
 * Core blocks a `wp_navigation` menu is built from; the theme's navigation
 * blocks read these from the menu.
 */
const JMC_NAVIGATION_CORE_BLOCKS = [
	'core/navigation-link',
	'core/navigation-submenu',
];

/**
 * List the blocks an editor context may insert.
 *
 * - `core/edit-site` (templates, template parts and navigation menus): theme
 *   blocks plus the template and navigation core blocks.
 * - `core/edit-post` and every other or unknown context: theme blocks only,
 *   the most restrictive list.
 *
 * Only candidate blocks are returned, in their original order.
 *
 * @param string            $context    Editor context name, e.g. `core/edit-post`.
 * @param array<int, mixed> $registered Candidate block names: the registered
 *                                      blocks, or an earlier filter's list.
 *
 * @return list<string> Allowed block names.
 */
function jmc_allowed_blocks_for_context( string $context, array $registered ): array {
	$core_blocks = 'core/edit-site' === $context
		? array_merge( JMC_TEMPLATE_CORE_BLOCKS, JMC_NAVIGATION_CORE_BLOCKS )
		: [];

	$allowed = array_filter(
		$registered,
		static fn ( mixed $name ): bool => is_string( $name )
			&& ( str_starts_with( $name, 'jmc/' ) || in_array( $name, $core_blocks, true ) )
	);

	return array_values( array_unique( $allowed ) );
}
