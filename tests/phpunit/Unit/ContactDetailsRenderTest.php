<?php
/**
 * Contact details render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the contact details block render callback.
 */
final class ContactDetailsRenderTest extends TestCase {

	/**
	 * Store a complete set of business contact details.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION        => '01234 567890',
			JMC_BUSINESS_WHATSAPP_OPTION     => '+44 7700 900123',
			JMC_BUSINESS_EMAIL_OPTION        => 'hello@example.com',
			JMC_BUSINESS_HOURS_OPTION        => [
				'monday'   => [
					'opens'  => '08:00',
					'closes' => '17:30',
				],
				'saturday' => [
					'opens'  => '09:00',
					'closes' => '12:00',
				],
			],
			JMC_BUSINESS_SERVICE_AREA_OPTION => [ 'Glasgow', 'East Kilbride' ],
			'time_format'                    => 'g:i a',
		];
	}

	/**
	 * Reset the option fixtures between tests.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'] );
	}

	/**
	 * Every stored detail renders as a labelled description list inside an address.
	 */
	public function test_renders_all_details(): void {
		$html = $this->render_contact_details();

		self::assertStringStartsWith( '<address class="jmc-contact-details">', trim( $html ) );
		self::assertMatchesRegularExpression(
			'#<dt[^>]*>(?:<svg[^>]*></svg>)?<span[^>]*>Phone</span></dt>\s*<dd[^>]*><a href="tel:01234567890">01234 567890</a></dd>#',
			$html
		);
		self::assertMatchesRegularExpression(
			'#<dt[^>]*>(?:<svg[^>]*></svg>)?<span[^>]*>Email</span></dt>\s*<dd[^>]*><a href="mailto:hello@example.com">hello@example.com</a></dd>#',
			$html
		);
		self::assertMatchesRegularExpression(
			'#<dt[^>]*>(?:<svg[^>]*></svg>)?<span[^>]*>WhatsApp</span></dt>\s*<dd[^>]*><a href="https://wa.me/447700900123">\+44 7700 900123</a></dd>#',
			$html
		);
		self::assertMatchesRegularExpression( '#<dt[^>]*>(?:<svg[^>]*></svg>)?<span[^>]*>Opening hours</span></dt>#', $html );
		self::assertMatchesRegularExpression(
			'#<dt[^>]*>(?:<svg[^>]*></svg>)?<span[^>]*>Areas covered</span></dt>\s*<dd[^>]*>\s*<ul class="jmc-contact-details__areas" role="list">\s*<li>Glasgow</li>\s*<li>East Kilbride</li>#',
			$html
		);
		self::assertSame( 1, substr_count( $html, '<dl' ) );
	}

	/**
	 * Each label carries its decorative icon, with the label text kept for screen readers.
	 */
	public function test_labels_carry_icons(): void {
		$html = $this->render_contact_details();

		foreach ( [
			'Phone'         => 'jmc/phone',
			'WhatsApp'      => 'jmc/whatsapp',
			'Email'         => 'jmc/mail',
			'Opening hours' => 'jmc/clock',
			'Areas covered' => 'jmc/house',
		] as $label => $icon ) {
			self::assertMatchesRegularExpression(
				'#<dt class="jmc-contact-details__label"><svg class="jmc-contact-details__icon" data-icon="' . preg_quote( $icon, '#' ) . '" aria-hidden="true"[^>]*></svg><span class="jmc-contact-details__label-text">' . $label . '</span></dt>#',
				$html
			);
		}
	}

	/**
	 * Every day is listed in week order, with machine-readable times or "Closed".
	 */
	public function test_lists_opening_hours_for_every_day(): void {
		$text = $this->text_of( $this->render_contact_details() );

		self::assertStringContainsString(
			'Monday 8:00 am to 5:30 pm Tuesday Closed Wednesday Closed Thursday Closed Friday Closed Saturday 9:00 am to 12:00 pm Sunday Closed',
			$text
		);

		$html = $this->render_contact_details();

		self::assertStringContainsString( '<time datetime="08:00">8:00 am</time> to <time datetime="17:30">5:30 pm</time>', $html );
		self::assertSame( 7, substr_count( $html, 'class="jmc-contact-details__day"' ) );
	}

	/**
	 * Switched-off fields are omitted, leaving no empty items behind.
	 */
	public function test_omits_switched_off_fields(): void {
		$html = $this->render_contact_details(
			[
				'showPhone' => false,
				'showHours' => false,
			]
		);

		self::assertStringNotContainsString( 'tel:', $html );
		self::assertStringNotContainsString( 'Opening hours', $html );
		self::assertStringNotContainsString( '<time', $html );
		self::assertStringContainsString( 'mailto:hello@example.com', $html );
		self::assertStringContainsString( '<li>Glasgow</li>', $html );
		self::assertSame( 3, substr_count( $html, '<dt' ) );
	}

	/**
	 * Malformed visibility attributes fall back to showing the field.
	 */
	public function test_malformed_visibility_shows_field(): void {
		$html = $this->render_contact_details(
			[
				'showPhone' => 'false',
				'showEmail' => 0,
			]
		);

		self::assertStringContainsString( 'tel:01234567890', $html );
		self::assertStringContainsString( 'mailto:hello@example.com', $html );
	}

	/**
	 * Missing or invalid stored details are omitted without empty markup.
	 */
	public function test_omits_missing_and_invalid_details(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION        => 'javascript:alert(1)',
			JMC_BUSINESS_EMAIL_OPTION        => 'not-an-email',
			JMC_BUSINESS_HOURS_OPTION        => [
				'monday' => [
					'opens'  => '17:00',
					'closes' => '08:00',
				],
			],
			JMC_BUSINESS_SERVICE_AREA_OPTION => [ 'Glasgow' ],
		];

		$html = $this->render_contact_details();

		self::assertStringNotContainsString( 'javascript:', $html );
		self::assertStringNotContainsString( 'not-an-email', $html );
		self::assertStringNotContainsString( 'Opening hours', $html );
		self::assertSame( 1, substr_count( $html, '<dt' ) );
		self::assertStringContainsString( '<li>Glasgow</li>', $html );
	}

	/**
	 * Nothing renders when there are no details to show.
	 *
	 * @dataProvider empty_provider
	 *
	 * @param array<string, mixed> $options    Stored options.
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function test_renders_nothing_without_details( array $options, array $attributes ): void {
		$GLOBALS['jmc_test_options'] = $options + ( $GLOBALS['jmc_test_options'] ?? [] );

		self::assertSame( '', trim( $this->render_contact_details( $attributes ) ) );
	}

	/**
	 * Stored options and attributes that leave nothing to show.
	 *
	 * @return array<string, array{array<string, mixed>, array<string, mixed>}>
	 */
	public function empty_provider(): array {
		return [
			'nothing stored'      => [
				[
					JMC_BUSINESS_PHONE_OPTION        => '',
					JMC_BUSINESS_WHATSAPP_OPTION     => '',
					JMC_BUSINESS_EMAIL_OPTION        => '',
					JMC_BUSINESS_HOURS_OPTION        => [],
					JMC_BUSINESS_SERVICE_AREA_OPTION => [],
				],
				[],
			],
			'everything hidden'   => [
				[],
				[
					'showPhone'       => false,
					'showWhatsApp'    => false,
					'showEmail'       => false,
					'showHours'       => false,
					'showServiceArea' => false,
				],
			],
			'malformed, unstored' => [
				[
					JMC_BUSINESS_PHONE_OPTION        => [ '01234 567890' ],
					JMC_BUSINESS_WHATSAPP_OPTION     => '07700 900123',
					JMC_BUSINESS_EMAIL_OPTION        => null,
					JMC_BUSINESS_HOURS_OPTION        => 'Mon–Fri 8–5',
					JMC_BUSINESS_SERVICE_AREA_OPTION => "\n \n",
				],
				[],
			],
		];
	}

	/**
	 * Stored details are escaped on output.
	 */
	public function test_escapes_stored_values(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_SERVICE_AREA_OPTION ] = [ 'Brigg & District', 'Hull "East"' ];

		$html = $this->render_contact_details();

		self::assertStringContainsString( '<li>Brigg &amp; District</li>', $html );
		self::assertStringContainsString( '<li>Hull &quot;East&quot;</li>', $html );
	}

	/**
	 * Collapse rendered markup into its readable text.
	 *
	 * @param string $html Rendered markup.
	 *
	 * @return string Text content with whitespace collapsed.
	 */
	private function text_of( string $html ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- WordPress isn't loaded in isolated tests.
		return trim( (string) preg_replace( '/\s+/', ' ', strip_tags( $html ) ) );
	}

	/**
	 * Execute the real contact details render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_contact_details( array $attributes = [] ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/contact-details/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
