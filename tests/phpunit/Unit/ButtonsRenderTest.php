<?php
/**
 * Buttons group render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the buttons group block render callback.
 */
final class ButtonsRenderTest extends TestCase {

	/**
	 * Rendered buttons are wrapped in the group, in order.
	 */
	public function test_wraps_rendered_buttons(): void {
		$html = $this->render_buttons(
			'<a class="jmc-button" href="/contact/">Get a quote</a><a class="jmc-button" href="tel:01234567890">Call us</a>'
		);

		self::assertStringContainsString( '<div class="jmc-buttons">', $html );
		self::assertMatchesRegularExpression( '/Get a quote.*Call us/s', $html );
	}

	/**
	 * A group whose buttons all omitted themselves renders nothing.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param string $content Rendered inner-block content.
	 */
	public function test_omits_empty_group( string $content ): void {
		self::assertSame( '', trim( $this->render_buttons( $content ) ) );
	}

	/**
	 * Inner content that counts as empty.
	 *
	 * @return array<string, array{string}>
	 */
	public function empty_content_provider(): array {
		return [
			'empty'      => [ '' ],
			'whitespace' => [ "\n\t  \n" ],
		];
	}

	/**
	 * Execute the real buttons group render callback.
	 *
	 * @param string $content Rendered inner-block content.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_buttons( string $content ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/buttons/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
