<?php
/**
 * Site logo render tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the site logo block render callback and its inline SVG.
 */
final class SiteLogoRenderTest extends TestCase {

	/**
	 * Temporary theme root holding the build files a test supplies.
	 *
	 * @var string
	 */
	private string $theme_root;

	/**
	 * Create a temporary theme root with the shipped logo in its build path.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->theme_root = sys_get_temp_dir() . '/jmc-site-logo-' . uniqid();

		$GLOBALS['jmc_test_theme_root'] = $this->theme_root;
		$GLOBALS['jmc_test_bloginfo']   = [ 'name' => 'AMC Electrical' ];

		$this->write_logo( (string) file_get_contents( dirname( __DIR__, 3 ) . '/src/assets/svgs/logo.svg' ) );
	}

	/**
	 * Remove the temporary theme root and test globals.
	 */
	protected function tearDown(): void {
		$logo = $this->theme_root . '/build/assets/svgs/logo.svg';

		if ( is_file( $logo ) ) {
			unlink( $logo );
		}

		foreach ( [ '/build/assets/svgs', '/build/assets', '/build', '' ] as $directory ) {
			if ( is_dir( $this->theme_root . $directory ) ) {
				rmdir( $this->theme_root . $directory );
			}
		}

		unset( $GLOBALS['jmc_test_theme_root'], $GLOBALS['jmc_test_bloginfo'] );

		parent::tearDown();
	}

	/**
	 * The logo is an inline SVG inside a link to the homepage.
	 */
	public function test_renders_inline_svg_linked_to_homepage(): void {
		$html = $this->render_logo( [] );

		self::assertMatchesRegularExpression(
			'/^<a href="https:\/\/example\.com\/" rel="home" class="jmc-site-logo">\s*<svg\b/',
			trim( $html )
		);
		self::assertStringNotContainsString( '<img', $html );
		self::assertSame( 1, substr_count( $html, '<svg' ) );
	}

	/**
	 * The SVG is decorative and the link is named by the site name.
	 */
	public function test_link_is_named_by_site_name_and_svg_is_hidden(): void {
		$html = $this->render_logo( [] );

		self::assertMatchesRegularExpression( '/<svg\b[^>]*\baria-hidden="true"/', $html );
		self::assertMatchesRegularExpression( '/<svg\b[^>]*\bfocusable="false"/', $html );
		self::assertStringNotContainsString( '<title', $html );
		self::assertStringNotContainsString( 'aria-label', $html );
		self::assertStringContainsString( '<span class="jmc-site-logo__name">AMC Electrical</span>', $html );
	}

	/**
	 * The SVG keeps its dimensions so the browser reserves space before paint.
	 */
	public function test_svg_reserves_its_aspect_ratio(): void {
		$html = $this->render_logo( [] );

		self::assertMatchesRegularExpression( '/<svg\b[^>]*\bviewbox="73 299 1375 457"/i', $html );
		self::assertMatchesRegularExpression( '/<svg\b[^>]*\bwidth="1375"/', $html );
		self::assertMatchesRegularExpression( '/<svg\b[^>]*\bheight="457"/', $html );
	}

	/**
	 * Colours come only from the three token-driven classes.
	 */
	public function test_svg_colours_come_from_classes_not_hex(): void {
		$html = $this->render_logo( [] );

		self::assertDoesNotMatchRegularExpression( '/#[0-9a-f]{3,8}\b/i', $html );
		self::assertDoesNotMatchRegularExpression( '/\b(fill|stroke|style)=/i', $html );
		self::assertStringContainsString( '<g class="jmc-site-logo__primary">', $html );
		self::assertStringContainsString( '<g class="jmc-site-logo__accent">', $html );
		self::assertStringContainsString( '<g class="jmc-site-logo__secondary">', $html );
	}

	/**
	 * The site name is stripped of markup and escaped.
	 *
	 * @dataProvider site_name_provider
	 *
	 * @param string $name     Stored site name.
	 * @param string $expected Expected visually hidden link text.
	 */
	public function test_site_name_is_cleaned( string $name, string $expected ): void {
		$GLOBALS['jmc_test_bloginfo'] = [ 'name' => $name ];

		self::assertStringContainsString(
			'<span class="jmc-site-logo__name">' . $expected . '</span>',
			$this->render_logo( [] )
		);
	}

	/**
	 * Stored site names and the link text they produce.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function site_name_provider(): array {
		return [
			'plain'         => [ 'AMC Electrical', 'AMC Electrical' ],
			'special chars' => [ 'Smith & "Sons"', 'Smith &amp; &quot;Sons&quot;' ],
			'markup'        => [ '<b>AMC</b><script>alert(1)</script>', 'AMC' ],
			'empty'         => [ '', 'Home' ],
			'whitespace'    => [ '   ', 'Home' ],
			'only markup'   => [ '<i></i>', 'Home' ],
		];
	}

	/**
	 * The chosen block style class reaches the link, and custom classes are sanitised.
	 *
	 * @dataProvider class_name_provider
	 *
	 * @param mixed  $class_name Stored className attribute.
	 * @param string $expected   Expected class attribute value.
	 */
	public function test_class_names( mixed $class_name, string $expected ): void {
		self::assertStringContainsString(
			'class="' . $expected . '"',
			$this->render_logo( [ 'className' => $class_name ] )
		);
	}

	/**
	 * Stored className values and the resulting link classes.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function class_name_provider(): array {
		return [
			'brand style'      => [ 'is-style-brand', 'jmc-site-logo is-style-brand' ],
			'mono dark style'  => [ 'is-style-mono-dark', 'jmc-site-logo is-style-mono-dark' ],
			'mono light style' => [ 'is-style-mono-light', 'jmc-site-logo is-style-mono-light' ],
			'follow palette'   => [ 'is-style-default', 'jmc-site-logo is-style-default' ],
			'custom and style' => [ 'is-style-brand my-logo', 'jmc-site-logo is-style-brand my-logo' ],
			'unsafe'           => [ '"><script>', 'jmc-site-logo script' ],
			'empty'            => [ '', 'jmc-site-logo' ],
		];
	}

	/**
	 * Without the artwork the block renders nothing, so the site title shows.
	 */
	public function test_missing_artwork_renders_nothing(): void {
		unlink( $this->theme_root . '/build/assets/svgs/logo.svg' );

		self::assertSame( '', trim( $this->render_logo( [] ) ) );
	}

	/**
	 * A file with no SVG root renders nothing.
	 */
	public function test_non_svg_artwork_renders_nothing(): void {
		$this->write_logo( '<p>Not a logo</p>' );

		self::assertSame( '', trim( $this->render_logo( [] ) ) );
	}

	/**
	 * Markup outside the SVG allow-list is removed from the artwork.
	 */
	public function test_artwork_is_filtered_through_allow_list(): void {
		$this->write_logo(
			'<svg viewBox="0 0 10 10" onload="alert(1)" style="color:red">'
			. '<script>alert(2)</script>'
			. '<a href="javascript:alert(3)"><path d="M0 0H10" fill="#ff0000" onclick="alert(4)"/></a>'
			. '<use href="#x"/><foreignObject><iframe src="https://example.org"></iframe></foreignObject>'
			. '</svg>'
		);

		$html = $this->render_logo( [] );

		self::assertSame( 1, preg_match( '/<svg\b.*<\/svg>/s', $html, $svg ) );

		$html = $svg[0];

		self::assertStringContainsString( 'd="M0 0H10"', $html );

		foreach ( [ 'onload', 'onclick', 'style=', '<script', '<a ', 'javascript:', 'fill=', '<use', '<foreignobject', '<iframe', '#ff0000' ] as $forbidden ) {
			self::assertStringNotContainsStringIgnoringCase( $forbidden, $html );
		}
	}

	/**
	 * Write the logo file into the temporary theme's build directory.
	 *
	 * @param string $markup Logo file contents.
	 */
	private function write_logo( string $markup ): void {
		$directory = $this->theme_root . '/build/assets/svgs';

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory, 0777, true );
		}

		file_put_contents( $directory . '/logo.svg', $markup );
	}

	/**
	 * Execute the real site logo render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_logo( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/site-logo/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
