<?php
/**
 * Footer navigation render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the footer navigation block render callback.
 */
final class FooterNavigationRenderTest extends TestCase {

	/**
	 * Register a menu fixture with two links.
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['jmc_test_posts'] = [
			10 => $this->navigation_post(
				title: 'Footer menu',
				blocks: [
					$this->link_block( id: 5, label: 'About', url: 'https://example.com/about/' ),
					$this->link_block( id: 0, label: 'Contact', url: 'https://example.com/contact/' ),
				]
			),
		];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_posts'], $GLOBALS['jmc_test_current_page'] );

		parent::tearDown();
	}

	/**
	 * A selected menu renders a labelled list of its links in order.
	 */
	public function test_renders_selected_menu(): void {
		$html = $this->render_navigation(
			[
				'navigationId' => 10,
				'className'    => 'is-custom',
			]
		);

		self::assertStringContainsString( '<nav class="jmc-footer-navigation is-custom" aria-label="Footer menu">', $html );
		self::assertStringContainsString( '<a href="https://example.com/about/" class="jmc-footer-navigation__link">About</a>', $html );
		self::assertMatchesRegularExpression( '/About.*Contact/s', $html );
		self::assertSame( 2, substr_count( $html, '<li class="jmc-footer-navigation__item">' ) );
		self::assertStringNotContainsString( 'aria-current', $html );
		self::assertStringNotContainsString( '<button', $html );
	}

	/**
	 * The current page's link is marked; custom links (no ID) never are.
	 */
	public function test_marks_current_page(): void {
		$GLOBALS['jmc_test_current_page'] = 5;

		$html = $this->render_navigation( [ 'navigationId' => 10 ] );

		self::assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		self::assertMatchesRegularExpression( '/aria-current="page">About</', $html );
	}

	/**
	 * An untitled menu falls back to a generic landmark label.
	 */
	public function test_untitled_menu_uses_fallback_label(): void {
		$GLOBALS['jmc_test_posts'][11] = $this->navigation_post(
			title: '  ',
			blocks: [ $this->link_block( id: 0, label: 'Home', url: '/' ) ]
		);

		$html = $this->render_navigation( [ 'navigationId' => 11 ] );

		self::assertStringContainsString( 'aria-label="Footer"', $html );
	}

	/**
	 * Labels and destinations are escaped.
	 */
	public function test_escapes_menu_values(): void {
		$GLOBALS['jmc_test_posts'][12] = $this->navigation_post(
			title: 'Menu "quoted"',
			blocks: [ $this->link_block( id: 0, label: '<b>Bold</b>', url: '/a?b=1&c=2' ) ]
		);

		$html = $this->render_navigation( [ 'navigationId' => 12 ] );

		self::assertStringContainsString( 'aria-label="Menu &quot;quoted&quot;"', $html );
		self::assertStringContainsString( '&lt;b&gt;Bold&lt;/b&gt;', $html );
		self::assertStringNotContainsString( '<b>', $html );
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

		self::assertSame( '', trim( $this->render_navigation( $attributes ) ) );
	}

	/**
	 * Attribute sets that do not resolve to a menu with links.
	 *
	 * @return array<string,array{0:array<string,mixed>}>
	 */
	public function unusable_menu_provider(): array {
		return [
			'omitted'      => [ [] ],
			'default'      => [ [ 'navigationId' => 0 ] ],
			'negative'     => [ [ 'navigationId' => -10 ] ],
			'wrong type'   => [ [ 'navigationId' => [ 10 ] ] ],
			'missing post' => [ [ 'navigationId' => 99 ] ],
			'not a menu'   => [ [ 'navigationId' => 21 ] ],
			'no links'     => [ [ 'navigationId' => 20 ] ],
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
	 * Execute the real footer navigation render callback.
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
	private function render_navigation( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/footer-navigation/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
