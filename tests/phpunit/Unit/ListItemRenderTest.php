<?php
/**
 * List item block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the list item block render callback.
 */
final class ListItemRenderTest extends TestCase {

	/**
	 * An item with no visible text renders nothing.
	 *
	 * @dataProvider empty_text_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_omits_empty_item( array $attributes ): void {
		self::assertSame( '', trim( $this->render_item( $attributes ) ) );
	}

	/**
	 * Attributes that leave an item without visible text.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function empty_text_provider(): array {
		return [
			'omitted'          => [ [] ],
			'empty'            => [ [ 'text' => '' ] ],
			'whitespace'       => [ [ 'text' => "  \n " ] ],
			'empty formatting' => [ [ 'text' => '<strong> </strong><em></em>' ] ],
			'only a script'    => [ [ 'text' => '<script>alert(1)</script>' ] ],
			'null'             => [ [ 'text' => null ] ],
			'array'            => [ [ 'text' => [ 'Free quotes' ] ] ],
		];
	}

	/**
	 * Icon lists put a decorative icon before the text.
	 */
	public function test_renders_decorative_icon_in_icon_list(): void {
		$html = $this->render_item(
			[ 'text' => 'Free quotes' ],
			[
				'jmc/listStyle' => 'icon',
				'jmc/listIcon'  => 'plus',
			]
		);

		self::assertMatchesRegularExpression(
			'#^<li class="jmc-list__item">\s*<svg class="jmc-list__icon" data-icon="jmc/plus" aria-hidden="true" focusable="false"></svg>\s*<span class="jmc-list__text">Free quotes</span>\s*</li>$#',
			trim( $html )
		);
	}

	/**
	 * Each supported icon reaches the icon registry.
	 *
	 * @dataProvider icon_provider
	 *
	 * @param string $icon Saved icon.
	 */
	public function test_renders_each_icon( string $icon ): void {
		$html = $this->render_item( [ 'text' => 'Free quotes' ], [ 'jmc/listIcon' => $icon ] );

		self::assertStringContainsString( "data-icon=\"jmc/{$icon}\"", $html );
	}

	/**
	 * Supported icons.
	 *
	 * @return array<string, array{string}>
	 */
	public function icon_provider(): array {
		return [
			'check'        => [ 'check' ],
			'circle-check' => [ 'circle-check' ],
			'shield-check' => [ 'shield-check' ],
			'plus'         => [ 'plus' ],
			'zap'          => [ 'zap' ],
			'arrow-right'  => [ 'arrow-right' ],
		];
	}

	/**
	 * Missing or invalid list context falls back to an icon list with a tick.
	 *
	 * @dataProvider invalid_context_provider
	 *
	 * @param array<string, mixed> $context Block context.
	 */
	public function test_invalid_context_falls_back_to_tick( array $context ): void {
		$html = $this->render_item( [ 'text' => 'Free quotes' ], $context );

		self::assertSame( 1, substr_count( $html, '<svg' ) );
		self::assertStringContainsString( 'data-icon="jmc/check"', $html );
	}

	/**
	 * Context without a supported style or icon.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function invalid_context_provider(): array {
		return [
			'omitted'         => [ [] ],
			'unknown style'   => [ [ 'jmc/listStyle' => 'roman' ] ],
			'unknown icon'    => [ [ 'jmc/listIcon' => 'skull' ] ],
			'unregistered'    => [ [ 'jmc/listIcon' => 'clock' ] ],
			'path traversal'  => [ [ 'jmc/listIcon' => '../whatsapp' ] ],
			'array icon'      => [ [ 'jmc/listIcon' => [ 'plus' ] ] ],
			'null everything' => [
				[
					'jmc/listStyle' => null,
					'jmc/listIcon'  => null,
				],
			],
		];
	}

	/**
	 * Bulleted and numbered lists use the browser's markers, not icons.
	 *
	 * @dataProvider marker_style_provider
	 *
	 * @param string $list_style List style from context.
	 */
	public function test_marker_styles_render_no_icon( string $list_style ): void {
		$html = $this->render_item(
			[ 'text' => 'Free quotes' ],
			[
				'jmc/listStyle' => $list_style,
				'jmc/listIcon'  => 'plus',
			]
		);

		self::assertStringNotContainsString( '<svg', $html );
		self::assertStringContainsString( '<span class="jmc-list__text">Free quotes</span>', $html );
	}

	/**
	 * List styles that use browser markers.
	 *
	 * @return array<string, array{string}>
	 */
	public function marker_style_provider(): array {
		return [
			'bullet' => [ 'bullet' ],
			'number' => [ 'number' ],
		];
	}

	/**
	 * Bold, italic and links survive, including nested formatting.
	 */
	public function test_keeps_inline_formatting(): void {
		$text = '<strong>Fully</strong> <em>insured</em>, see <a href="https://example.com/cover" target="_blank" rel="noreferrer noopener"><strong>our cover</strong></a>';

		$html = $this->render_item( [ 'text' => $text ] );

		self::assertStringContainsString( "<span class=\"jmc-list__text\">{$text}</span>", $html );
	}

	/**
	 * Anything beyond inline formatting is stripped, keeping the text.
	 */
	public function test_strips_unsafe_and_block_markup(): void {
		$html = $this->render_item(
			[
				'text' => '<h2 class="x">Safe</h2> <img src="x" onerror="alert(1)"><a href="javascript:alert(1)" onclick="alert(2)" style="color:red">link</a><script>alert(3)</script>',
			]
		);

		self::assertStringContainsString( 'Safe', $html );
		self::assertStringContainsString( '<a>link</a>', $html );

		foreach ( [ '<h2', '<img', '<script', 'javascript:', 'onclick', 'onerror', 'style=' ] as $unsafe ) {
			self::assertStringNotContainsString( $unsafe, $html );
		}
	}

	/**
	 * Plain-text special characters are encoded.
	 */
	public function test_encodes_special_characters(): void {
		$html = $this->render_item( [ 'text' => 'Fuses & sockets' ] );

		self::assertStringContainsString( 'Fuses &amp; sockets', $html );
	}

	/**
	 * The anchor and custom class reach the list item.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_item(
			[
				'text'      => 'Free quotes',
				'anchor'    => 'quotes',
				'className' => 'is-highlighted',
			],
			[ 'jmc/listStyle' => 'bullet' ]
		);

		self::assertStringStartsWith( '<li id="quotes" class="jmc-list__item is-highlighted">', trim( $html ) );
	}

	/**
	 * Render the list item block template and return its output.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param array<string, mixed> $context    Context provided by the parent list.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_item( array $attributes, array $context = [] ): string {
		// Stands in for WP_Block, which supplies the parent list's context.
		$block = (object) [ 'context' => $context ];

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/list-item/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
