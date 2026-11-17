<?php
/**
 * Footer legal render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the footer legal block render callback.
 */
final class FooterLegalRenderTest extends TestCase {

	/**
	 * Register the site title and a published legal menu with two links.
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['jmc_test_bloginfo'] = [ 'name' => 'Example Site' ];
		$GLOBALS['jmc_test_posts']    = [
			10 => $this->legal_menu(
				title: 'Legal',
				blocks: [
					$this->link_block( id: 3, label: 'Privacy Policy', url: 'https://example.com/privacy-policy/' ),
					$this->link_block( id: 0, label: 'Terms', url: 'https://example.com/terms/' ),
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
			$GLOBALS['jmc_test_bloginfo'],
			$GLOBALS['jmc_test_current_page'],
			$GLOBALS['jmc_test_get_posts_queries']
		);

		parent::tearDown();
	}

	/**
	 * The credit, copyright and legal links render in reading order.
	 */
	public function test_renders_credit_copyright_and_legal_links(): void {
		$html = $this->render_legal(
			[
				'copyrightHolder' => 'Example Electrical Ltd',
				'className'       => 'is-custom',
			]
		);

		self::assertStringContainsString( '<div class="jmc-footer-legal is-custom">', $html );
		self::assertStringContainsString( '<p class="jmc-footer-legal__copyright">© 2030 Example Electrical Ltd</p>', $html );
		self::assertStringContainsString( '<nav class="jmc-footer-legal__navigation" aria-label="Legal">', $html );
		self::assertStringContainsString( '<a href="https://example.com/privacy-policy/" class="jmc-footer-legal__link">Privacy Policy</a>', $html );
		self::assertSame( 2, substr_count( $html, '<li class="jmc-footer-legal__item">' ) );
		self::assertMatchesRegularExpression( '/__credit.*__copyright.*<nav.*Privacy Policy.*Terms/s', $html );
		self::assertStringNotContainsString( 'aria-current', $html );
	}

	/**
	 * The credit names the site's author and links to their email.
	 */
	public function test_renders_author_credit(): void {
		$html = $this->render_legal( [] );

		self::assertMatchesRegularExpression(
			'#<p class="jmc-footer-legal__credit">\s*Site created by <a href="mailto:mcpherso@pm.me">John McPherson</a>\s*</p>#',
			$html
		);
	}

	/**
	 * The copyright holder falls back to the site title, then to the year
	 * alone, and rich-text markup is normalised.
	 *
	 * @dataProvider copyright_provider
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $site_title Site title.
	 * @param string              $expected   Expected copyright text.
	 */
	public function test_copyright_holder( array $attributes, string $site_title, string $expected ): void {
		$GLOBALS['jmc_test_bloginfo'] = [ 'name' => $site_title ];

		$html = $this->render_legal( $attributes );

		self::assertStringContainsString( '<p class="jmc-footer-legal__copyright">' . $expected . '</p>', $html );
	}

	/**
	 * Copyright holder and site title combinations.
	 *
	 * @return array<string,array{0:array<string,mixed>,1:string,2:string}>
	 */
	public function copyright_provider(): array {
		return [
			'holder'            => [ [ 'copyrightHolder' => 'Holder Ltd' ], 'Example Site', '© 2030 Holder Ltd' ],
			'omitted holder'    => [ [], 'Example Site', '© 2030 Example Site' ],
			'blank holder'      => [ [ 'copyrightHolder' => ' <br> ' ], 'Example Site', '© 2030 Example Site' ],
			'malformed holder'  => [ [ 'copyrightHolder' => [ 'Holder' ] ], 'Example Site', '© 2030 Example Site' ],
			'rich-text holder'  => [ [ 'copyrightHolder' => 'Smith &amp; Sons' ], 'Example Site', '© 2030 Smith &amp; Sons' ],
			'no holder or site' => [ [], '', '© 2030' ],
		];
	}

	/**
	 * The copyright holder is escaped.
	 */
	public function test_escapes_copyright_holder(): void {
		$html = $this->render_legal( [ 'copyrightHolder' => '&lt;script&gt;alert(1)&lt;/script&gt;' ] );

		self::assertStringContainsString( '© 2030 &lt;script&gt;alert(1)&lt;/script&gt;', $html );
		self::assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * The landmark is labelled with the menu title, or "Legal" when untitled.
	 */
	public function test_landmark_label(): void {
		$GLOBALS['jmc_test_posts'][10]->post_title = 'Legal "info"';

		self::assertStringContainsString( 'aria-label="Legal &quot;info&quot;"', $this->render_legal( [] ) );

		$GLOBALS['jmc_test_posts'][10]->post_title = '  ';

		self::assertStringContainsString( 'aria-label="Legal"', $this->render_legal( [] ) );
	}

	/**
	 * The current page's link is marked; custom links (no ID) never are.
	 */
	public function test_marks_current_page(): void {
		$GLOBALS['jmc_test_current_page'] = 3;

		$html = $this->render_legal( [] );

		self::assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		self::assertMatchesRegularExpression( '/aria-current="page">Privacy Policy</', $html );
	}

	/**
	 * Only the published `legal` menu is used.
	 */
	public function test_looks_up_published_legal_menu(): void {
		$this->render_legal( [] );

		self::assertSame( 'legal', $GLOBALS['jmc_test_get_posts_queries'][0]['name'] );
		self::assertSame( [ 'publish' ], $GLOBALS['jmc_test_get_posts_queries'][0]['post_status'] );
	}

	/**
	 * Without a usable legal menu the links are omitted, but the credit and
	 * copyright still render.
	 *
	 * @dataProvider unusable_menu_provider
	 *
	 * @param array<int,object> $posts Post fixtures.
	 */
	public function test_omits_links_without_usable_menu( array $posts ): void {
		$GLOBALS['jmc_test_posts'] = $posts;

		$html = $this->render_legal( [] );

		self::assertStringNotContainsString( '<nav', $html );
		self::assertStringNotContainsString( '<ul', $html );
		self::assertStringContainsString( 'jmc-footer-legal__credit', $html );
		self::assertStringContainsString( '© 2030 Example Site', $html );
	}

	/**
	 * Fixtures that do not resolve to a published legal menu with links.
	 *
	 * @return array<string,array{0:array<int,object>}>
	 */
	public function unusable_menu_provider(): array {
		$link = [ $this->link_block( id: 0, label: 'Terms', url: '/terms/' ) ];

		return [
			'no menu'      => [ [] ],
			'no links'     => [ [ 10 => $this->legal_menu( title: 'Legal', blocks: [] ) ] ],
			'draft menu'   => [ [ 10 => $this->legal_menu( title: 'Legal', blocks: $link, status: 'draft' ) ] ],
			'other slug'   => [ [ 10 => $this->legal_menu( title: 'Legal', blocks: $link, slug: 'footer' ) ] ],
			'invalid link' => [ [ 10 => $this->legal_menu( title: 'Legal', blocks: [ $this->link_block( id: 0, label: ' ', url: '/terms/' ) ] ) ] ],
		];
	}

	/**
	 * Build a `wp_navigation` post fixture.
	 *
	 * @param string                         $title  Menu title.
	 * @param array<int,array<string,mixed>> $blocks Parsed menu blocks.
	 * @param string                         $status Post status.
	 * @param string                         $slug   Menu slug.
	 *
	 * @return object Post fixture.
	 */
	private function legal_menu(
		string $title,
		array $blocks,
		string $status = 'publish',
		string $slug = 'legal'
	): object {
		return (object) [
			'post_type'    => 'wp_navigation',
			'post_name'    => $slug,
			'post_status'  => $status,
			'post_title'   => $title,
			'post_content' => json_encode( $blocks, JSON_THROW_ON_ERROR ), // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The parse_blocks() test double decodes plain JSON.
		];
	}

	/**
	 * Build a parsed `core/navigation-link` block.
	 *
	 * @param int    $id    Linked post ID, or 0 for a custom link.
	 * @param string $label Link label.
	 * @param string $url   Link destination.
	 *
	 * @return array<string,mixed> Parsed block.
	 */
	private function link_block( int $id, string $label, string $url ): array {
		return [
			'blockName'   => 'core/navigation-link',
			'attrs'       => [
				'id'    => $id,
				'label' => $label,
				'url'   => $url,
			],
			'innerBlocks' => [],
		];
	}

	/**
	 * Execute the real footer legal render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string,mixed> $attributes Block attributes to render.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_legal( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/footer-legal/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
