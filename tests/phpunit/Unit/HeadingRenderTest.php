<?php
/**
 * Heading block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the heading block render callback.
 */
final class HeadingRenderTest extends TestCase {

	/**
	 * A heading with no usable text renders nothing.
	 *
	 * @dataProvider empty_text_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_omits_heading_without_text( array $attributes ): void {
		self::assertSame( '', trim( $this->render_heading( $attributes ) ) );
	}

	/**
	 * Attributes that leave a heading without text.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function empty_text_provider(): array {
		return [
			'omitted'    => [ [ 'level' => '3' ] ],
			'empty'      => [ [ 'text' => '' ] ],
			'whitespace' => [ [ 'text' => "  \n\t " ] ],
			'null'       => [ [ 'text' => null ] ],
			'number'     => [ [ 'text' => 42 ] ],
			'array'      => [ [ 'text' => [ 'Our services' ] ] ],
		];
	}

	/**
	 * Omitted settings render a level-two heading with trimmed text.
	 */
	public function test_renders_default_heading(): void {
		$html = $this->render_heading( [ 'text' => '  Solutions built around you  ' ] );

		self::assertMatchesRegularExpression(
			'#^<h2 class="jmc-heading">\s*Solutions built around you\s*</h2>$#',
			trim( $html )
		);
	}

	/**
	 * Supported levels, saved as strings or numbers, set the heading element.
	 *
	 * @dataProvider level_provider
	 *
	 * @param int|string $level Level attribute value.
	 * @param string     $tag   Expected heading element.
	 */
	public function test_applies_level( int|string $level, string $tag ): void {
		$html = $this->render_heading(
			[
				'text'  => 'Our services',
				'level' => $level,
			]
		);

		self::assertMatchesRegularExpression(
			"#^<{$tag} class=\"jmc-heading\">\s*Our services\s*</{$tag}>$#",
			trim( $html )
		);
	}

	/**
	 * Level values and the elements they produce.
	 *
	 * @return array<string, array{int|string, string}>
	 */
	public function level_provider(): array {
		return [
			'string 1' => [ '1', 'h1' ],
			'string 2' => [ '2', 'h2' ],
			'string 3' => [ '3', 'h3' ],
			'string 4' => [ '4', 'h4' ],
			'string 5' => [ '5', 'h5' ],
			'string 6' => [ '6', 'h6' ],
			'number 1' => [ 1, 'h1' ],
			'number 4' => [ 4, 'h4' ],
		];
	}

	/**
	 * Out-of-range or malformed levels fall back to a level-two heading.
	 *
	 * @dataProvider malformed_level_provider
	 *
	 * @param mixed $level Level attribute value.
	 */
	public function test_ignores_malformed_level( mixed $level ): void {
		$html = $this->render_heading(
			[
				'text'  => 'Our services',
				'level' => $level,
			]
		);

		self::assertMatchesRegularExpression( '#^<h2 class="jmc-heading">\s*Our services\s*</h2>$#', trim( $html ) );
	}

	/**
	 * Level values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_level_provider(): array {
		return [
			'zero'     => [ '0' ],
			'too deep' => [ '7' ],
			'negative' => [ -1 ],
			'tag name' => [ 'h3' ],
			'markup'   => [ '3><script>' ],
			'empty'    => [ '' ],
			'array'    => [ [ '3' ] ],
			'boolean'  => [ true ],
			'null'     => [ null ],
		];
	}

	/**
	 * A heading inside a hero gains the hero heading class.
	 */
	public function test_applies_hero_context(): void {
		$html = $this->render_heading(
			[
				'text'  => 'Electricians you can trust',
				'level' => '1',
			],
			[ 'jmc/variant' => 'hero' ]
		);

		self::assertStringStartsWith( '<h1 class="jmc-heading jmc-hero__text-heading">', trim( $html ) );
	}

	/**
	 * Unsupported or malformed context falls back to the default heading.
	 *
	 * @dataProvider malformed_context_provider
	 *
	 * @param mixed $context_variant Context variant value.
	 */
	public function test_ignores_malformed_context( mixed $context_variant ): void {
		$html = $this->render_heading(
			[ 'text' => 'Our services' ],
			[ 'jmc/variant' => $context_variant ]
		);

		self::assertStringStartsWith( '<h2 class="jmc-heading">', trim( $html ) );
	}

	/**
	 * Context values that are not supported.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_context_provider(): array {
		return [
			'unknown'    => [ 'banner' ],
			'wrong case' => [ 'Hero' ],
			'array'      => [ [ 'hero' ] ],
			'null'       => [ null ],
		];
	}

	/**
	 * The anchor and custom class reach the heading.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_heading(
			[
				'text'      => 'Our services',
				'level'     => '3',
				'anchor'    => 'services',
				'className' => 'is-style-display',
			]
		);

		self::assertStringStartsWith( '<h3 class="jmc-heading is-style-display" id="services">', trim( $html ) );
	}

	/**
	 * Text, anchor and custom class are escaped or sanitised.
	 */
	public function test_escapes_output(): void {
		$html = $this->render_heading(
			[
				'text'      => 'Fuses & <script>alert(1)</script>',
				'anchor'    => '"><script>',
				'className' => 'is-large" onclick="alert(1)',
			]
		);

		self::assertStringContainsString( 'Fuses &amp; &lt;script&gt;alert(1)&lt;/script&gt;', $html );
		self::assertStringContainsString( 'id="&quot;&gt;&lt;script&gt;"', $html );
		self::assertStringContainsString( 'class="jmc-heading is-large onclickalert1"', $html );
		self::assertStringNotContainsString( '<script>', $html );
	}

	/**
	 * Render the heading block template and return its output.
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
	private function render_heading( array $attributes, array $context = [] ): string {
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
			require dirname( __DIR__, 3 ) . '/src/blocks/content/heading/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
