<?php
/**
 * Paragraph block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the paragraph block render callback.
 */
final class ParagraphRenderTest extends TestCase {

	/**
	 * A paragraph with no usable text renders nothing.
	 *
	 * @dataProvider empty_text_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_omits_paragraph_without_text( array $attributes ): void {
		self::assertSame( '', trim( $this->render_paragraph( $attributes ) ) );
	}

	/**
	 * Attributes that leave a paragraph without text.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function empty_text_provider(): array {
		return [
			'omitted'    => [ [] ],
			'empty'      => [ [ 'text' => '' ] ],
			'whitespace' => [ [ 'text' => "  \n\t " ] ],
			'null'       => [ [ 'text' => null ] ],
			'number'     => [ [ 'text' => 42 ] ],
			'array'      => [ [ 'text' => [ 'Free quotes' ] ] ],
		];
	}

	/**
	 * Omitted settings render the default paragraph with trimmed text.
	 */
	public function test_renders_default_paragraph(): void {
		$html = $this->render_paragraph( [ 'text' => '  Reliable electrical services.  ' ] );

		self::assertMatchesRegularExpression(
			'#^<p class="jmc-paragraph">\s*Reliable electrical services\.\s*</p>$#',
			trim( $html )
		);
	}

	/**
	 * Supported variants add their class.
	 *
	 * @dataProvider variant_provider
	 *
	 * @param string $variant Variant attribute value.
	 * @param string $classes Expected paragraph classes.
	 */
	public function test_applies_variant( string $variant, string $classes ): void {
		$html = $this->render_paragraph(
			[
				'text'    => 'Our services',
				'variant' => $variant,
			]
		);

		self::assertStringStartsWith( "<p class=\"{$classes}\">", trim( $html ) );
	}

	/**
	 * Variant values and the classes they produce.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function variant_provider(): array {
		return [
			'default' => [ 'default', 'jmc-paragraph' ],
			'label'   => [ 'label', 'jmc-paragraph jmc-label' ],
		];
	}

	/**
	 * Unsupported or malformed variants fall back to the default paragraph.
	 *
	 * @dataProvider malformed_value_provider
	 *
	 * @param mixed $variant Variant attribute value.
	 */
	public function test_ignores_malformed_variant( mixed $variant ): void {
		$html = $this->render_paragraph(
			[
				'text'    => 'Our services',
				'variant' => $variant,
			]
		);

		self::assertStringStartsWith( '<p class="jmc-paragraph">', trim( $html ) );
	}

	/**
	 * A paragraph inside a hero gains the hero text class.
	 */
	public function test_applies_hero_context(): void {
		$html = $this->render_paragraph(
			[ 'text' => 'Fully qualified electricians.' ],
			[ 'jmc/variant' => 'hero' ]
		);

		self::assertStringStartsWith( '<p class="jmc-paragraph jmc-hero__text-para">', trim( $html ) );
	}

	/**
	 * A label inside a hero keeps the label style rather than the hero text class.
	 */
	public function test_label_ignores_hero_context(): void {
		$html = $this->render_paragraph(
			[
				'text'    => 'Domestic',
				'variant' => 'label',
			],
			[ 'jmc/variant' => 'hero' ]
		);

		self::assertStringStartsWith( '<p class="jmc-paragraph jmc-label">', trim( $html ) );
		self::assertStringNotContainsString( 'jmc-hero__text-para', $html );
	}

	/**
	 * Unsupported or malformed context falls back to the default paragraph.
	 *
	 * @dataProvider malformed_value_provider
	 *
	 * @param mixed $context_variant Context variant value.
	 */
	public function test_ignores_malformed_context( mixed $context_variant ): void {
		$html = $this->render_paragraph(
			[ 'text' => 'Our services' ],
			[ 'jmc/variant' => $context_variant ]
		);

		self::assertStringStartsWith( '<p class="jmc-paragraph">', trim( $html ) );
	}

	/**
	 * Variant and context values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_value_provider(): array {
		return [
			'unknown'    => [ 'lead' ],
			'wrong case' => [ 'Label' ],
			'array'      => [ [ 'label' ] ],
			'number'     => [ 1 ],
			'null'       => [ null ],
		];
	}

	/**
	 * The anchor and custom class reach the paragraph.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_paragraph(
			[
				'text'      => 'Our services',
				'variant'   => 'label',
				'anchor'    => 'services-intro',
				'className' => 'is-lead',
			]
		);

		self::assertStringStartsWith( '<p class="jmc-paragraph is-lead jmc-label" id="services-intro">', trim( $html ) );
	}

	/**
	 * Text, anchor and custom class are escaped or sanitised.
	 */
	public function test_escapes_output(): void {
		$html = $this->render_paragraph(
			[
				'text'      => 'Fuses & <script>alert(1)</script>',
				'anchor'    => '"><script>',
				'className' => 'is-lead" onclick="alert(1)',
			]
		);

		self::assertStringContainsString( 'Fuses &amp; &lt;script&gt;alert(1)&lt;/script&gt;', $html );
		self::assertStringContainsString( 'id="&quot;&gt;&lt;script&gt;"', $html );
		self::assertStringContainsString( 'class="jmc-paragraph is-lead onclickalert1"', $html );
		self::assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * Render the paragraph block template and return its output.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 * @param array<string, mixed> $context    Context provided by the parent block.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_paragraph( array $attributes, array $context = [] ): string {
		// Stands in for WP_Block, which supplies the parent block's context.
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
			require dirname( __DIR__, 3 ) . '/src/blocks/content/paragraph/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
