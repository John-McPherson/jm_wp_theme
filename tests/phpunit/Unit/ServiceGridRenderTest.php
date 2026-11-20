<?php
/**
 * Service grid block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the service grid block render callback.
 */
final class ServiceGridRenderTest extends TestCase {

	private const CONTENT = '<div class="jmc-section__column"><h2 class="jmc-heading">Our services</h2></div>'
		. '<div class="jmc-section__column"><ul class="jmc-service-list"></ul></div>';

	/**
	 * Omitted attributes render the default palette around the columns.
	 */
	public function test_renders_defaults(): void {
		$html = $this->render_grid( [] );

		self::assertSame( '<section class="jmc-section jmc-service-grid">', $this->section_tag( $html ) );
		self::assertStringNotContainsString( 'jmc-palette--', $html );
		self::assertStringNotContainsString( ' id=', $html );
	}

	/**
	 * The rendered columns sit inside the section container, unchanged and in order.
	 */
	public function test_wraps_content_in_container(): void {
		$html = $this->render_grid( [] );

		self::assertMatchesRegularExpression(
			'#<div\s+class="jmc-section__container">\s*' . preg_quote( self::CONTENT, '#' ) . '\s*</div>\s*</section>#',
			$html
		);
	}

	/**
	 * Supported palettes add their class.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param string $palette Palette attribute value.
	 * @param string $classes Expected section classes.
	 */
	public function test_applies_palette( string $palette, string $classes ): void {
		$html = $this->render_grid( [ 'palette' => $palette ] );

		self::assertSame( "<section class=\"{$classes}\">", $this->section_tag( $html ) );
	}

	/**
	 * Palette values and the section classes they produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function palette_provider(): array {
		return [
			'default'   => [ 'default', 'jmc-section jmc-service-grid' ],
			'secondary' => [ 'secondary', 'jmc-section jmc-service-grid jmc-palette--secondary' ],
			'inverse'   => [ 'inverse', 'jmc-section jmc-service-grid jmc-palette--inverse' ],
		];
	}

	/**
	 * Unsupported or malformed palettes fall back to the default palette.
	 *
	 * @dataProvider malformed_palette_provider
	 *
	 * @param mixed $palette Palette attribute value.
	 */
	public function test_ignores_malformed_palette( mixed $palette ): void {
		$html = $this->render_grid( [ 'palette' => $palette ] );

		self::assertSame( '<section class="jmc-section jmc-service-grid">', $this->section_tag( $html ) );
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
			'number'     => [ 1 ],
			'null'       => [ null ],
		];
	}

	/**
	 * The anchor and custom classes reach the section.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_grid(
			[
				'palette'   => 'inverse',
				'anchor'    => 'Our Services',
				'className' => ' is-compact  is-wide ',
			]
		);

		self::assertSame(
			'<section class="jmc-section jmc-service-grid jmc-palette--inverse is-compact is-wide" id="our-services">',
			$this->section_tag( $html )
		);
	}

	/**
	 * An empty anchor adds no ID.
	 */
	public function test_ignores_empty_anchor(): void {
		$html = $this->render_grid( [ 'anchor' => '' ] );

		self::assertStringNotContainsString( ' id=', $html );
	}

	/**
	 * The anchor and custom classes are sanitised.
	 */
	public function test_sanitises_anchor_and_class_name(): void {
		$html = $this->render_grid(
			[
				'anchor'    => '"><em>Services</em> & repairs',
				'className' => 'is-compact" onclick="alert(1)',
			]
		);

		self::assertSame(
			'<section class="jmc-section jmc-service-grid is-compact onclickalert1" id="services-repairs">',
			$this->section_tag( $html )
		);
		self::assertStringNotContainsString( '<em>', $html );
	}

	/**
	 * Return the section's opening tag with its whitespace normalised.
	 *
	 * @param string $html Rendered markup.
	 *
	 * @return string The opening `<section>` tag, or an empty string.
	 */
	private function section_tag( string $html ): string {
		return 1 === preg_match( '#<section\s+([^>]*)>#', $html, $matches )
			? "<section {$matches[1]}>"
			: '';
	}

	/**
	 * Execute the real service grid render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string               $content    Rendered inner-block content.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_grid( array $attributes, string $content = self::CONTENT ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/layouts/service-grid/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
