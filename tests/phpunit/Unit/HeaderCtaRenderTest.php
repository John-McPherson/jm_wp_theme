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
	 * Reset the option fixtures between tests.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'] );
	}

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
	 * Saved CTAs without a link or button type render as primary `link` buttons.
	 */
	public function test_saved_cta_renders_as_primary_link(): void {
		$saved = [
			'text' => 'Get a quote',
			'link' => [ 'url' => 'https://example.com/contact/' ],
		];

		self::assertSame(
			$this->render_cta(
				$saved + [
					'linkType'   => 'link',
					'buttonType' => 'primary',
				]
			),
			$this->render_cta( $saved )
		);
	}

	/**
	 * A `link` CTA may link to an email address, as the button block does.
	 */
	public function test_renders_mailto_link(): void {
		$html = $this->render_cta(
			[
				'text' => 'Email us',
				'link' => [ 'url' => 'mailto:hello@example.com' ],
			]
		);

		self::assertStringContainsString( 'href="mailto:hello@example.com"', $html );
	}

	/**
	 * Phone, email and WhatsApp CTAs link to the site-wide business details, not their own link.
	 */
	public function test_contact_types_use_business_details(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION    => '01234 567890',
			JMC_BUSINESS_EMAIL_OPTION    => 'hello@example.com',
			JMC_BUSINESS_WHATSAPP_OPTION => '+44 7700 900123',
		];

		$link = [
			'url'           => 'https://example.com/',
			'opensInNewTab' => true,
		];

		$phone = $this->render_cta(
			[
				'text'     => 'Call us',
				'linkType' => 'phone',
				'link'     => $link,
			]
		);

		self::assertStringContainsString( 'href="tel:01234567890"', $phone );
		self::assertStringContainsString( 'class="jmc-button jmc-button--small jmc-header-cta"', $phone );
		self::assertStringNotContainsString( 'example.com', $phone );
		self::assertStringNotContainsString( 'target=', $phone );

		$email = $this->render_cta(
			[
				'text'     => 'Email us',
				'linkType' => 'email',
				'link'     => $link,
			]
		);

		self::assertStringContainsString( 'href="mailto:hello@example.com"', $email );
		self::assertStringNotContainsString( 'target=', $email );

		$whatsapp = $this->render_cta(
			[
				'text'     => 'WhatsApp us',
				'linkType' => 'whatsapp',
				'link'     => [ 'url' => 'https://example.com/' ],
			]
		);

		self::assertStringContainsString( 'href="https://wa.me/447700900123"', $whatsapp );
		self::assertStringNotContainsString( 'example.com', $whatsapp );
		self::assertMatchesRegularExpression(
			'#<svg class="jmc-button__icon" data-icon="jmc/whatsapp" aria-hidden="true"[^>]*></svg>\s*WhatsApp us#',
			$whatsapp
		);
		self::assertStringNotContainsString( 'jmc-button__icon', $phone . $email );
	}

	/**
	 * Email and WhatsApp CTAs add their pre-filled message; phone CTAs ignore it.
	 */
	public function test_contact_types_add_prefilled_message(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION    => '01234 567890',
			JMC_BUSINESS_EMAIL_OPTION    => 'hello@example.com',
			JMC_BUSINESS_WHATSAPP_OPTION => '+44 7700 900123',
		];

		$message = [
			'text'            => 'Contact us',
			'emailSubject'    => 'Quote request',
			'emailBody'       => "Please call me back.\nThanks",
			'whatsappMessage' => "Hi there,\nI need an electrician.",
		];

		self::assertStringContainsString(
			'href="mailto:hello@example.com?subject=Quote%20request&amp;body=Please%20call%20me%20back.%0D%0AThanks"',
			$this->render_cta( [ 'linkType' => 'email' ] + $message )
		);
		self::assertStringContainsString(
			'href="https://wa.me/447700900123?text=Hi%20there%2C%0AI%20need%20an%20electrician."',
			$this->render_cta( [ 'linkType' => 'whatsapp' ] + $message )
		);
		self::assertStringContainsString(
			'href="tel:01234567890"',
			$this->render_cta( [ 'linkType' => 'phone' ] + $message )
		);
	}

	/**
	 * Contact CTAs render nothing when their business detail is empty or invalid.
	 *
	 * @dataProvider missing_contact_provider
	 *
	 * @param array<string, mixed> $options    Stored options.
	 * @param array<string, mixed> $attributes Block attributes to render.
	 */
	public function test_omits_contact_cta_without_details( array $options, array $attributes ): void {
		$GLOBALS['jmc_test_options'] = $options;

		self::assertSame( '', trim( $this->render_cta( $attributes ) ) );
	}

	/**
	 * Option and attribute sets that must render nothing.
	 *
	 * @return array<string, array{array<string, mixed>, array<string, mixed>}>
	 */
	public function missing_contact_provider(): array {
		// A valid own link must not stand in for a missing business detail.
		$link = [ 'url' => 'https://example.com/' ];

		return [
			'no phone set'    => [
				[],
				[
					'text'     => 'Call us',
					'linkType' => 'phone',
					'link'     => $link,
				],
			],
			'no email set'    => [
				[],
				[
					'text'     => 'Email us',
					'linkType' => 'email',
					'link'     => $link,
				],
			],
			'no WhatsApp set' => [
				[],
				[
					'text'     => 'WhatsApp us',
					'linkType' => 'whatsapp',
					'link'     => $link,
				],
			],
			'invalid phone'   => [
				[ JMC_BUSINESS_PHONE_OPTION => '0800 ELECTRIC' ],
				[
					'text'     => 'Call us',
					'linkType' => 'phone',
				],
			],
			'phone, no label' => [
				[ JMC_BUSINESS_PHONE_OPTION => '01234 567890' ],
				[ 'linkType' => 'phone' ],
			],
		];
	}

	/**
	 * An unknown link type falls back to the CTA's own link.
	 */
	public function test_unknown_link_type_uses_own_link(): void {
		$GLOBALS['jmc_test_options'] = [ JMC_BUSINESS_PHONE_OPTION => '01234 567890' ];

		$html = $this->render_cta(
			[
				'text'     => 'Contact',
				'linkType' => 'fax',
				'link'     => [ 'url' => '/contact/' ],
			]
		);

		self::assertStringContainsString( 'href="/contact/"', $html );
	}

	/**
	 * The secondary style adds its class after the header classes; unknown styles fall back to primary.
	 *
	 * @dataProvider button_type_provider
	 *
	 * @param mixed  $button_type Saved button type.
	 * @param string $classes     Expected class attribute.
	 */
	public function test_button_type_classes( mixed $button_type, string $classes ): void {
		$GLOBALS['jmc_test_options'] = [ JMC_BUSINESS_PHONE_OPTION => '01234 567890' ];

		foreach ( [ 'link', 'phone' ] as $link_type ) {
			self::assertStringContainsString(
				'class="' . $classes . '"',
				$this->render_cta(
					[
						'text'       => 'Contact',
						'link'       => [ 'url' => '/contact/' ],
						'linkType'   => $link_type,
						'buttonType' => $button_type,
					]
				)
			);
		}
	}

	/**
	 * Button types and the classes they render.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function button_type_provider(): array {
		return [
			'primary'    => [ 'primary', 'jmc-button jmc-button--small jmc-header-cta' ],
			'secondary'  => [ 'secondary', 'jmc-button jmc-button--small jmc-header-cta jmc-button__secondary' ],
			'unknown'    => [ 'tertiary', 'jmc-button jmc-button--small jmc-header-cta' ],
			'non-string' => [ [ 'secondary' ], 'jmc-button jmc-button--small jmc-header-cta' ],
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
