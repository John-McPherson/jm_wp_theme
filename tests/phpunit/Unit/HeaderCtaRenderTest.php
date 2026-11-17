<?php
/**
 * Header call-to-action render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the header call-to-action block render callback.
 */
final class HeaderCtaRenderTest extends TestCase {

	/**
	 * A configured web link renders a button with its label and destination.
	 */
	public function test_renders_configured_url(): void {
		$html = $this->render_cta(
			[
				'text' => 'Get a quote',
				'link' => [ 'url' => 'https://example.com/contact/' ],
			]
		);

		self::assertStringContainsString( 'href="https://example.com/contact/"', $html );
		self::assertStringContainsString( 'class="jmc-button jmc-button--small jmc-header-cta"', $html );
		self::assertStringContainsString( 'Get a quote', $html );
		self::assertStringNotContainsString( 'target=', $html );
	}

	/**
	 * A site-relative path is a valid destination.
	 */
	public function test_renders_site_relative_path(): void {
		$html = $this->render_cta(
			[
				'text' => 'Contact us',
				'link' => [ 'url' => '/contact/' ],
			]
		);

		self::assertStringContainsString( 'href="/contact/"', $html );
	}

	/**
	 * A telephone destination is normalised for the dialler.
	 */
	public function test_renders_normalised_telephone_link(): void {
		$html = $this->render_cta(
			[
				'text' => 'Call 01234 567 890',
				'link' => [ 'url' => 'tel:01234 567 890' ],
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $html );
		self::assertStringContainsString( 'Call 01234 567 890', $html );
	}

	/**
	 * Web links may open in a new tab, with safe rel tokens and a screen-reader hint.
	 */
	public function test_new_tab_url_adds_rel_and_hint(): void {
		$html = $this->render_cta(
			[
				'text' => 'Get a quote',
				'link' => [
					'url'           => 'https://example.com/',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'target="_blank"', $html );
		self::assertStringContainsString( 'rel="noopener noreferrer"', $html );
		self::assertStringContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * Telephone links never open in a new tab, even when requested.
	 */
	public function test_telephone_link_ignores_new_tab(): void {
		$html = $this->render_cta(
			[
				'text' => 'Call us',
				'link' => [
					'url'           => 'tel:01234567890',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * The label and custom classes are escaped or sanitised.
	 */
	public function test_escapes_label_and_sanitises_classes(): void {
		$html = $this->render_cta(
			[
				'text'      => '<script>alert(1)</script> & "quote"',
				'link'      => [ 'url' => 'https://example.com/?a=1&b=2' ],
				'className' => 'is-style-x" onclick="alert(1)',
			]
		);

		self::assertStringNotContainsString( '<script>', $html );
		self::assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;quote&quot;', $html );
		self::assertStringContainsString( 'href="https://example.com/?a=1&amp;b=2"', $html );
		// The injected quote is stripped, so the payload can only survive as an inert class token.
		self::assertStringNotContainsString( 'onclick=', $html );
		self::assertStringContainsString( 'class="jmc-button jmc-button--small jmc-header-cta is-style-x onclickalert1"', $html );
	}

	/**
	 * Missing, empty, unsafe or malformed data omits the CTA entirely.
	 *
	 * @dataProvider omitted_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 */
	public function test_omits_cta( array $attributes ): void {
		self::assertSame( '', trim( $this->render_cta( $attributes ) ) );
	}

	/**
	 * Attribute sets that must render nothing.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function omitted_provider(): array {
		$url = [ 'url' => 'https://example.com/' ];

		return [
			'no attributes'     => [ [] ],
			'empty text'        => [
				[
					'text' => '',
					'link' => $url,
				],
			],
			'whitespace text'   => [
				[
					'text' => '   ',
					'link' => $url,
				],
			],
			'non-string text'   => [
				[
					'text' => [ 'Call' ],
					'link' => $url,
				],
			],
			'missing link'      => [ [ 'text' => 'Call' ] ],
			'empty url'         => [
				[
					'text' => 'Call',
					'link' => [ 'url' => '' ],
				],
			],
			'malformed link'    => [
				[
					'text' => 'Call',
					'link' => 'https://example.com/',
				],
			],
			'javascript url'    => [
				[
					'text' => 'Call',
					'link' => [ 'url' => 'javascript:alert(1)' ],
				],
			],
			'mailto url'        => [
				[
					'text' => 'Call',
					'link' => [ 'url' => 'mailto:hello@example.com' ],
				],
			],
			'invalid telephone' => [
				[
					'text' => 'Call',
					'link' => [ 'url' => 'tel:0800-ELECTRIC' ],
				],
			],
			'protocol-relative' => [
				[
					'text' => 'Call',
					'link' => [ 'url' => '//example.com' ],
				],
			],
		];
	}

	/**
	 * Execute the real header CTA render callback.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * attributes that trigger them fail the test.
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_cta( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/header-cta/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
