<?php
/**
 * Button content block render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the button block render callback.
 */
final class ButtonRenderTest extends TestCase {

	/**
	 * Reset the option fixtures between tests.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'] );
	}

	/**
	 * Phone and email buttons link to the site-wide business details, not their own link.
	 */
	public function test_contact_buttons_use_business_details(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION => '01234 567890',
			JMC_BUSINESS_EMAIL_OPTION => 'hello@example.com',
		];

		$phone = $this->render_button(
			[
				'text'     => 'Call us',
				'linkType' => 'phone',
				'link'     => [
					'url'           => 'https://example.com/',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $phone );
		self::assertStringContainsString( 'Call us', $phone );
		self::assertStringNotContainsString( 'example.com', $phone );
		self::assertStringNotContainsString( 'target=', $phone );

		$email = $this->render_button(
			[
				'text'     => 'Email us',
				'linkType' => 'email',
			]
		);

		self::assertStringContainsString( 'href="mailto:hello@example.com"', $email );
	}

	/**
	 * Contact buttons render nothing without a valid business detail or label.
	 *
	 * @dataProvider missing_contact_provider
	 *
	 * @param array<string, mixed> $options    Stored options.
	 * @param array<string, mixed> $attributes Block attributes to render.
	 */
	public function test_omits_contact_button_without_details( array $options, array $attributes ): void {
		$GLOBALS['jmc_test_options'] = $options;

		self::assertSame( '', trim( $this->render_button( $attributes ) ) );
	}

	/**
	 * Option and attribute sets that must render nothing.
	 *
	 * @return array<string, array{array<string, mixed>, array<string, mixed>}>
	 */
	public function missing_contact_provider(): array {
		$phone = [
			'text'     => 'Call us',
			'linkType' => 'phone',
		];
		$email = [
			'text'     => 'Email us',
			'linkType' => 'email',
		];

		return [
			'no phone set'    => [ [], $phone ],
			'no email set'    => [ [], $email ],
			'invalid phone'   => [ [ JMC_BUSINESS_PHONE_OPTION => '0800 ELECTRIC' ], $phone ],
			'invalid email'   => [ [ JMC_BUSINESS_EMAIL_OPTION => 'hello@' ], $email ],
			'phone, no label' => [
				[ JMC_BUSINESS_PHONE_OPTION => '01234 567890' ],
				[ 'linkType' => 'phone' ],
			],
		];
	}

	/**
	 * An unknown link type falls back to the button's own link.
	 */
	public function test_unknown_link_type_uses_own_link(): void {
		$GLOBALS['jmc_test_options'] = [ JMC_BUSINESS_PHONE_OPTION => '01234 567890' ];

		$html = $this->render_button(
			[
				'text'     => 'Contact',
				'linkType' => 'fax',
				'link'     => [ 'url' => '/contact/' ],
			]
		);

		self::assertStringContainsString( 'href="/contact/"', $html );
	}

	/**
	 * A configured web link renders a primary button with its label and destination.
	 */
	public function test_renders_configured_url(): void {
		$html = $this->render_button(
			[
				'text' => 'Get a quote',
				'link' => [ 'url' => 'https://example.com/contact/' ],
			]
		);

		self::assertStringContainsString( 'href="https://example.com/contact/"', $html );
		self::assertStringContainsString( 'class="jmc-button"', $html );
		self::assertStringContainsString( 'Get a quote', $html );
		self::assertStringNotContainsString( 'target=', $html );
	}

	/**
	 * A site-relative path is a valid destination.
	 */
	public function test_renders_site_relative_path(): void {
		$html = $this->render_button(
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
		$html = $this->render_button(
			[
				'text' => 'Call 01234 567 890',
				'link' => [ 'url' => 'tel:01234 567 890' ],
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $html );
	}

	/**
	 * An email destination renders a mailto link that never opens in a new tab.
	 */
	public function test_renders_mailto_link(): void {
		$html = $this->render_button(
			[
				'text' => 'Email us',
				'link' => [
					'url'           => 'mailto:hello@example.com?subject=Quote',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringContainsString( 'href="mailto:hello@example.com?subject=Quote"', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * The editor's new-tab setting opens web links in a new tab with safe rel tokens and a hint.
	 */
	public function test_opens_in_new_tab_adds_rel_and_hint(): void {
		$html = $this->render_button(
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
	 * Content saved with the legacy `target` key still opens in a new tab.
	 */
	public function test_legacy_target_opens_in_new_tab(): void {
		$html = $this->render_button(
			[
				'text' => 'Get a quote',
				'link' => [
					'url'    => 'https://example.com/',
					'target' => '_blank',
				],
			]
		);

		self::assertStringContainsString( 'target="_blank"', $html );
	}

	/**
	 * Unsupported target values are not output.
	 */
	public function test_ignores_unsupported_target(): void {
		$html = $this->render_button(
			[
				'text' => 'Get a quote',
				'link' => [
					'url'    => 'https://example.com/',
					'target' => '_top" onclick="alert(1)',
				],
			]
		);

		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( 'onclick', $html );
	}

	/**
	 * A disabled, missing or malformed new-tab setting keeps the link in the same tab.
	 *
	 * @dataProvider same_tab_provider
	 *
	 * @param mixed $opens_in_new_tab Stored `opensInNewTab` value, or null to omit it.
	 */
	public function test_stays_in_same_tab( mixed $opens_in_new_tab ): void {
		$link = [ 'url' => 'https://example.com/' ];

		if ( null !== $opens_in_new_tab ) {
			$link['opensInNewTab'] = $opens_in_new_tab;
		}

		$html = $this->render_button(
			[
				'text' => 'Get a quote',
				'link' => $link,
			]
		);

		self::assertStringContainsString( 'href="https://example.com/"', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( 'rel=', $html );
		self::assertStringNotContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * New-tab values that must not open a new tab.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function same_tab_provider(): array {
		return [
			'missing' => [ null ],
			'false'   => [ false ],
			'string'  => [ 'true' ],
			'integer' => [ 1 ],
			'array'   => [ [ true ] ],
		];
	}

	/**
	 * Telephone links never open in a new tab, even when requested.
	 */
	public function test_telephone_link_ignores_new_tab(): void {
		$html = $this->render_button(
			[
				'text' => 'Call us',
				'link' => [
					'url'           => 'tel:01234567890',
					'opensInNewTab' => true,
				],
			]
		);

		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * Supported variants map to their class; unknown variants fall back to primary.
	 */
	public function test_variant_classes(): void {
		$link = [ 'url' => '/contact/' ];

		self::assertStringContainsString(
			'class="jmc-button jmc-button__secondary"',
			$this->render_button(
				[
					'text'       => 'Contact',
					'link'       => $link,
					'buttonType' => 'secondary',
				]
			)
		);

		self::assertStringContainsString(
			'class="jmc-button"',
			$this->render_button(
				[
					'text'       => 'Contact',
					'link'       => $link,
					'buttonType' => 'tertiary',
				]
			)
		);
	}

	/**
	 * The label is escaped.
	 */
	public function test_escapes_label(): void {
		$html = $this->render_button(
			[
				'text' => '<script>alert(1)</script> & "quote"',
				'link' => [ 'url' => 'https://example.com/?a=1&b=2' ],
			]
		);

		self::assertStringNotContainsString( '<script>', $html );
		self::assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;quote&quot;', $html );
		self::assertStringContainsString( 'href="https://example.com/?a=1&amp;b=2"', $html );
	}

	/**
	 * Missing, empty, unsafe or malformed data omits the button entirely.
	 *
	 * @dataProvider omitted_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes to render.
	 */
	public function test_omits_button( array $attributes ): void {
		self::assertSame( '', trim( $this->render_button( $attributes ) ) );
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
			'missing link'      => [ [ 'text' => 'Call' ] ],
			'empty url'         => [
				[
					'text' => 'Call',
					'link' => [ 'url' => '' ],
				],
			],
			'placeholder hash'  => [
				[
					'text' => 'Call',
					'link' => [ 'url' => '#' ],
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
			'invalid mailto'    => [
				[
					'text' => 'Email',
					'link' => [ 'url' => 'mailto:hello@' ],
				],
			],
		];
	}

	/**
	 * Execute the real button render callback.
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
	private function render_button( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/button/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
