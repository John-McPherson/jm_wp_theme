<?php
/**
 * Footer template contract tests.
 *
 * @package JMC_Custom_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests the theme-owned FSE footer contracts.
 */
final class FooterTemplateTest extends TestCase {

	/**
	 * Theme root directory.
	 *
	 * @var string
	 */
	private string $theme_root;

	/**
	 * Prepare paths used by each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->theme_root = dirname( __DIR__, 3 );
	}

	/**
	 * Read a required theme file.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 * @return string
	 */
	private function read_theme_file( string $relative_path ): string {
		$path = $this->theme_root . '/' . $relative_path;

		$this->assertFileExists( $path );

		$contents = file_get_contents( $path );

		$this->assertNotFalse(
			$contents,
			sprintf( 'Unable to read %s.', $relative_path )
		);

		return $contents;
	}

	/**
	 * The footer template part must be registered in theme.json.
	 */
	public function test_theme_json_registers_footer_template_part(): void {
		$theme_json = json_decode(
			$this->read_theme_file( 'theme.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$this->assertIsArray( $theme_json );
		$this->assertArrayHasKey( 'templateParts', $theme_json );

		$footer_parts = array_values(
			array_filter(
				$theme_json['templateParts'],
				static function ( array $part ): bool {
					return 'footer' === ( $part['name'] ?? null );
				}
			)
		);

		$this->assertCount(
			1,
			$footer_parts,
			'Exactly one footer template part should be registered.'
		);

		$this->assertSame( 'footer', $footer_parts[0]['area'] );
		$this->assertNotEmpty( $footer_parts[0]['title'] );
	}

	/**
	 * Every theme template must include the registered footer as a <footer>
	 * landmark, after the page content.
	 */
	public function test_templates_include_footer_part(): void {
		$templates = glob( $this->theme_root . '/templates/*.html' );

		$this->assertNotEmpty( $templates );

		foreach ( $templates as $path ) {
			$template = $this->read_theme_file(
				'templates/' . basename( $path )
			);

			$this->assertMatchesRegularExpression(
				'/<!--\s+wp:template-part\s+\{(?=[^}]*"slug"\s*:\s*"footer")(?=[^}]*"tagName"\s*:\s*"footer")[^}]*\}\s+\/-->\s*$/',
				$template,
				sprintf( '%s should end with the footer template part.', basename( $path ) )
			);
		}
	}

	/**
	 * The footer must wrap its content in the palette-aware footer container,
	 * with the business details then a first menu column inside it. The
	 * container and business details are locked against removal because
	 * neither block is insertable; menu columns can be added and removed.
	 */
	public function test_footer_container_contains_locked_footer_blocks(): void {
		$footer = $this->read_theme_file( 'parts/footer.html' );

		$this->assertMatchesRegularExpression(
			'/^\s*<!--\s+wp:jmc\/footer\s+\{"palette":"inverse","lock":\{"remove":true\}\}\s+-->'
			. '\s*<!--\s+wp:jmc\/footer-business\s+\{"lock":\{"remove":true\}\}\s+\/-->'
			. '\s*<!--\s+wp:jmc\/footer-navigation\s+\/-->'
			. '\s*<!--\s+\/wp:jmc\/footer\s+-->\s*$/',
			$footer
		);
	}

	/**
	 * Business identity must not be hard-coded into the template.
	 */
	public function test_footer_does_not_hard_code_business_details(): void {
		$footer = strtolower(
			$this->read_theme_file( 'parts/footer.html' )
		);

		$this->assertStringNotContainsString( 'amc electrical', $footer );
		$this->assertDoesNotMatchRegularExpression( '/\b(tel|mailto):/', $footer );
		$this->assertDoesNotMatchRegularExpression( '/\bhref\s*=/', $footer );
	}
}
