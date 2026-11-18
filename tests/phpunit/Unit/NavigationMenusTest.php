<?php
/**
 * Registered navigation menu tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/navigation.php';

/**
 * Verify the menu registry, the slug lookup and menu creation.
 */
final class NavigationMenusTest extends TestCase {

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_posts'],
			$GLOBALS['jmc_test_options'],
			$GLOBALS['jmc_test_inserted_posts'],
			$GLOBALS['jmc_test_get_posts_queries']
		);

		parent::tearDown();
	}

	/**
	 * The theme registers the main, footer and legal menus, in that order.
	 */
	public function test_registers_main_footer_and_legal_menus(): void {
		self::assertSame(
			[
				'main'   => 'Main',
				'footer' => 'Footer',
				'legal'  => 'Legal',
			],
			jmc_navigation_menus()
		);
	}

	/**
	 * Menu creation runs on theme activation and once on every request.
	 */
	public function test_hooks_menu_creation(): void {
		self::assertContains(
			[ 'jmc_ensure_navigation_menus', 10 ],
			$GLOBALS['jmc_test_actions']['after_switch_theme'] ?? []
		);
		self::assertContains(
			[ 'jmc_ensure_navigation_menus', 20 ],
			$GLOBALS['jmc_test_actions']['init'] ?? []
		);
	}

	/**
	 * The lookup finds a published menu by slug.
	 */
	public function test_finds_published_menu_by_slug(): void {
		$GLOBALS['jmc_test_posts'] = [
			1 => $this->menu( slug: 'main' ),
			2 => $this->menu( slug: 'legal' ),
		];

		self::assertSame( $GLOBALS['jmc_test_posts'][2], jmc_get_navigation_by_slug( slug: 'legal' ) );
		self::assertSame( 'wp_navigation', $GLOBALS['jmc_test_get_posts_queries'][0]['post_type'] );
		self::assertSame( 1, $GLOBALS['jmc_test_get_posts_queries'][0]['posts_per_page'] );
	}

	/**
	 * Unpublished menus are only found when their status is requested.
	 */
	public function test_ignores_unpublished_menus_by_default(): void {
		$GLOBALS['jmc_test_posts'] = [ 1 => $this->menu( slug: 'legal', status: 'draft' ) ];

		self::assertNull( jmc_get_navigation_by_slug( slug: 'legal' ) );
		self::assertNotNull( jmc_get_navigation_by_slug( slug: 'legal', post_status: [ 'publish', 'draft' ] ) );
	}

	/**
	 * Other post types and unknown slugs are not matched.
	 */
	public function test_returns_null_for_unknown_menu(): void {
		$GLOBALS['jmc_test_posts'] = [
			1 => (object) [
				'post_type'   => 'page',
				'post_name'   => 'legal',
				'post_status' => 'publish',
			],
		];

		self::assertNull( jmc_get_navigation_by_slug( slug: 'legal' ) );
		self::assertNull( jmc_get_navigation_by_slug( slug: 'missing' ) );
	}

	/**
	 * A slug that sanitises to nothing returns null without querying.
	 */
	public function test_empty_slug_skips_query(): void {
		self::assertNull( jmc_get_navigation_by_slug( slug: ' <> ' ) );
		self::assertArrayNotHasKey( 'jmc_test_get_posts_queries', $GLOBALS );
	}

	/**
	 * Missing menus are created as published `wp_navigation` posts with the
	 * registered slug and title, and the run is recorded.
	 */
	public function test_creates_missing_menus(): void {
		jmc_ensure_navigation_menus();

		self::assertSame(
			[
				[ 'wp_navigation', 'main', 'Main', 'publish' ],
				[ 'wp_navigation', 'footer', 'Footer', 'publish' ],
				[ 'wp_navigation', 'legal', 'Legal', 'publish' ],
			],
			array_map(
				static fn ( array $post ): array => [ $post['post_type'], $post['post_name'], $post['post_title'], $post['post_status'] ],
				$GLOBALS['jmc_test_inserted_posts']
			)
		);
		self::assertSame( 'main,footer,legal', $GLOBALS['jmc_test_options']['jmc_navigation_menus'] );
	}

	/**
	 * Existing menus are never duplicated, whatever their status.
	 *
	 * @dataProvider existing_status_provider
	 *
	 * @param string $status Status of the existing legal menu.
	 */
	public function test_skips_existing_menus( string $status ): void {
		$GLOBALS['jmc_test_posts'] = [ 1 => $this->menu( slug: 'legal', status: $status ) ];

		jmc_ensure_navigation_menus();

		self::assertSame(
			[ 'main', 'footer' ],
			array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' )
		);
	}

	/**
	 * Statuses an existing menu can have.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function existing_status_provider(): array {
		return [
			'published' => [ 'publish' ],
			'draft'     => [ 'draft' ],
			'private'   => [ 'private' ],
			'trashed'   => [ 'trash' ],
		];
	}

	/**
	 * Nothing is queried or created once the registered slugs have been
	 * handled, so a menu an editor deletes is not recreated.
	 */
	public function test_does_nothing_when_already_run(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_navigation_menus' => 'main,footer,legal' ];

		jmc_ensure_navigation_menus();

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_get_posts_queries', $GLOBALS );
	}

	/**
	 * A change to the registered slugs runs creation again.
	 */
	public function test_runs_again_when_registered_slugs_change(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_navigation_menus' => 'main,footer' ];
		$GLOBALS['jmc_test_posts']   = [
			1 => $this->menu( slug: 'main' ),
			2 => $this->menu( slug: 'footer' ),
		];

		jmc_ensure_navigation_menus();

		self::assertSame( [ 'legal' ], array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame( 'main,footer,legal', $GLOBALS['jmc_test_options']['jmc_navigation_menus'] );
	}

	/**
	 * Build a `wp_navigation` post fixture.
	 *
	 * @param string $slug   Menu slug.
	 * @param string $status Post status.
	 *
	 * @return object Post fixture.
	 */
	private function menu( string $slug, string $status = 'publish' ): object {
		return (object) [
			'post_type'    => 'wp_navigation',
			'post_name'    => $slug,
			'post_status'  => $status,
			'post_title'   => ucfirst( $slug ),
			'post_content' => '[]',
		];
	}
}
