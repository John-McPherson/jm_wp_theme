<?php
/**
 * Image component regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the image component's size and alternative-text arguments.
 */
final class ImageComponentTest extends TestCase {

	/**
	 * Register the Media Library image the tests use.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_attachments'] = [
			7 => [ 'alt' => 'Media Library text' ],
		];
	}

	/**
	 * Remove the Media Library fixtures.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_attachments'] );
	}

	/**
	 * Without size or alt arguments the component keeps its previous output.
	 */
	public function test_defaults_to_large_image_and_media_library_alt(): void {
		$html = $this->render_image( [ 'image_id' => 7 ] );

		self::assertStringContainsString( '7-large.png', $html );
		self::assertStringContainsString( 'alt="Media Library text"', $html );
		self::assertStringContainsString( 'sizes="(max-width: 768px) 100vw, 50vw"', $html );
		self::assertStringContainsString( 'loading="lazy"', $html );
	}

	/**
	 * Size and sizes arguments are passed through.
	 */
	public function test_applies_size_arguments(): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'size'     => 'medium',
				'sizes'    => '10rem',
			]
		);

		self::assertStringContainsString( '7-medium.png', $html );
		self::assertStringContainsString( 'sizes="10rem"', $html );
	}

	/**
	 * An explicit alt replaces the Media Library text, trimmed.
	 */
	public function test_explicit_alt_replaces_media_library_text(): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'alt'      => '  NICEIC  ',
			]
		);

		self::assertStringContainsString( 'alt="NICEIC"', $html );
	}

	/**
	 * An explicit empty alt marks the image as decorative.
	 */
	public function test_empty_alt_marks_image_decorative(): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'alt'      => '',
			]
		);

		self::assertStringContainsString( 'alt=""', $html );
	}

	/**
	 * Malformed arguments fall back to the defaults.
	 */
	public function test_malformed_arguments_fall_back(): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'size'     => [ 'medium' ],
				'alt'      => [ 'NICEIC' ],
			]
		);

		self::assertStringContainsString( '7-large.png', $html );
		self::assertStringContainsString( 'alt="Media Library text"', $html );
	}

	/**
	 * Registered sizes and `full` are passed through.
	 *
	 * @dataProvider registered_size_provider
	 *
	 * @param string $size Image size.
	 */
	public function test_accepts_registered_sizes( string $size ): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'size'     => $size,
			]
		);

		self::assertStringContainsString( "7-{$size}.png", $html );
	}

	/**
	 * Sizes the component accepts.
	 *
	 * @return array<string, array{string}>
	 */
	public function registered_size_provider(): array {
		return [
			'thumbnail'    => [ 'thumbnail' ],
			'medium_large' => [ 'medium_large' ],
			'full'         => [ 'full' ],
		];
	}

	/**
	 * Unregistered sizes fall back to `large`.
	 *
	 * @dataProvider unregistered_size_provider
	 *
	 * @param mixed $size Image size.
	 */
	public function test_unregistered_size_falls_back_to_large( mixed $size ): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'size'     => $size,
			]
		);

		self::assertStringContainsString( '7-large.png', $html );
	}

	/**
	 * Sizes that are not registered.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function unregistered_size_provider(): array {
		return [
			'unknown'    => [ 'hero-banner' ],
			'empty'      => [ '' ],
			'wrong case' => [ 'Medium' ],
			'integer'    => [ 300 ],
		];
	}

	/**
	 * Supported loading values are output.
	 *
	 * @dataProvider loading_provider
	 *
	 * @param string $loading Loading strategy.
	 */
	public function test_applies_loading( string $loading ): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'loading'  => $loading,
			]
		);

		self::assertStringContainsString( "loading=\"{$loading}\"", $html );
	}

	/**
	 * Supported loading strategies.
	 *
	 * @return array<string, array{string}>
	 */
	public function loading_provider(): array {
		return [
			'lazy'  => [ 'lazy' ],
			'eager' => [ 'eager' ],
		];
	}

	/**
	 * A `false` loading value omits the attribute.
	 */
	public function test_false_loading_omits_attribute(): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'loading'  => false,
			]
		);

		self::assertStringNotContainsString( 'loading=', $html );
	}

	/**
	 * Unsupported loading values fall back to lazy loading.
	 *
	 * @dataProvider invalid_loading_provider
	 *
	 * @param mixed $loading Loading strategy.
	 */
	public function test_invalid_loading_falls_back_to_lazy( mixed $loading ): void {
		$html = $this->render_image(
			[
				'image_id' => 7,
				'loading'  => $loading,
			]
		);

		self::assertStringContainsString( 'loading="lazy"', $html );
	}

	/**
	 * Unsupported loading values.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function invalid_loading_provider(): array {
		return [
			'unknown' => [ 'auto' ],
			'empty'   => [ '' ],
			'true'    => [ true ],
			'null'    => [ null ],
			'array'   => [ [ 'eager' ] ],
		];
	}

	/**
	 * Missing or unknown images render nothing.
	 *
	 * @dataProvider missing_image_provider
	 *
	 * @param array<string, mixed> $args Component arguments.
	 */
	public function test_omits_missing_image( array $args ): void {
		self::assertSame( '', trim( $this->render_image( $args ) ) );
	}

	/**
	 * Arguments that leave the component without an image.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function missing_image_provider(): array {
		return [
			'omitted' => [ [] ],
			'zero'    => [ [ 'image_id' => 0 ] ],
			'unknown' => [ [ 'image_id' => 99 ] ],
		];
	}

	/**
	 * Render the real image component.
	 *
	 * @param array<string, mixed> $args Component arguments.
	 *
	 * @return string Rendered markup.
	 */
	private function render_image( array $args ): string {
		ob_start();
		jmc_component( name: 'image', args: $args );

		return (string) ob_get_clean();
	}
}
