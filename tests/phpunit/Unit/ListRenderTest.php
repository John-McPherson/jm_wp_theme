<?php
/**
 * List block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the list block render callback.
 */
final class ListRenderTest extends TestCase {

	/**
	 * Rendered list items used as inner content.
	 */
	private const ITEMS = '<li class="jmc-list__item"><span class="jmc-list__text">Free quotes</span></li>';

	/**
	 * A list whose items all rendered nothing renders nothing.
	 *
	 * @dataProvider empty_content_provider
	 *
	 * @param string $content Rendered inner content.
	 */
	public function test_omits_empty_list( string $content ): void {
		self::assertSame( '', trim( $this->render_list( [ 'listStyle' => 'number' ], $content ) ) );
	}

	/**
	 * Inner content with no items.
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
	 * Each style renders the right list element, with role="list" only where markers are removed.
	 *
	 * @dataProvider style_provider
	 *
	 * @param string $list_style Saved list style.
	 * @param string $opening    Expected opening tag.
	 * @param string $closing    Expected closing tag.
	 */
	public function test_renders_each_style( string $list_style, string $opening, string $closing ): void {
		$html = $this->render_list( [ 'listStyle' => $list_style ] );

		self::assertStringStartsWith( $opening, trim( $html ) );
		self::assertStringEndsWith( $closing, trim( $html ) );
		self::assertStringContainsString( self::ITEMS, $html );
	}

	/**
	 * Supported list styles and their markup.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function style_provider(): array {
		return [
			'icon'   => [ 'icon', '<ul class="jmc-list jmc-list--icon" role="list">', '</ul>' ],
			'bullet' => [ 'bullet', '<ul class="jmc-list jmc-list--bullet">', '</ul>' ],
			'number' => [ 'number', '<ol class="jmc-list jmc-list--number">', '</ol>' ],
		];
	}

	/**
	 * Missing or invalid styles fall back to the icon list.
	 *
	 * @dataProvider invalid_style_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_invalid_style_falls_back_to_icon_list( array $attributes ): void {
		$html = $this->render_list( $attributes );

		self::assertStringStartsWith( '<ul class="jmc-list jmc-list--icon" role="list">', trim( $html ) );
		self::assertStringNotContainsString( '<ol', $html );
	}

	/**
	 * Attributes without a supported list style.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function invalid_style_provider(): array {
		return [
			'omitted'   => [ [] ],
			'null'      => [ [ 'listStyle' => null ] ],
			'unknown'   => [ [ 'listStyle' => 'roman' ] ],
			'uppercase' => [ [ 'listStyle' => 'NUMBER' ] ],
			'array'     => [ [ 'listStyle' => [ 'number' ] ] ],
			'markup'    => [ [ 'listStyle' => 'number" onclick="x' ] ],
		];
	}

	/**
	 * The anchor and custom class reach the list element, escaped.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_list(
			[
				'listStyle' => 'bullet',
				'anchor'    => 'why-us',
				'className' => 'is-compact "><script>',
			]
		);

		self::assertStringStartsWith( '<ul id="why-us" class="jmc-list jmc-list--bullet is-compact', trim( $html ) );
		self::assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * Render the list block template and return its output.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param string               $content    Rendered inner content.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_list( array $attributes, string $content = self::ITEMS ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/list/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
