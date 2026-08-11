<?php
/**
 * Hero render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test Hero block palette rendering.
 */
final class HeroRenderTest extends TestCase {

	/**
	 * Verify supported and malformed palette values.
	 *
	 * PHP notices and warnings are converted into exceptions so an undefined
	 * palette-class key causes the test to fail.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param array<string, mixed> $attributes     Block attributes to render.
	 * @param string|null          $expected_class Expected palette class, or null.
	 *
	 * @return void
	 */
	public function test_palette_rendering(
		array $attributes,
		?string $expected_class
	): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function (
				int $severity,
				string $message,
				string $file,
				int $line
			): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException(
					$message,               // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					0,
					$severity,              // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					$file,              // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					$line               // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				);
			}
		);

		try {
			$html = $this->render_hero( $attributes );
		} finally {
			restore_error_handler();
		}

		self::assertStringContainsString(
			'jmc-hero',
			$html
		);

		if ( null === $expected_class ) {
			self::assertStringNotContainsString(
				'jmc-palette--',
				$html
			);

			return;
		}

		self::assertStringContainsString(
			$expected_class,
			$html
		);
	}

	/**
	 * Provide supported and malformed palette test cases.
	 *
	 * @return array<string, array<mixed>> Palette test cases.
	 */
	public static function palette_provider(): array {
		return [
			'omitted palette'   => [
				[],
				null,
			],
			'declared default'  => [
				[ 'palette' => 'default' ],
				null,
			],
			'secondary palette' => [
				[ 'palette' => 'secondary' ],
				'jmc-palette--secondary',
			],
			'inverse palette'   => [
				[ 'palette' => 'inverse' ],
				'jmc-palette--inverse',
			],
			'unexpected string' => [
				[ 'palette' => 'unknown' ],
				null,
			],
			'unexpected type'   => [
				[ 'palette' => [ 'invalid' ] ],
				null,
			],
		];
	}

	/**
	 * Execute the real Hero render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered Hero markup.
	 */
	private function render_hero( array $attributes ): string {
		$content = '<p>Hero content</p>';

		ob_start();

		try {
			require dirname( __DIR__, 3 )
				. '/src/blocks/layouts/hero/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		}
	}
}
