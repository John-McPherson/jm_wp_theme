<?php
/**
 * Trust and credentials section and credential list render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the trust and credentials section and credential list render callbacks.
 */
final class TrustCredentialsRenderTest extends TestCase {

	private const ITEM = '<li class="jmc-credential"><img src="logo.png" alt="NICEIC" /></li>';

	/**
	 * A list with credentials renders them in a semantic list.
	 */
	public function test_list_wraps_credentials(): void {
		$html = $this->render_list( [], self::ITEM );

		self::assertStringContainsString( '<ul class="jmc-credential-list" role="list">', $html );
		self::assertStringContainsString( self::ITEM, $html );
	}

	/**
	 * The list's anchor and custom class reach the list element.
	 */
	public function test_list_applies_anchor_and_class_name(): void {
		$html = $this->render_list(
			[
				'anchor'    => 'accreditations',
				'className' => 'is-compact',
			],
			self::ITEM
		);

		self::assertStringContainsString( '<ul id="accreditations" class="jmc-credential-list is-compact" role="list">', $html );
	}

	/**
	 * The list's anchor is escaped and its custom class sanitised.
	 */
	public function test_list_escapes_anchor_and_class_name(): void {
		$html = $this->render_list(
			[
				'anchor'    => '"><script>',
				'className' => 'is-compact" onclick="alert(1)',
			],
			self::ITEM
		);

		self::assertStringContainsString( '<ul id="&quot;&gt;&lt;script&gt;" class="jmc-credential-list is-compact onclickalert1" role="list">', $html );
		self::assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * A list whose credentials all rendered nothing is omitted.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param string $content Rendered inner-block content.
	 */
	public function test_omits_empty_list( string $content ): void {
		self::assertSame( '', trim( $this->render_list( [], $content ) ) );
	}

	/**
	 * The section defaults to no palette class so it inherits its surroundings.
	 */
	public function test_section_defaults_to_inherited_palette(): void {
		$html = $this->render_section( [], self::ITEM );

		self::assertStringContainsString( '<section class="jmc-section jmc-trust-credentials">', $html );
		self::assertStringContainsString( '<div class="jmc-section__column">', $html );
		self::assertStringContainsString( self::ITEM, $html );
	}

	/**
	 * Supported palettes add their class.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param string $palette Palette attribute value.
	 * @param string $classes Expected section classes.
	 */
	public function test_section_applies_palette( string $palette, string $classes ): void {
		$html = $this->render_section( [ 'palette' => $palette ], self::ITEM );

		self::assertStringContainsString( "<section class=\"{$classes}\">", $html );
	}

	/**
	 * Palette values and the section classes they produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function palette_provider(): array {
		return [
			'default'   => [ 'default', 'jmc-section jmc-trust-credentials' ],
			'secondary' => [ 'secondary', 'jmc-section jmc-trust-credentials jmc-palette--secondary' ],
			'inverse'   => [ 'inverse', 'jmc-section jmc-trust-credentials jmc-palette--inverse' ],
		];
	}

	/**
	 * Unsupported or malformed palettes fall back to the inherited default.
	 *
	 * @dataProvider malformed_palette_provider
	 *
	 * @param mixed $palette Palette attribute value.
	 */
	public function test_section_ignores_malformed_palette( mixed $palette ): void {
		$html = $this->render_section( [ 'palette' => $palette ], self::ITEM );

		self::assertStringContainsString( '<section class="jmc-section jmc-trust-credentials">', $html );
		self::assertStringNotContainsString( 'jmc-palette--', $html );
	}

	/**
	 * Palette values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_palette_provider(): array {
		return [
			'unknown'    => [ 'primary' ],
			'wrong case' => [ 'Inverse' ],
			'array'      => [ [ 'inverse' ] ],
			'null'       => [ null ],
		];
	}

	/**
	 * The section's anchor and custom class reach the section element.
	 */
	public function test_section_applies_anchor_and_class_name(): void {
		$html = $this->render_section(
			[
				'anchor'    => 'credentials',
				'className' => 'is-compact',
			],
			self::ITEM
		);

		self::assertStringContainsString( '<section id="credentials" class="jmc-section jmc-trust-credentials is-compact">', $html );
	}

	/**
	 * A section with no credentials is omitted rather than left as an empty band.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param string $content Rendered inner-block content.
	 */
	public function test_omits_empty_section( string $content ): void {
		self::assertSame( '', trim( $this->render_section( [ 'palette' => 'inverse' ], $content ) ) );
	}

	/**
	 * Inner content that leaves the list or section empty.
	 *
	 * @return array<string, array{string}>
	 */
	public function empty_content_provider(): array {
		return [
			'nothing'    => [ '' ],
			'whitespace' => [ "\n\t\n" ],
		];
	}

	/**
	 * Execute the real credential list render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string               $content    Rendered credential items.
	 *
	 * @return string Rendered markup.
	 */
	private function render_list( array $attributes, string $content ): string {
		return $this->render( 'content/credential-list', $attributes, $content );
	}

	/**
	 * Execute the real trust and credentials section render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string               $content    Rendered inner-block content.
	 *
	 * @return string Rendered markup.
	 */
	private function render_section( array $attributes, string $content ): string {
		return $this->render( 'layouts/trust-credentials', $attributes, $content );
	}

	/**
	 * Execute a real block render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param string               $block      Block directory relative to `src/blocks`.
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string               $content    Rendered inner-block content.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render( string $block, array $attributes, string $content ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . "/src/blocks/{$block}/render.php";

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
