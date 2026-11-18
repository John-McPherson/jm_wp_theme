<?php
/**
 * Primary navigation render tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/navigation.php';

/**
 * Test the primary navigation block render callback.
 */
final class NavigationRenderTest extends TestCase {

	/**
	 * Register a menu fixture with a link and a submenu.
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['jmc_test_unique_id'] = 0;
		$GLOBALS['jmc_test_posts']     = [
			10 => $this->navigation_post(
				title: 'Primary',
				blocks: [
					$this->block( 'core/navigation-link', id: 5, label: 'Home', url: '/' ),
					$this->block(
						'core/navigation-submenu',
						id: 6,
						label: 'Services',
						url: '/services/',
						inner_blocks: [
							$this->block( 'core/navigation-link', id: 7, label: 'Domestic', url: '/services/domestic/' ),
							$this->block( 'core/navigation-link', id: 0, label: 'Commercial', url: '/services/commercial/' ),
						]
					),
				]
			),
		];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_posts'],
			$GLOBALS['jmc_test_current_page'],
			$GLOBALS['jmc_test_unique_id'],
			$GLOBALS['jmc_test_fired_actions']
		);

		parent::tearDown();
	}

	/**
	 * A submenu parent renders as a link plus a separate disclosure button
	 * controlling a nested list.
	 */
	public function test_renders_submenu_parent_as_link_and_disclosure(): void {
		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertStringContainsString( '<li class="jmc-navigation__item jmc-navigation__item--has-submenu">', $html );
		self::assertStringContainsString( '<a href="/services/" class="jmc-navigation__link">Services</a>', $html );
		self::assertStringContainsString(
			'<button type="button" class="jmc-navigation__submenu-toggle" aria-expanded="false" aria-controls="jmc-navigation__submenu-2">',
			$html
		);
		self::assertMatchesRegularExpression( '/<span class="sro">\s*Services submenu\s*<\/span>/', $html );
		self::assertStringContainsString( '<ul id="jmc-navigation__submenu-2" class="jmc-navigation__submenu">', $html );
		self::assertStringContainsString(
			'<li class="jmc-navigation__submenu-item">
								<a href="/services/domestic/" class="jmc-navigation__submenu-link">Domestic</a>',
			$html
		);
		self::assertMatchesRegularExpression( '/Home.*Services.*Domestic.*Commercial/s', $html );
	}

	/**
	 * Submenus are rendered open so every link works without JavaScript.
	 */
	public function test_submenus_are_not_hidden_in_server_markup(): void {
		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertDoesNotMatchRegularExpression( '/<ul[^>]*jmc-navigation__submenu[^>]*hidden/', $html );
		self::assertSame( 4, substr_count( $html, '<a href=' ) );
	}

	/**
	 * A plain link has no disclosure button.
	 */
	public function test_plain_links_have_no_submenu_controls(): void {
		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertStringContainsString( '<li class="jmc-navigation__item">', $html );
		self::assertSame( 1, substr_count( $html, 'jmc-navigation__submenu-toggle' ) );
		self::assertSame( 1, substr_count( $html, '<ul id="jmc-navigation__submenu-' ) );
	}

	/**
	 * Every submenu toggle controls its own unique, existing list, and no
	 * ID collides with the primary menu toggle's list.
	 */
	public function test_ids_are_unique_and_associated(): void {
		$GLOBALS['jmc_test_posts'][11] = $this->navigation_post(
			title: 'Primary',
			blocks: [
				$this->block( 'core/navigation-submenu', label: 'A', url: '/a/', inner_blocks: [ $this->block( 'core/navigation-link', label: 'A1', url: '/a/1/' ) ] ),
				$this->block( 'core/navigation-submenu', label: 'B', url: '/b/', inner_blocks: [ $this->block( 'core/navigation-link', label: 'B1', url: '/b/1/' ) ] ),
			]
		);

		$html = $this->render_navigation( [ 'navigationId' => 11 ] );

		preg_match_all( '/\sid="([^"]+)"/', $html, $ids );
		preg_match_all( '/aria-controls="([^"]+)"/', $html, $controls );

		self::assertSame( $ids[1], array_unique( $ids[1] ) );
		self::assertCount( 3, $controls[1] );
		self::assertSame( [], array_diff( $controls[1], $ids[1] ) );
	}

	/**
	 * A submenu parent without a URL renders as a labelled toggle, not a link.
	 */
	public function test_parent_without_url_renders_toggle_only(): void {
		$GLOBALS['jmc_test_posts'][12] = $this->navigation_post(
			title: 'Primary',
			blocks: [
				$this->block(
					'core/navigation-submenu',
					label: 'About',
					inner_blocks: [ $this->block( 'core/navigation-link', label: 'Team', url: '/team/' ) ]
				),
			]
		);

		$html = $this->render_navigation( [ 'navigationId' => 12 ] );

		self::assertStringNotContainsString( 'jmc-navigation__link', $html );
		self::assertStringNotContainsString( 'About submenu', $html );
		self::assertStringContainsString( '<span class="jmc-navigation__label">About</span>', $html );
		self::assertMatchesRegularExpression(
			'/<button type="button" class="jmc-navigation__submenu-toggle jmc-navigation__submenu-toggle--label"[^>]*>\s*About\s*<span/',
			$html
		);
	}

	/**
	 * The current page is marked at either level, and a parent of the
	 * current page is flagged as its ancestor.
	 */
	public function test_marks_current_page_in_submenu(): void {
		$GLOBALS['jmc_test_current_page'] = 7;

		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		self::assertStringContainsString( 'class="jmc-navigation__submenu-link" aria-current="page">Domestic</a>', $html );
		self::assertStringContainsString( 'jmc-navigation__item--has-submenu jmc-navigation__item--current-ancestor', $html );
	}

	/**
	 * A current primary link is marked; custom links are never current.
	 */
	public function test_marks_current_primary_page(): void {
		$GLOBALS['jmc_test_current_page'] = 6;

		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		self::assertStringContainsString( 'class="jmc-navigation__link" aria-current="page">Services</a>', $html );
		self::assertStringNotContainsString( 'current-ancestor', $html );
	}

	/**
	 * Labels and destinations are escaped at both levels.
	 */
	public function test_escapes_menu_values(): void {
		$GLOBALS['jmc_test_posts'][13] = $this->navigation_post(
			title: 'Primary',
			blocks: [
				$this->block(
					'core/navigation-submenu',
					label: '<b>Bold</b> "quoted"',
					url: '/a?b=1&c=2',
					inner_blocks: [ $this->block( 'core/navigation-link', label: '<i>Child</i>', url: '/c/' ) ]
				),
			]
		);

		$html = $this->render_navigation( [ 'navigationId' => 13 ] );

		self::assertStringNotContainsString( '<b>', $html );
		self::assertStringNotContainsString( '<i>', $html );
		self::assertStringContainsString( '&lt;b&gt;Bold&lt;/b&gt; &quot;quoted&quot; submenu', $html );
		self::assertStringContainsString( '&lt;i&gt;Child&lt;/i&gt;', $html );
		self::assertStringContainsString( 'href="/a?b=1&amp;c=2"', $html );
	}

	/**
	 * Nesting deeper than one level renders as a single, well-formed submenu.
	 */
	public function test_unsupported_depth_renders_flat_submenu(): void {
		$GLOBALS['jmc_test_posts'][14] = $this->navigation_post(
			title: 'Primary',
			blocks: [
				$this->block(
					'core/navigation-submenu',
					label: 'Services',
					url: '/services/',
					inner_blocks: [
						$this->block(
							'core/navigation-submenu',
							label: 'Domestic',
							url: '/domestic/',
							inner_blocks: [ $this->block( 'core/navigation-link', label: 'Rewiring', url: '/rewiring/' ) ]
						),
					]
				),
			]
		);

		$html = $this->render_navigation( [ 'navigationId' => 14 ] );

		self::assertSame( 1, substr_count( $html, 'jmc-navigation__submenu"' ) );
		self::assertSame( 1, substr_count( $html, '<button type="button"' ) );
		self::assertSame( 2, substr_count( $html, 'jmc-navigation__submenu-item' ) );
		self::assertSame( substr_count( $html, '<ul' ), substr_count( $html, '</ul>' ) );
		self::assertSame( substr_count( $html, '<li' ), substr_count( $html, '</li>' ) );
		self::assertCount( 1, $GLOBALS['jmc_test_fired_actions'] );
	}

	/**
	 * Nothing is rendered without a usable menu.
	 *
	 * @dataProvider unusable_menu_provider
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function test_renders_nothing_without_usable_menu( array $attributes ): void {
		$GLOBALS['jmc_test_posts'][20] = $this->navigation_post( title: 'Empty', blocks: [] );
		$GLOBALS['jmc_test_posts'][21] = (object) [
			'post_type'    => 'page',
			'post_title'   => 'Not a menu',
			'post_content' => '[]',
		];
		$GLOBALS['jmc_test_posts'][22] = $this->navigation_post(
			title: 'Only empty submenus',
			blocks: [ $this->block( 'core/navigation-submenu', label: 'Nothing' ) ]
		);

		self::assertSame( '', trim( $this->render_navigation( $attributes ) ) );
	}

	/**
	 * Attribute sets that do not resolve to a menu with items.
	 *
	 * @return array<string,array{0:array<string,mixed>}>
	 */
	public function unusable_menu_provider(): array {
		return [
			'omitted'        => [ [] ],
			'missing post'   => [ [ 'navigationId' => 99 ] ],
			'not a menu'     => [ [ 'navigationId' => 21 ] ],
			'no links'       => [ [ 'navigationId' => 20 ] ],
			'empty submenus' => [ [ 'navigationId' => 22 ] ],
		];
	}

	/**
	 * Build a `wp_navigation` post fixture.
	 *
	 * @param string                         $title  Menu title.
	 * @param array<int,array<string,mixed>> $blocks Parsed menu blocks.
	 *
	 * @return object Post fixture.
	 */
	private function navigation_post( string $title, array $blocks ): object {
		return (object) [
			'post_type'    => 'wp_navigation',
			'post_title'   => $title,
			'post_content' => json_encode( $blocks, JSON_THROW_ON_ERROR ), // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The parse_blocks() test double decodes plain JSON.
		];
	}

	/**
	 * Build a parsed Navigation link or submenu block.
	 *
	 * @param string                         $block_name   Block name.
	 * @param int                            $id           Linked post ID, or 0 for a custom link.
	 * @param string                         $label        Item label.
	 * @param string                         $url          Item destination; empty to omit.
	 * @param array<int,array<string,mixed>> $inner_blocks Nested blocks.
	 *
	 * @return array<string,mixed> Parsed block.
	 */
	private function block(
		string $block_name,
		int $id = 0,
		string $label = '',
		string $url = '',
		array $inner_blocks = []
	): array {
		return [
			'blockName'   => $block_name,
			'attrs'       => array_filter(
				[
					'id'    => $id,
					'label' => $label,
					'url'   => $url,
				]
			),
			'innerBlocks' => $inner_blocks,
		];
	}

	/**
	 * Execute the real navigation render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * data that triggers them fails the test.
	 *
	 * @param array<string,mixed> $attributes Block attributes to render.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_navigation( array $attributes ): string {
		$content = '';

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/navigation/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
