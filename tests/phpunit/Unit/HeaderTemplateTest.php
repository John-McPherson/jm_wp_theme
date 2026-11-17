<?php
/**
 * Header template contract tests.
 *
 * @package JMC_Custom_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests the theme-owned FSE header contracts.
 */
final class HeaderTemplateTest extends TestCase {

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
	 * The header template part must be registered in theme.json.
	 */
	public function test_theme_json_registers_header_template_part(): void {
		$theme_json = json_decode(
			$this->read_theme_file( 'theme.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);

		$this->assertIsArray( $theme_json );
		$this->assertArrayHasKey( 'templateParts', $theme_json );

		$header_parts = array_values(
			array_filter(
				$theme_json['templateParts'],
				static function ( array $part ): bool {
					return 'header' === ( $part['name'] ?? null );
				}
			)
		);

		$this->assertCount(
			1,
			$header_parts,
			'Exactly one header template part should be registered.'
		);

		$this->assertSame( 'header', $header_parts[0]['area'] );
		$this->assertNotEmpty( $header_parts[0]['title'] );
	}

	/**
	 * The index template must include the registered header.
	 */
	public function test_index_template_includes_header_part(): void {
		$template = $this->read_theme_file(
			'templates/index.html'
		);

		$this->assertMatchesRegularExpression(
			'/<!--\s+wp:template-part\s+\{[^}]*"slug"\s*:\s*"header"[^}]*\}\s+\/-->/',
			$template
		);
	}

	/**
	 * The header must expose editable WordPress identity blocks.
	 */
	public function test_header_contains_editable_site_identity(): void {
		$header = $this->read_theme_file(
			'parts/header.html'
		);

		$this->assertStringContainsString(
			'<!-- wp:site-logo',
			$header
		);

		$this->assertStringContainsString(
			'<!-- wp:site-title',
			$header
		);

		$this->assertStringContainsString(
			'jmc-logo',
			$header
		);
	}

	/**
	 * Business identity must not be hard-coded into the template.
	 */
	public function test_header_does_not_hard_code_site_identity(): void {
		$header = strtolower(
			$this->read_theme_file( 'parts/header.html' )
		);

		$this->assertStringNotContainsString(
			'amc electrical',
			$header
		);

		$this->assertDoesNotMatchRegularExpression(
			'/<img\b/i',
			$header,
			'The template should use the Site Logo block.'
		);

		$this->assertDoesNotMatchRegularExpression(
			'/\bsrc\s*=/i',
			$header,
			'The template should not contain a hard-coded logo URL.'
		);
	}
}
