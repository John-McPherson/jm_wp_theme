<?php
/**
 * Footer container render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the footer container block render callback.
 */
final class FooterRenderTest extends TestCase {

	/**
	 * Each palette maps to its class, and malformed values fall back to default.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $expected   Expected wrapper class attribute.
	 */
	public function test_palette_classes( array $attributes, string $expected ): void {
		$html = $this->render_footer( $attributes, '<p>Inner</p>' );

		self::assertStringContainsString( 'class="' . $expected . '"', $html );
	}

	/**
	 * Palette attribute values and the wrapper class each should produce.
	 *
	 * @return array<string,array{0:array<string,mixed>,1:string}>
	 */
	public function palette_provider(): array {
		return [
			'omitted'    => [ [], 'jmc-footer-inner' ],
			'default'    => [ [ 'palette' => 'default' ], 'jmc-footer-inner' ],
			'secondary'  => [ [ 'palette' => 'secondary' ], 'jmc-footer-inner jmc-palette--secondary' ],
			'inverse'    => [ [ 'palette' => 'inverse' ], 'jmc-footer-inner jmc-palette--inverse' ],
			'unknown'    => [ [ 'palette' => 'neon' ], 'jmc-footer-inner' ],
			'wrong type' => [ [ 'palette' => [ 'inverse' ] ], 'jmc-footer-inner' ],
		];
	}

	/**
	 * Inner content is wrapped in the container, and custom classes are kept.
	 */
	public function test_wraps_content_in_container(): void {
		$html = $this->render_footer(
			[
				'palette'   => 'inverse',
				'className' => 'is-custom',
			],
			'<p>Inner</p>'
		);

		self::assertStringContainsString( 'class="jmc-footer-inner jmc-palette--inverse is-custom"', $html );
		self::assertMatchesRegularExpression(
			'/<div class="jmc-footer-inner__container">\s*<p>Inner<\/p>\s*<\/div>/',
			$html
		);
	}

	/**
	 * Nothing is rendered when the inner blocks produce no markup.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param mixed $content Inner-block content.
	 */
	public function test_renders_nothing_without_content( mixed $content ): void {
		self::assertSame( '', trim( $this->render_footer( [ 'palette' => 'inverse' ], $content ) ) );
	}

	/**
	 * Inner content values that contain no markup.
	 *
	 * @return array<string,array{0:mixed}>
	 */
	public function empty_content_provider(): array {
		return [
			'empty'      => [ '' ],
			'whitespace' => [ "\n\t " ],
			'null'       => [ null ],
		];
	}

	/**
	 * Execute the real footer render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string,mixed> $attributes Block attributes to render.
	 * @param mixed               $content    Rendered inner-block content.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_footer( array $attributes, mixed $content ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/footer/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
