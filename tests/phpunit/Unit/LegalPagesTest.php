<?php
/**
 * Legal pages setup tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/legal-pages.php';
require_once dirname( __DIR__, 3 ) . '/inc/legal-pages.php';

/**
 * Verify the legal page registry, page creation and Legal menu links.
 */
final class LegalPagesTest extends TestCase {

	/**
	 * Every registered legal page slug, in menu order.
	 */
	private const ALL_SLUGS = 'privacy-policy,cookie-policy,terms-and-conditions,accessibility-statement';

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_posts'],
			$GLOBALS['jmc_test_options'],
			$GLOBALS['jmc_test_inserted_posts'],
			$GLOBALS['jmc_test_updated_posts'],
			$GLOBALS['jmc_test_get_posts_queries']
		);

		parent::tearDown();
	}

	/**
	 * The theme registers the four legal pages, in menu order.
	 */
	public function test_registers_legal_pages_in_order(): void {
		self::assertSame(
			[
				'privacy-policy'          => 'Privacy Policy',
				'cookie-policy'           => 'Cookie Policy',
				'terms-and-conditions'    => 'Terms and Conditions',
				'accessibility-statement' => 'Accessibility Statement',
			],
			jmc_legal_pages()
		);
	}

	/**
	 * Setup runs on theme activation and on every request, after the menus
	 * are created.
	 */
	public function test_hooks_setup_after_menu_creation(): void {
		self::assertContains(
			[ 'jmc_ensure_legal_pages', 20 ],
			$GLOBALS['jmc_test_actions']['after_switch_theme'] ?? []
		);
		self::assertContains(
			[ 'jmc_ensure_legal_pages', 21 ],
			$GLOBALS['jmc_test_actions']['init'] ?? []
		);
	}

	/**
	 * Missing pages are created as drafts with their slug, title and marked
	 * placeholder content, and the run is recorded.
	 */
	public function test_creates_missing_pages_as_drafts(): void {
		jmc_ensure_legal_pages();

		$inserted = $GLOBALS['jmc_test_inserted_posts'];

		self::assertSame(
			[
				[ 'page', 'privacy-policy', 'Privacy Policy', 'draft' ],
				[ 'page', 'cookie-policy', 'Cookie Policy', 'draft' ],
				[ 'page', 'terms-and-conditions', 'Terms and Conditions', 'draft' ],
				[ 'page', 'accessibility-statement', 'Accessibility Statement', 'draft' ],
			],
			array_map(
				static fn ( array $post ): array => [ $post['post_type'], $post['post_name'], $post['post_title'], $post['post_status'] ],
				$inserted
			)
		);

		foreach ( $inserted as $post ) {
			self::assertStringStartsWith( "<!-- wp:paragraph -->\n<p>Placeholder: replace this text with the site’s {$post['post_title']}.", $post['post_content'] );
			self::assertStringContainsString( 'reviewed by someone qualified before this page is published', $post['post_content'] );
		}

		self::assertSame( self::ALL_SLUGS, $GLOBALS['jmc_test_options']['jmc_legal_pages'] );
	}

	/**
	 * A new privacy policy is set as the privacy policy page.
	 */
	public function test_sets_created_privacy_policy_as_privacy_page(): void {
		jmc_ensure_legal_pages();

		self::assertSame( 1001, $GLOBALS['jmc_test_options']['wp_page_for_privacy_policy'] );
	}

	/**
	 * The page set in Settings → Privacy is used rather than a new page,
	 * whatever its slug or status, and the setting is left unchanged.
	 *
	 * @dataProvider page_status_provider
	 *
	 * @param string $status Status of the privacy policy page.
	 */
	public function test_reuses_privacy_policy_setting( string $status ): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => '5' ];
		$GLOBALS['jmc_test_posts']   = [ 5 => $this->page( id: 5, slug: 'our-privacy', status: $status ) ];

		jmc_ensure_legal_pages();

		self::assertNotContains( 'privacy-policy', array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame( '5', $GLOBALS['jmc_test_options']['wp_page_for_privacy_policy'] );
	}

	/**
	 * A privacy policy setting that does not point at a page is ignored, and
	 * a new privacy policy replaces it.
	 *
	 * @dataProvider unusable_privacy_setting_provider
	 *
	 * @param mixed             $setting The privacy policy page setting.
	 * @param array<int,object> $posts   Post fixtures.
	 */
	public function test_creates_privacy_policy_when_setting_is_unusable( mixed $setting, array $posts ): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => $setting ];
		$GLOBALS['jmc_test_posts']   = $posts;

		jmc_ensure_legal_pages();

		self::assertSame( 'privacy-policy', $GLOBALS['jmc_test_inserted_posts'][0]['post_name'] );
		self::assertSame( 1001, $GLOBALS['jmc_test_options']['wp_page_for_privacy_policy'] );
	}

	/**
	 * Privacy policy settings that do not resolve to a page.
	 *
	 * @return array<string,array{0:mixed,1:array<int,object>}>
	 */
	public function unusable_privacy_setting_provider(): array {
		return [
			'unset'        => [ 0, [] ],
			'deleted page' => [ '7', [] ],
			'not a page'   => [
				'7',
				[
					7 => (object) [
						'ID'          => 7,
						'post_type'   => 'post',
						'post_name'   => 'privacy-policy',
						'post_status' => 'publish',
					],
				],
			],
			'malformed'    => [ 'privacy', [] ],
		];
	}

	/**
	 * An existing page with a legal slug is never duplicated, whatever its
	 * status, including a trashed page, whose slug WordPress suffixes.
	 *
	 * @dataProvider page_status_provider
	 *
	 * @param string $status Status of the existing cookie policy page.
	 */
	public function test_skips_existing_pages( string $status ): void {
		$slug = 'trash' === $status ? 'cookie-policy__trashed' : 'cookie-policy';

		$GLOBALS['jmc_test_posts'] = [ 5 => $this->page( id: 5, slug: $slug, status: $status ) ];

		jmc_ensure_legal_pages();

		self::assertSame(
			[ 'privacy-policy', 'terms-and-conditions', 'accessibility-statement' ],
			array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' )
		);
		self::assertSame( 'page', $GLOBALS['jmc_test_get_posts_queries'][0]['post_type'] );
		self::assertSame( [ 'privacy-policy', 'privacy-policy__trashed' ], $GLOBALS['jmc_test_get_posts_queries'][0]['post_name__in'] );
		self::assertSame(
			[ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
			$GLOBALS['jmc_test_get_posts_queries'][0]['post_status']
		);
	}

	/**
	 * Statuses an existing page can have.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function page_status_provider(): array {
		return [
			'published' => [ 'publish' ],
			'draft'     => [ 'draft' ],
			'private'   => [ 'private' ],
			'trashed'   => [ 'trash' ],
		];
	}

	/**
	 * Each page is linked from the Legal menu by ID, in the registered order.
	 */
	public function test_links_pages_from_legal_menu_in_order(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_ensure_legal_pages();

		$links = $this->menu_blocks();

		self::assertSame(
			[
				[ 1001, 'Privacy Policy' ],
				[ 1002, 'Cookie Policy' ],
				[ 1003, 'Terms and Conditions' ],
				[ 1004, 'Accessibility Statement' ],
			],
			array_map(
				static fn ( array $link ): array => [ $link['attrs']['id'], $link['attrs']['label'] ],
				$links
			)
		);

		foreach ( $links as $link ) {
			self::assertSame( 'core/navigation-link', $link['blockName'] );
			self::assertSame( 'page', $link['attrs']['type'] );
			self::assertSame( 'post-type', $link['attrs']['kind'] );
		}

		self::assertCount( 1, $GLOBALS['jmc_test_updated_posts'] );
	}

	/**
	 * Existing pages are linked by their own ID and title; trashed pages are
	 * not linked.
	 */
	public function test_links_existing_pages_but_not_trashed_pages(): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => 3 ];
		$GLOBALS['jmc_test_posts']   = [
			3  => $this->page( id: 3, slug: 'privacy-policy', status: 'draft', title: 'Privacy' ),
			4  => $this->page( id: 4, slug: 'cookie-policy__trashed', status: 'trash' ),
			10 => $this->legal_menu( blocks: [] ),
		];

		jmc_ensure_legal_pages();

		self::assertSame(
			[ [ 3, 'Privacy' ], [ 1001, 'Terms and Conditions' ], [ 1002, 'Accessibility Statement' ] ],
			array_map(
				static fn ( array $link ): array => [ $link['attrs']['id'], $link['attrs']['label'] ],
				$this->menu_blocks()
			)
		);
	}

	/**
	 * Pages the menu already links to, at any depth, are not linked again,
	 * and the editor's links are kept first and unchanged.
	 */
	public function test_does_not_duplicate_existing_links(): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => 3 ];

		$existing = [
			$this->link_block( id: 0, label: 'Sitemap', kind: 'custom' ),
			[
				'blockName'   => 'core/navigation-submenu',
				'attrs'       => [ 'label' => 'More' ],
				'innerBlocks' => [ $this->link_block( id: 3, label: 'Our privacy policy', kind: 'post-type' ) ],
			],
			$this->link_block( id: 4, label: 'Cookies', kind: 'post-type' ),
		];

		$GLOBALS['jmc_test_posts'] = [
			3  => $this->page( id: 3, slug: 'privacy-policy', status: 'publish' ),
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'publish' ),
			10 => $this->legal_menu( blocks: $existing ),
		];

		jmc_ensure_legal_pages();

		$blocks = $this->menu_blocks();

		self::assertSame( $existing, array_slice( $blocks, 0, 3 ) );
		self::assertSame(
			[ 'Terms and Conditions', 'Accessibility Statement' ],
			array_map( static fn ( array $link ): string => $link['attrs']['label'], array_slice( $blocks, 3 ) )
		);
	}

	/**
	 * A taxonomy link with the same ID as a page does not count as a link
	 * to that page.
	 */
	public function test_ignores_taxonomy_links_with_matching_id(): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => 3 ];
		$GLOBALS['jmc_test_posts']   = [
			3  => $this->page( id: 3, slug: 'privacy-policy', status: 'publish' ),
			10 => $this->legal_menu( blocks: [ $this->link_block( id: 3, label: 'News', kind: 'taxonomy' ) ] ),
		];

		jmc_ensure_legal_pages();

		self::assertSame( [ 3, 3, 1001, 1002, 1003 ], array_map( static fn ( array $link ): int => $link['attrs']['id'], $this->menu_blocks() ) );
	}

	/**
	 * The menu is not updated when every page is already linked.
	 */
	public function test_leaves_menu_alone_when_every_page_is_linked(): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => 1 ];
		$slugs                       = [ 'privacy-policy', 'cookie-policy', 'terms-and-conditions', 'accessibility-statement' ];

		foreach ( $slugs as $index => $slug ) {
			$GLOBALS['jmc_test_posts'][ $index + 1 ] = $this->page( id: $index + 1, slug: $slug, status: 'publish' );
		}

		$GLOBALS['jmc_test_posts'][10] = $this->legal_menu(
			blocks: array_map(
				fn ( int $id ): array => $this->link_block( id: $id, label: 'Page', kind: 'post-type' ),
				[ 1, 2, 3, 4 ]
			)
		);

		jmc_ensure_legal_pages();

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
	}

	/**
	 * Without a published Legal menu the pages are still created, no menu is
	 * changed, and the run is recorded.
	 *
	 * @dataProvider unusable_menu_provider
	 *
	 * @param array<int,object> $posts Post fixtures.
	 */
	public function test_creates_pages_without_usable_menu( array $posts ): void {
		$GLOBALS['jmc_test_posts'] = $posts;

		jmc_ensure_legal_pages();

		self::assertCount( 4, $GLOBALS['jmc_test_inserted_posts'] );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
		self::assertSame( self::ALL_SLUGS, $GLOBALS['jmc_test_options']['jmc_legal_pages'] );
	}

	/**
	 * Fixtures without a published `legal` menu.
	 *
	 * @return array<string,array{0:array<int,object>}>
	 */
	public function unusable_menu_provider(): array {
		return [
			'no menu'      => [ [] ],
			'draft menu'   => [ [ 10 => $this->legal_menu( blocks: [], status: 'draft' ) ] ],
			'trashed menu' => [ [ 10 => $this->legal_menu( blocks: [], status: 'trash' ) ] ],
		];
	}

	/**
	 * Nothing is queried, created or linked once every page has been
	 * handled, so a page or link an editor deletes is not restored.
	 */
	public function test_does_nothing_when_already_run(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_legal_pages' => self::ALL_SLUGS ];
		$GLOBALS['jmc_test_posts']   = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_ensure_legal_pages();

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_get_posts_queries', $GLOBALS );
	}

	/**
	 * Running setup twice creates and links each page once.
	 */
	public function test_is_idempotent(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_ensure_legal_pages();
		jmc_ensure_legal_pages();

		self::assertCount( 4, $GLOBALS['jmc_test_inserted_posts'] );
		self::assertCount( 1, $GLOBALS['jmc_test_updated_posts'] );
		self::assertCount( 4, $this->menu_blocks() );
	}

	/**
	 * A newly registered page is created and linked, while pages and links
	 * for slugs already handled are left as the editor has them.
	 */
	public function test_only_handles_newly_registered_pages(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_legal_pages' => 'privacy-policy,cookie-policy,terms-and-conditions' ];
		$GLOBALS['jmc_test_posts']   = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_ensure_legal_pages();

		self::assertSame( [ 'accessibility-statement' ], array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame( [ 1001 ], array_map( static fn ( array $link ): int => $link['attrs']['id'], $this->menu_blocks() ) );
		self::assertSame( self::ALL_SLUGS, $GLOBALS['jmc_test_options']['jmc_legal_pages'] );
	}

	/**
	 * Pages unticked in the settings are skipped by the automatic setup.
	 */
	public function test_skips_unticked_pages(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_legal_pages_disabled' => [ 'cookie-policy', 'unknown' ] ];
		$GLOBALS['jmc_test_posts']   = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_ensure_legal_pages();

		self::assertSame(
			[ 'privacy-policy', 'terms-and-conditions', 'accessibility-statement' ],
			array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' )
		);
		self::assertCount( 3, $this->menu_blocks() );
	}

	/**
	 * Ticking every page on an empty site creates and links each one, and
	 * records every page as ticked and handled.
	 */
	public function test_ticking_creates_and_links_pages(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_save_legal_pages( ticked: array_keys( jmc_legal_pages() ) );

		self::assertSame( array_keys( jmc_legal_pages() ), array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame( [ 'draft' ], array_unique( array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_status' ) ) );
		self::assertSame( [ 1001, 1002, 1003, 1004 ], $this->menu_link_ids() );
		self::assertSame( [], $GLOBALS['jmc_test_options']['jmc_legal_pages_disabled'] );
		self::assertSame( self::ALL_SLUGS, $GLOBALS['jmc_test_options']['jmc_legal_pages'] );
	}

	/**
	 * Ticking a page that exists reuses it, whatever its status, without
	 * changing it.
	 */
	public function test_ticking_reuses_existing_page(): void {
		$GLOBALS['jmc_test_posts'] = [
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'publish', title: 'Cookies' ),
			10 => $this->legal_menu( blocks: [] ),
		];

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertSame( [ 4 ], $this->menu_link_ids() );
		self::assertSame( [ 10 ], array_column( $GLOBALS['jmc_test_updated_posts'], 'ID' ) );
	}

	/**
	 * Ticking a page the menu already links to adds no second link, and
	 * saving the same choice again changes nothing.
	 */
	public function test_ticking_does_not_duplicate_links(): void {
		$GLOBALS['jmc_test_posts'] = [
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'draft' ),
			10 => $this->legal_menu( blocks: [ $this->link_block( id: 4, label: 'Cookies', kind: 'post-type' ) ] ),
		];

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );
		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );

		self::assertSame( [ 4 ], $this->menu_link_ids() );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
	}

	/**
	 * Ticking a page whose link an editor removed adds the link again, even
	 * after the automatic setup has handled it.
	 */
	public function test_ticking_restores_a_removed_link(): void {
		$GLOBALS['jmc_test_options'] = [ 'jmc_legal_pages' => self::ALL_SLUGS ];
		$GLOBALS['jmc_test_posts']   = [
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'publish' ),
			10 => $this->legal_menu( blocks: [] ),
		];

		jmc_ensure_legal_pages();

		self::assertSame( [], $this->menu_link_ids() );

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );

		self::assertSame( [ 4 ], $this->menu_link_ids() );
	}

	/**
	 * Ticking a trashed page neither duplicates nor links it.
	 */
	public function test_ticking_leaves_trashed_page_alone(): void {
		$GLOBALS['jmc_test_posts'] = [
			4  => $this->page( id: 4, slug: 'cookie-policy__trashed', status: 'trash' ),
			10 => $this->legal_menu( blocks: [] ),
		];

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
	}

	/**
	 * Unticking removes only that page's links, at any depth. The page,
	 * submenus, custom links and other pages' links are kept in order, and a
	 * submenu's inner content stays in step with its links.
	 */
	public function test_unticking_removes_only_the_page_link(): void {
		$GLOBALS['jmc_test_options'] = [ 'wp_page_for_privacy_policy' => 3 ];

		$submenu = [
			'blockName'    => 'core/navigation-submenu',
			'attrs'        => [ 'label' => 'More' ],
			'innerBlocks'  => [
				$this->link_block( id: 4, label: 'Cookies', kind: 'post-type' ),
				$this->link_block( id: 0, label: 'Sitemap', kind: 'custom' ),
			],
			'innerHTML'    => '<ul></ul>',
			'innerContent' => [ '<ul>', null, null, '</ul>' ],
		];

		$GLOBALS['jmc_test_posts'] = [
			3  => $this->page( id: 3, slug: 'privacy-policy', status: 'publish' ),
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'publish' ),
			10 => $this->legal_menu(
				blocks: [
					$this->link_block( id: 3, label: 'Privacy', kind: 'post-type' ),
					$this->link_block( id: 4, label: 'Cookies', kind: 'post-type' ),
					$this->link_block( id: 4, label: 'News', kind: 'taxonomy' ),
					$submenu,
				]
			),
		];

		$cookie_page = clone $GLOBALS['jmc_test_posts'][4];

		jmc_save_legal_pages( ticked: [ 'privacy-policy' ] );

		$blocks = $this->menu_blocks();

		self::assertSame( [ 'Privacy', 'News', 'More' ], array_map( static fn ( array $block ): string => $block['attrs']['label'], $blocks ) );
		self::assertSame( [ 'Sitemap' ], array_map( static fn ( array $block ): string => $block['attrs']['label'], $blocks[2]['innerBlocks'] ) );
		self::assertSame( [ '<ul>', null, '</ul>' ], $blocks[2]['innerContent'] );
		self::assertEquals( $cookie_page, $GLOBALS['jmc_test_posts'][4] );
		self::assertSame( [ 10 ], array_column( $GLOBALS['jmc_test_updated_posts'], 'ID' ) );
		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertSame(
			[ 'cookie-policy', 'terms-and-conditions', 'accessibility-statement' ],
			$GLOBALS['jmc_test_options']['jmc_legal_pages_disabled']
		);
	}

	/**
	 * Unticking every page creates nothing, keeps the pages, and leaves a
	 * menu without legal links unchanged.
	 */
	public function test_unticking_missing_or_unlinked_pages_changes_nothing(): void {
		$GLOBALS['jmc_test_posts'] = [
			4  => $this->page( id: 4, slug: 'cookie-policy', status: 'draft' ),
			10 => $this->legal_menu( blocks: [ $this->link_block( id: 0, label: 'Sitemap', kind: 'custom' ) ] ),
		];

		jmc_save_legal_pages( ticked: [] );

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
		self::assertSame( array_keys( jmc_legal_pages() ), $GLOBALS['jmc_test_options']['jmc_legal_pages_disabled'] );
	}

	/**
	 * Re-ticking an unticked page links the same page again.
	 */
	public function test_reticking_reuses_the_page(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );
		jmc_save_legal_pages( ticked: [] );

		self::assertSame( [], $this->menu_link_ids() );

		jmc_save_legal_pages( ticked: [ 'cookie-policy' ] );

		self::assertCount( 1, $GLOBALS['jmc_test_inserted_posts'] );
		self::assertSame( [ 1001 ], $this->menu_link_ids() );
	}

	/**
	 * Unregistered and malformed ticked values are ignored.
	 */
	public function test_ignores_unregistered_ticked_values(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_save_legal_pages( ticked: [ 'about-us', [ 'cookie-policy' ], 7, 'terms-and-conditions', 'terms-and-conditions' ] );

		self::assertSame( [ 'terms-and-conditions' ], array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame( [ 1001 ], $this->menu_link_ids() );
	}

	/**
	 * After a save, the automatic setup does nothing.
	 */
	public function test_saving_marks_every_page_handled(): void {
		$GLOBALS['jmc_test_posts'] = [ 10 => $this->legal_menu( blocks: [] ) ];

		jmc_save_legal_pages( ticked: [] );
		jmc_ensure_legal_pages();

		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
	}

	/**
	 * Read the IDs of the Legal menu fixture's top-level links.
	 *
	 * @return int[] Linked IDs.
	 */
	private function menu_link_ids(): array {
		return array_map( static fn ( array $link ): int => $link['attrs']['id'], $this->menu_blocks() );
	}

	/**
	 * Read the Legal menu fixture's blocks.
	 *
	 * @return array<int,array<string,mixed>> Parsed menu blocks.
	 */
	private function menu_blocks(): array {
		return parse_blocks( $GLOBALS['jmc_test_posts'][10]->post_content );
	}

	/**
	 * Build a page fixture.
	 *
	 * @param int    $id     Page ID.
	 * @param string $slug   Page slug.
	 * @param string $status Post status.
	 * @param string $title  Page title.
	 *
	 * @return object Post fixture.
	 */
	private function page( int $id, string $slug, string $status, string $title = 'Page' ): object {
		return (object) [
			'ID'          => $id,
			'post_type'   => 'page',
			'post_name'   => $slug,
			'post_status' => $status,
			'post_title'  => $title,
		];
	}

	/**
	 * Build the `legal` menu fixture with ID 10.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed menu blocks.
	 * @param string                         $status Post status.
	 *
	 * @return object Post fixture.
	 */
	private function legal_menu( array $blocks, string $status = 'publish' ): object {
		return (object) [
			'ID'           => 10,
			'post_type'    => 'wp_navigation',
			'post_name'    => 'legal',
			'post_status'  => $status,
			'post_title'   => 'Legal',
			'post_content' => serialize_blocks( $blocks ),
		];
	}

	/**
	 * Build a parsed `core/navigation-link` block.
	 *
	 * @param int    $id    Linked post or term ID, or 0 for a custom link.
	 * @param string $label Link label.
	 * @param string $kind  Link kind: `post-type`, `taxonomy` or `custom`.
	 *
	 * @return array<string,mixed> Parsed block.
	 */
	private function link_block( int $id, string $label, string $kind ): array {
		return [
			'blockName'   => 'core/navigation-link',
			'attrs'       => [
				'id'    => $id,
				'label' => $label,
				'url'   => 'https://example.com/' . $id . '/',
				'kind'  => $kind,
			],
			'innerBlocks' => [],
		];
	}
}
