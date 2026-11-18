<?php
/**
 * Credential render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the credential block render callback.
 */
final class CredentialRenderTest extends TestCase {

	/**
	 * Register the Media Library images the tests use.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_attachments'] = [
			7 => [
				'alt'   => 'NICEIC Approved Contractor',
				'title' => 'niceic-logo',
			],
			8 => [
				'alt'   => '  ',
				'title' => 'TrustATrader',
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
	 * A credential with no usable image renders nothing.
	 *
	 * @dataProvider missing_image_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_omits_credential_without_image( array $attributes ): void {
		self::assertSame( '', trim( $this->render_credential( $attributes ) ) );
	}

	/**
	 * Attributes that leave a credential without an image.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function missing_image_provider(): array {
		return [
			'omitted'          => [ [] ],
			'zero'             => [ [ 'imageId' => 0 ] ],
			'negative'         => [ [ 'imageId' => -7 ] ],
			'non-numeric'      => [ [ 'imageId' => 'logo' ] ],
			'array'            => [ [ 'imageId' => [ 7 ] ] ],
			'null'             => [ [ 'imageId' => null ] ],
			'deleted image'    => [ [ 'imageId' => 99 ] ],
			'link but no logo' => [ [ 'link' => [ 'url' => 'https://example.com/' ] ] ],
		];
	}

	/**
	 * A logo renders as a list item with its medium image and alternative text.
	 */
	public function test_renders_logo_with_media_library_alt(): void {
		$html = $this->render_credential( [ 'imageId' => 7 ] );

		self::assertStringContainsString( '<li class="jmc-credential">', $html );
		self::assertStringContainsString( 'src="https://example.test/uploads/7-medium.png"', $html );
		self::assertStringContainsString( 'class="jmc-image jmc-credential__image"', $html );
		self::assertStringContainsString( 'alt="NICEIC Approved Contractor"', $html );
		self::assertStringContainsString( 'sizes="10rem"', $html );
		self::assertStringNotContainsString( '<a', $html );
	}

	/**
	 * A numeric string image ID, as some editors store it, still renders.
	 */
	public function test_accepts_numeric_string_image_id(): void {
		self::assertStringContainsString( '7-medium.png', $this->render_credential( [ 'imageId' => '7' ] ) );
	}

	/**
	 * A logo without alternative text is named by its title, never left unnamed.
	 */
	public function test_falls_back_to_title_without_alt(): void {
		$html = $this->render_credential( [ 'imageId' => 8 ] );

		self::assertStringContainsString( 'alt="TrustATrader"', $html );
	}

	/**
	 * The anchor and custom class reach the list item.
	 */
	public function test_applies_anchor_and_class_name(): void {
		$html = $this->render_credential(
			[
				'imageId'   => 7,
				'anchor'    => 'niceic',
				'className' => 'is-featured',
			]
		);

		self::assertStringContainsString( '<li id="niceic" class="jmc-credential is-featured">', $html );
	}

	/**
	 * A safe link wraps the logo and opens in the same tab by default.
	 */
	public function test_links_logo(): void {
		$html = $this->render_credential(
			[
				'imageId' => 7,
				'link'    => [ 'url' => 'https://niceic.com/' ],
			]
		);

		self::assertStringContainsString( '<a class="jmc-credential__link" href="https://niceic.com/">', $html );
		self::assertStringContainsString( 'alt="NICEIC Approved Contractor"', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( 'opens in a new tab', $html );
	}

	/**
	 * A new-tab link is protected and announced to screen readers.
	 */
	public function test_new_tab_link_is_protected_and_announced(): void {
		$html = $this->render_credential(
			[
				'imageId' => 7,
				'link'    => [
					'url'           => 'https://niceic.com/',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'target="_blank"', $html );
		self::assertStringContainsString( 'rel="noopener noreferrer"', $html );
		self::assertStringContainsString( '<span class="screen-reader-text">(opens in a new tab)</span>', $html );
	}

	/**
	 * Telephone links hand off to the dialler, so never open a new tab.
	 */
	public function test_telephone_link_ignores_new_tab(): void {
		$html = $this->render_credential(
			[
				'imageId' => 7,
				'link'    => [
					'url'           => 'tel:01234 567890',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( 'opens in a new tab', $html );
	}

	/**
	 * Only a literal true opens a new tab.
	 */
	public function test_truthy_new_tab_values_are_ignored(): void {
		$html = $this->render_credential(
			[
				'imageId' => 7,
				'link'    => [
					'url'           => 'https://niceic.com/',
					'opensInNewTab' => 'true',
				],
			]
		);

		self::assertStringNotContainsString( 'target=', $html );
	}

	/**
	 * Unsafe or malformed links leave the logo unlinked rather than dropping it.
	 *
	 * @dataProvider unsafe_link_provider
	 *
	 * @param mixed $link Link attribute value.
	 */
	public function test_unsafe_link_renders_unlinked_logo( mixed $link ): void {
		$html = $this->render_credential(
			[
				'imageId' => 7,
				'link'    => $link,
			]
		);

		self::assertStringContainsString( '7-medium.png', $html );
		self::assertStringNotContainsString( '<a', $html );
	}

	/**
	 * Link values that must not produce a link.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function unsafe_link_provider(): array {
		return [
			'empty url'      => [ [ 'url' => '' ] ],
			'javascript'     => [ [ 'url' => 'javascript:alert(1)' ] ],
			'mailto'         => [ [ 'url' => 'mailto:hello@example.com' ] ],
			'embedded space' => [ [ 'url' => 'https://example .com/' ] ],
			'invalid phone'  => [ [ 'url' => 'tel:call-us' ] ],
			'url not string' => [ [ 'url' => [ 'https://example.com/' ] ] ],
			'link string'    => [ 'https://example.com/' ],
			'link null'      => [ null ],
		];
	}

	/**
	 * Execute the real credential render callback.
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
	private function render_credential( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/credential/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
