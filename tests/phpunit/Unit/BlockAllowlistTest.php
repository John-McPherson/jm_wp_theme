<?php
/**
 * Block allow-list tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/blocks.php';

/**
 * Verify each editor context allows only the blocks the theme supports.
 */
final class BlockAllowlistTest extends TestCase {

	/**
	 * Registered blocks: theme, template core, navigation core, other core and plugin blocks.
	 */
	private const REGISTERED = [
		'core/paragraph',
		'jmc/hero',
		'core/template-part',
		'jmc/heading',
		'core/group',
		'core/post-content',
		'core/post-title',
		'core/site-title',
		'core/navigation-link',
		'core/navigation-submenu',
		'core/navigation',
		'core/block',
		'core/pattern',
		'core/breadcrumbs',
		'yoast/faq-block',
		'jmc/footer',
	];

	/**
	 * The post editor allows theme blocks only, in their original order.
	 */
	public function test_post_editor_allows_only_theme_blocks(): void {
		self::assertSame(
			[ 'jmc/hero', 'jmc/heading', 'jmc/footer' ],
			jmc_allowed_blocks_for_context( 'core/edit-post', self::REGISTERED )
		);
	}

	/**
	 * The Site Editor adds the template and navigation core blocks to the theme blocks.
	 */
	public function test_site_editor_allows_theme_template_and_navigation_blocks(): void {
		self::assertSame(
			[
				'jmc/hero',
				'core/template-part',
				'jmc/heading',
				'core/group',
				'core/post-content',
				'core/post-title',
				'core/site-title',
				'core/navigation-link',
				'core/navigation-submenu',
				'jmc/footer',
			],
			jmc_allowed_blocks_for_context( 'core/edit-site', self::REGISTERED )
		);
	}

	/**
	 * Unknown and unnamed contexts fall back to the post editor's list.
	 *
	 * @dataProvider fallback_context_provider
	 *
	 * @param string $context Editor context name.
	 */
	public function test_other_contexts_fall_back_to_the_post_editor_list( string $context ): void {
		self::assertSame(
			jmc_allowed_blocks_for_context( 'core/edit-post', self::REGISTERED ),
			jmc_allowed_blocks_for_context( $context, self::REGISTERED )
		);
	}

	/**
	 * Contexts that are not the Site Editor.
	 *
	 * @return array<string, array{string}>
	 */
	public function fallback_context_provider(): array {
		return [
			'widgets editor'     => [ 'core/edit-widgets' ],
			'customizer widgets' => [ 'core/customize-widgets' ],
			'generic context'    => [ 'core' ],
			'plugin context'     => [ 'plugin/editor' ],
			'empty context'      => [ '' ],
			'site editor casing' => [ 'Core/Edit-Site' ],
		];
	}

	/**
	 * Core blocks outside the lists, synced patterns and plugin blocks are never allowed.
	 */
	public function test_excludes_other_core_and_plugin_blocks(): void {
		foreach ( [ 'core/edit-post', 'core/edit-site' ] as $context ) {
			$allowed = jmc_allowed_blocks_for_context( $context, self::REGISTERED );

			foreach ( [ 'core/paragraph', 'core/navigation', 'core/block', 'core/pattern', 'core/breadcrumbs', 'yoast/faq-block' ] as $name ) {
				self::assertNotContains( $name, $allowed, "{$name} in {$context}" );
			}
		}
	}

	/**
	 * Core list blocks that are not candidates are not added.
	 */
	public function test_returns_only_candidate_blocks(): void {
		self::assertSame(
			[ 'jmc/hero', 'core/group' ],
			jmc_allowed_blocks_for_context( 'core/edit-site', [ 'jmc/hero', 'core/group' ] )
		);
	}

	/**
	 * Lookalike names, duplicates and non-string candidates are dropped.
	 */
	public function test_drops_lookalike_duplicate_and_malformed_names(): void {
		self::assertSame(
			[ 'jmc/hero', 'core/group' ],
			jmc_allowed_blocks_for_context(
				'core/edit-site',
				[ 'jmc/hero', 'jmc/hero', 'jmcx/hero', 'JMC/hero', 'xjmc/hero', 'core/group-x', 'core/group', null, 42, [ 'jmc/heading' ] ]
			)
		);
	}

	/**
	 * No candidates allow no blocks.
	 */
	public function test_returns_empty_list_without_candidates(): void {
		self::assertSame( [], jmc_allowed_blocks_for_context( 'core/edit-site', [] ) );
	}

	/**
	 * Every block in the templates and template parts can be inserted in the Site Editor.
	 */
	public function test_site_editor_allows_every_template_block(): void {
		$names = self::block_names_in( [ 'templates/*.html', 'parts/*.html' ] );

		self::assertNotEmpty( $names );
		self::assertSame( [], array_values( array_diff( $names, jmc_allowed_blocks_for_context( 'core/edit-site', $names ) ) ) );
	}

	/**
	 * Every block in the theme's patterns can be inserted in the post editor.
	 */
	public function test_post_editor_allows_every_pattern_block(): void {
		$names = self::block_names_in( [ 'patterns/*.php' ] );

		self::assertNotEmpty( $names );
		self::assertSame( [], array_values( array_diff( $names, jmc_allowed_blocks_for_context( 'core/edit-post', $names ) ) ) );
	}

	/**
	 * Collect the block names used in theme files.
	 *
	 * @param string[] $globs File globs relative to the theme root.
	 *
	 * @return list<string> Unique block names, with core blocks namespaced.
	 */
	private static function block_names_in( array $globs ): array {
		$names = [];

		foreach ( $globs as $glob ) {
			$files = glob( dirname( __DIR__, 3 ) . '/' . $glob );

			foreach ( false === $files ? [] : $files as $file ) {
				$contents = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a local theme file.

				preg_match_all( '/<!--\s+wp:([a-z0-9-]+(?:\/[a-z0-9-]+)?)/', $contents, $matches );

				foreach ( $matches[1] as $name ) {
					$names[] = str_contains( $name, '/' ) ? $name : "core/{$name}";
				}
			}
		}

		return array_values( array_unique( $names ) );
	}
}
