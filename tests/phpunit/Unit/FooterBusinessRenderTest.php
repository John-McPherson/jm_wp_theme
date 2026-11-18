<?php
/**
 * Footer business details render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the footer business details block render callback.
 */
final class FooterBusinessRenderTest extends TestCase {

	/**
	 * Start each test with no business contact settings.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_options'] = [];
	}

	/**
	 * Reset the option fixtures between tests.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'] );
	}

	/**
	 * Fully configured details render every line in order: the name and address
	 * from the block, the contact lines from the site-wide settings.
	 */
	public function test_renders_all_fields(): void {
		$this->set_contact_settings();

		$html = $this->render_business(
			[
				'companyName' => 'Example Electrical Ltd',
				'address'     => '1 High Street<br>Example Town',
				'className'   => 'is-custom',
			]
		);

		self::assertStringContainsString( 'class="jmc-footer-business is-custom"', $html );
		self::assertStringContainsString( '<p class="jmc-footer-business__name"><a href="https://example.com/">Example Electrical Ltd</a></p>', $html );
		self::assertStringContainsString( '<address class="jmc-footer-business__contact">', $html );
		self::assertStringContainsString( '1 High Street<br>Example Town', $html );
		self::assertStringContainsString( '<a href="tel:01234567890">01234 567 890</a>', $html );
		self::assertStringContainsString( '<a href="https://wa.me/447700900123"><span class="jmc-footer-business__hidden-label">WhatsApp: </span>+44 7700 900123</a>', $html );
		self::assertStringContainsString( '<a href="mailto:hello@example.com">hello@example.com</a>', $html );
		self::assertStringContainsString( 'Pudsey, Leeds and Bradford', $html );

		self::assertMatchesRegularExpression(
			'/__name.*__address.*__phone.*__whatsapp.*__email.*<\/address>.*__service-area/s',
			$html
		);
	}

	/**
	 * Legacy contact attributes are no longer rendered; the settings win.
	 */
	public function test_ignores_legacy_contact_attributes(): void {
		$this->set_contact_settings();

		$html = $this->render_business(
			[
				'phone'       => '09999 999999',
				'email'       => 'old@example.com',
				'serviceArea' => 'Old Town',
			]
		);

		self::assertStringNotContainsString( '09999', $html );
		self::assertStringNotContainsString( 'old@example.com', $html );
		self::assertStringNotContainsString( 'Old Town', $html );
		self::assertStringContainsString( 'tel:01234567890', $html );
	}

	/**
	 * Legacy contact attributes alone render nothing once the settings are empty.
	 */
	public function test_legacy_attributes_alone_render_nothing(): void {
		$html = $this->render_business(
			[
				'phone'       => '01234567890',
				'email'       => 'hello@example.com',
				'serviceArea' => 'Pudsey',
			]
		);

		self::assertSame( '', trim( $html ) );
	}

	/**
	 * Each contact and service-area line leads with its decorative theme icon;
	 * the business name has none.
	 */
	public function test_lines_use_theme_icons(): void {
		$this->set_contact_settings();

		$html = $this->render_business(
			[
				'companyName' => 'Example Electrical Ltd',
				'address'     => '1 High Street',
			]
		);

		foreach (
			[
				'address'      => 'jmc/map-pin',
				'phone'        => 'jmc/phone',
				'whatsapp'     => 'jmc/whatsapp',
				'email'        => 'jmc/mail',
				'service-area' => 'jmc/house',
			] as $line => $icon
		) {
			self::assertMatchesRegularExpression(
				'/<p class="jmc-footer-business__line jmc-footer-business__' . $line . '">\s*'
				. '<svg class="jmc-footer-business__icon" data-icon="' . preg_quote( $icon, '/' ) . '" aria-hidden="true"/',
				$html,
				sprintf( 'The %s line should start with %s.', $line, $icon )
			);
		}

		self::assertSame( 5, substr_count( $html, '<svg' ) );
	}

	/**
	 * The service area is introduced by a visible label and read as one phrase.
	 *
	 * @dataProvider service_area_provider
	 *
	 * @param string[]     $places   Stored places.
	 * @param string       $expected Expected escaped phrase.
	 */
	public function test_service_area_is_one_phrase( array $places, string $expected ): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_SERVICE_AREA_OPTION ] = $places;

		self::assertStringContainsString(
			'<span><span class="jmc-footer-business__label">Areas covered:</span> ' . $expected . '</span>',
			$this->render_business( [] )
		);
	}

	/**
	 * Stored service areas and how the footer reads them.
	 *
	 * @return array<string,array{0:list<string>,1:string}>
	 */
	public function service_area_provider(): array {
		return [
			'one place'    => [ [ 'Pudsey & Leeds' ], 'Pudsey &amp; Leeds' ],
			'two places'   => [ [ 'Pudsey', 'Leeds' ], 'Pudsey and Leeds' ],
			'three places' => [ [ 'Pudsey', 'Leeds', 'Bradford' ], 'Pudsey, Leeds and Bradford' ],
		];
	}

	/**
	 * Icons are only rendered for lines that are present.
	 */
	public function test_omitted_lines_have_no_icon(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_PHONE_OPTION ] = '01234567890';

		$html = $this->render_business( [] );

		self::assertSame( 1, substr_count( $html, '<svg' ) );
		self::assertStringContainsString( 'data-icon="jmc/phone"', $html );
	}

	/**
	 * Nothing is rendered when every field and setting is omitted, empty or malformed.
	 *
	 * @dataProvider empty_details_provider
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param array<string,mixed> $options    Stored options.
	 */
	public function test_renders_nothing_without_details( array $attributes, array $options ): void {
		$GLOBALS['jmc_test_options'] = $options;

		self::assertSame( '', trim( $this->render_business( $attributes ) ) );
	}

	/**
	 * Attribute and option sets that contain no usable details.
	 *
	 * @return array<string,array{0:array<string,mixed>,1:array<string,mixed>}>
	 */
	public function empty_details_provider(): array {
		return [
			'omitted'           => [ [], [] ],
			'defaults'          => [
				[
					'companyName' => '',
					'address'     => '',
				],
				[
					JMC_BUSINESS_PHONE_OPTION        => '',
					JMC_BUSINESS_WHATSAPP_OPTION     => '',
					JMC_BUSINESS_EMAIL_OPTION        => '',
					JMC_BUSINESS_SERVICE_AREA_OPTION => [],
				],
			],
			'whitespace/markup' => [
				[
					'companyName' => '  ',
					'address'     => '<br><br>',
				],
				[ JMC_BUSINESS_SERVICE_AREA_OPTION => [ '  ', '<b>' ] ],
			],
			'wrong types'       => [
				[
					'companyName' => [ 'x' ],
					'address'     => 12345,
				],
				[
					JMC_BUSINESS_PHONE_OPTION        => 12345,
					JMC_BUSINESS_EMAIL_OPTION        => [ 'hello@example.com' ],
					JMC_BUSINESS_SERVICE_AREA_OPTION => 42,
				],
			],
			'invalid settings'  => [
				[],
				[
					JMC_BUSINESS_PHONE_OPTION    => 'Ask for Dave',
					JMC_BUSINESS_WHATSAPP_OPTION => '07700 900123',
					JMC_BUSINESS_EMAIL_OPTION    => 'javascript:alert(1)',
				],
			],
		];
	}

	/**
	 * The address wrapper is omitted when there are no contact details.
	 */
	public function test_omits_contact_wrapper_without_contact_details(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_SERVICE_AREA_OPTION ] = [ 'Pudsey' ];

		$html = $this->render_business( [ 'companyName' => 'Example Electrical Ltd' ] );

		self::assertStringContainsString( 'Example Electrical Ltd', $html );
		self::assertStringContainsString( '__service-area', $html );
		self::assertStringNotContainsString( '<address', $html );
	}

	/**
	 * Each invalid setting drops only its own line; the others remain.
	 *
	 * @dataProvider invalid_setting_provider
	 *
	 * @param string $option Option holding an invalid value.
	 * @param string $value  The invalid value.
	 * @param string $line   The footer line it would have rendered.
	 */
	public function test_invalid_setting_omits_its_line( string $option, string $value, string $line ): void {
		$this->set_contact_settings();
		$GLOBALS['jmc_test_options'][ $option ] = $value;

		$html = $this->render_business( [] );

		self::assertStringNotContainsString( 'jmc-footer-business__' . $line . '"', $html );
		self::assertStringContainsString( '__service-area', $html );
		self::assertSame( 2, substr_count( $html, '<a ' ) );
	}

	/**
	 * Invalid values for each linked setting.
	 *
	 * @return array<string,array{0:string,1:string,2:string}>
	 */
	public function invalid_setting_provider(): array {
		return [
			'phone'    => [ JMC_BUSINESS_PHONE_OPTION, 'Ask for Dave', 'phone' ],
			'whatsapp' => [ JMC_BUSINESS_WHATSAPP_OPTION, '07700 900123', 'whatsapp' ],
			'email'    => [ JMC_BUSINESS_EMAIL_OPTION, 'javascript:alert(1)', 'email' ],
		];
	}

	/**
	 * Rich-text markup is stripped, entities decoded once and output escaped.
	 */
	public function test_values_are_plain_text_and_escaped(): void {
		$html = $this->render_business(
			[
				'companyName' => 'Smith &amp; Sons <script>alert(1)</script>',
				'address'     => '<strong>1</strong> &lt;High&gt; St<br />  <br>Town',
			]
		);

		self::assertStringContainsString( 'Smith &amp; Sons alert(1)', $html );
		self::assertStringNotContainsString( '<script', $html );
		self::assertStringNotContainsString( '<strong', $html );
		self::assertStringContainsString( '1 &lt;High&gt; St<br>Town', $html );
	}

	/**
	 * Store a complete, valid set of business contact settings.
	 */
	private function set_contact_settings(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION        => '01234 567 890',
			JMC_BUSINESS_WHATSAPP_OPTION     => '+44 7700 900123',
			JMC_BUSINESS_EMAIL_OPTION        => 'hello@example.com',
			JMC_BUSINESS_SERVICE_AREA_OPTION => [ 'Pudsey', 'Leeds', 'Bradford' ],
		];
	}

	/**
	 * Render the block template with the supplied attributes.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_business( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/footer-business/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
