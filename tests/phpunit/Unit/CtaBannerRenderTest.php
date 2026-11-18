<?php
/**
 * CTA banner render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the CTA banner block render callback.
 */
final class CtaBannerRenderTest extends TestCase {

	/**
	 * Omitted attributes render the inverse, side-by-side defaults.
	 */
	public function test_defaults_to_inverse_columns(): void {
		$html = $this->render_banner( [] );

		self::assertStringContainsString( 'class="jmc-section jmc-cta jmc-palette--inverse"', $html );
		self::assertStringContainsString( 'class="jmc-section__container jmc-column-right"', $html );
		self::assertStringNotContainsString( 'jmc-cta--stacked', $html );
	}

	/**
	 * The columns layout honours the selected column order.
	 */
	public function test_columns_layout_applies_order(): void {
		$html = $this->render_banner(
			[
				'layout' => 'columns',
				'order'  => 'left',
			]
		);

		self::assertStringContainsString( 'class="jmc-section__container jmc-column-left"', $html );
	}

	/**
	 * The contact layout adds its modifier, sits side by side and honours the selected column order.
	 */
	public function test_contact_layout_applies_order(): void {
		$html = $this->render_banner(
			[
				'layout' => 'contact',
				'order'  => 'left',
			]
		);

		self::assertStringContainsString( 'class="jmc-section jmc-cta jmc-cta--contact jmc-palette--inverse"', $html );
		self::assertStringContainsString( 'class="jmc-section__container jmc-column-left"', $html );
	}

	/**
	 * The stacked layout adds its modifier and ignores column order.
	 */
	public function test_stacked_layout_ignores_order(): void {
		$html = $this->render_banner(
			[
				'layout'  => 'stacked',
				'order'   => 'left',
				'palette' => 'secondary',
			]
		);

		self::assertStringContainsString( 'class="jmc-section jmc-cta jmc-cta--stacked jmc-palette--secondary"', $html );
		self::assertStringContainsString( 'class="jmc-section__container"', $html );
		self::assertStringNotContainsString( 'jmc-column-', $html );
	}

	/**
	 * Unsupported or malformed layout values fall back to columns.
	 *
	 * @dataProvider malformed_layout_provider
	 *
	 * @param mixed $layout Layout attribute value.
	 */
	public function test_malformed_layout_falls_back_to_columns( mixed $layout ): void {
		$html = $this->render_banner( [ 'layout' => $layout ] );

		self::assertStringNotContainsString( 'jmc-cta--stacked', $html );
		self::assertStringContainsString( 'jmc-column-right', $html );
	}

	/**
	 * Layout values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_layout_provider(): array {
		return [
			'unknown'    => [ 'grid' ],
			'wrong case' => [ 'Stacked' ],
			'array'      => [ [ 'stacked' ] ],
			'null'       => [ null ],
		];
	}

	/**
	 * Columns left empty by omitted headings, copy and buttons are dropped.
	 */
	public function test_drops_empty_columns(): void {
		$html = $this->render_banner(
			[],
			$this->render_column( '' ) . $this->render_column( '<a class="jmc-button" href="/contact/">Contact</a>' )
		);

		self::assertSame( 1, substr_count( $html, 'jmc-section__column' ) );
		self::assertStringContainsString( 'Contact', $html );
	}

	/**
	 * A banner whose columns are all empty renders nothing.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param string $content Rendered inner-block content.
	 */
	public function test_omits_empty_banner( string $content ): void {
		self::assertSame( '', trim( $this->render_banner( [ 'layout' => 'stacked' ], $content ) ) );
	}

	/**
	 * Inner content that leaves the banner empty.
	 *
	 * @return array<string, array{string}>
	 */
	public function empty_content_provider(): array {
		return [
			'no columns'        => [ '' ],
			'whitespace'        => [ "\n\t\n" ],
			'one empty column'  => [ $this->render_column( '' ) ],
			'two empty columns' => [ $this->render_column( "\n" ) . "\n" . $this->render_column( '' ) ],
		];
	}

	/**
	 * Render a real `jmc/column` wrapper around the given content.
	 *
	 * @param string $content Rendered inner-block content for the column.
	 *
	 * @return string Rendered column markup.
	 */
	private function render_column( string $content ): string {
		ob_start();
		require dirname( __DIR__, 3 ) . '/src/blocks/content/column/render.php';

		return (string) ob_get_clean();
	}

	/**
	 * Execute the real CTA banner render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string|null          $content    Rendered inner-block content; defaults to one heading column.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_banner( array $attributes, ?string $content = null ): string {
		$content ??= $this->render_column( '<h2 class="jmc-heading">Have a project in mind?</h2>' );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/layouts/cta-banner/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
