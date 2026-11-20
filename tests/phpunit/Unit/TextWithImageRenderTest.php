<?php
/**
 * Text with image block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the text with image block render callback.
 */
final class TextWithImageRenderTest extends TestCase {

	private const CONTENT = '<h2 class="jmc-heading">Solutions built around you</h2>';

	/**
	 * Register the Media Library image the tests use.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_attachments'] = [
			42 => [
				'alt'   => 'An electrician fitting a consumer unit',
				'title' => 'consumer-unit',
			],
		];
	}

	/**
	 * Remove the Media Library fixtures.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_attachments'] );
	}

	/**
	 * Omitted attributes render the default palette with the image on the right and no image.
	 */
	public function test_renders_defaults(): void {
		$html = $this->render_section( [] );

		self::assertSame( '<section class="jmc-section jmc-text-with-image jmc-text-with-image--image-right">', $this->section_tag( $html ) );
		self::assertStringContainsString( '<div class="jmc-section__container">', $html );
		self::assertSame( 2, substr_count( $html, '<div class="jmc-section__column">' ) );
		self::assertStringNotContainsString( 'jmc-palette--', $html );
		self::assertStringNotContainsString( '<img', $html );
		self::assertStringNotContainsString( 'jmc-text-with-image__image', $html );
	}

	/**
	 * The rendered inner blocks sit in the first column, before the image column.
	 */
	public function test_renders_content_in_first_column(): void {
		$html = $this->render_section( [ 'imageId' => 42 ] );

		self::assertMatchesRegularExpression(
			'#<div class="jmc-section__column">\s*' . preg_quote( self::CONTENT, '#' ) . '\s*</div>\s*<div class="jmc-section__column">\s*<div class="jmc-image jmc-text-with-image__image">#',
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
		$html = $this->render_section( [ 'palette' => $palette ] );

		self::assertSame( "<section class=\"{$classes}\">", $this->section_tag( $html ) );
	}

	/**
	 * Palette values and the section classes they produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function palette_provider(): array {
		return [
			'default'   => [ 'default', 'jmc-section jmc-text-with-image jmc-text-with-image--image-right' ],
			'secondary' => [ 'secondary', 'jmc-section jmc-text-with-image jmc-palette--secondary jmc-text-with-image--image-right' ],
			'inverse'   => [ 'inverse', 'jmc-section jmc-text-with-image jmc-palette--inverse jmc-text-with-image--image-right' ],
		];
	}

	/**
	 * Unsupported or malformed palettes fall back to the default palette.
	 *
	 * @dataProvider malformed_value_provider
	 *
	 * @param mixed $palette Palette attribute value.
	 */
	public function test_ignores_malformed_palette( mixed $palette ): void {
		$html = $this->render_section( [ 'palette' => $palette ] );

		self::assertSame( '<section class="jmc-section jmc-text-with-image jmc-text-with-image--image-right">', $this->section_tag( $html ) );
		self::assertStringNotContainsString( 'jmc-palette--', $html );
	}

	/**
	 * Supported orders set which side the image sits on.
	 *
	 * @dataProvider order_provider
	 *
	 * @param string $order    Order attribute value.
	 * @param string $modifier Expected order class.
	 */
	public function test_applies_order( string $order, string $modifier ): void {
		$html = $this->render_section( [ 'order' => $order ] );

		self::assertSame( "<section class=\"jmc-section jmc-text-with-image {$modifier}\">", $this->section_tag( $html ) );
	}

	/**
	 * Order values and the classes they produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function order_provider(): array {
		return [
			'left'  => [ 'left', 'jmc-text-with-image--image-left' ],
			'right' => [ 'right', 'jmc-text-with-image--image-right' ],
		];
	}

	/**
	 * Unsupported or malformed orders fall back to the image on the right.
	 *
	 * @dataProvider malformed_value_provider
	 *
	 * @param mixed $order Order attribute value.
	 */
	public function test_ignores_malformed_order( mixed $order ): void {
		$html = $this->render_section( [ 'order' => $order ] );

		self::assertStringContainsString( 'jmc-text-with-image--image-right', $html );
		self::assertStringNotContainsString( 'jmc-text-with-image--image-left', $html );
	}

	/**
	 * Palette and order values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_value_provider(): array {
		return [
			'unknown'    => [ 'primary' ],
			'wrong case' => [ 'Left' ],
			'array'      => [ [ 'left' ] ],
			'number'     => [ 1 ],
			'null'       => [ null ],
		];
	}

	/**
	 * A Media Library image renders full size, lazily, with its alt text.
	 *
	 * @dataProvider image_id_provider
	 *
	 * @param int|string $image_id Image ID attribute value.
	 */
	public function test_renders_image( int|string $image_id ): void {
		$html = $this->render_section( [ 'imageId' => $image_id ] );

		self::assertMatchesRegularExpression(
			'#<div class="jmc-image jmc-text-with-image__image">\s*<img src="https://example.test/uploads/42-full.png" alt="An electrician fitting a consumer unit" class="jmc-text-with-image__media" loading="lazy" />\s*</div>#',
			$html
		);
	}

	/**
	 * Image IDs that identify the registered image.
	 *
	 * @return array<string, array{int|string}>
	 */
	public function image_id_provider(): array {
		return [
			'integer'        => [ 42 ],
			'numeric string' => [ '42' ],
		];
	}

	/**
	 * Missing, malformed and deleted images leave the image column empty.
	 *
	 * @dataProvider missing_image_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_omits_missing_image( array $attributes ): void {
		$html = $this->render_section( $attributes );

		self::assertStringNotContainsString( '<img', $html );
		self::assertStringNotContainsString( 'jmc-text-with-image__image', $html );
		self::assertSame( 2, substr_count( $html, '<div class="jmc-section__column">' ) );
		self::assertStringContainsString( self::CONTENT, $html );
	}

	/**
	 * Attributes without a usable image.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function missing_image_provider(): array {
		return [
			'omitted'        => [ [] ],
			'zero'           => [ [ 'imageId' => 0 ] ],
			'negative'       => [ [ 'imageId' => -42 ] ],
			'deleted'        => [ [ 'imageId' => 99 ] ],
			'non-numeric'    => [ [ 'imageId' => 'image-42' ] ],
			'array'          => [ [ 'imageId' => [ 42 ] ] ],
			'null'           => [ [ 'imageId' => null ] ],
			'only image URL' => [ [ 'imageUrl' => 'https://example.test/uploads/42-full.png' ] ],
		];
	}

	/**
	 * The anchor and custom classes reach the section.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_section(
			[
				'palette'   => 'secondary',
				'order'     => 'left',
				'anchor'    => 'About us',
				'className' => ' is-compact  is-wide ',
			]
		);

		self::assertSame(
			'<section class="jmc-section jmc-text-with-image jmc-palette--secondary jmc-text-with-image--image-left is-compact is-wide" id="about-us">',
			$this->section_tag( $html )
		);
	}

	/**
	 * An empty anchor adds no ID.
	 */
	public function test_ignores_empty_anchor(): void {
		$html = $this->render_section( [ 'anchor' => '' ] );

		self::assertStringNotContainsString( ' id=', $html );
	}

	/**
	 * The anchor and custom classes are sanitised.
	 */
	public function test_sanitises_anchor_and_class_name(): void {
		$html = $this->render_section(
			[
				'anchor'    => '"><em>Our</em> work.',
				'className' => 'is-compact" onclick="alert(1)',
			]
		);

		self::assertSame(
			'<section class="jmc-section jmc-text-with-image jmc-text-with-image--image-right is-compact onclickalert1" id="our-work">',
			$this->section_tag( $html )
		);
		self::assertStringNotContainsString( '<script>', $html );
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
	 * Execute the real text with image render callback.
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
	private function render_section( array $attributes, string $content = self::CONTENT ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/layouts/text-with-image/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
