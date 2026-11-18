<?php
/**
 * Business settings admin page tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/settings.php';
require_once dirname( __DIR__, 3 ) . '/inc/contact.php';

/**
 * Verify the business settings are registered, gated and rendered on Settings → Business.
 */
final class BusinessSettingsPageTest extends TestCase {

	/**
	 * Start with no settings and no registrations.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_options']  = [];
		$GLOBALS['jmc_test_settings'] = [];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'], $GLOBALS['jmc_test_settings'] );
	}

	/**
	 * Every setting belongs to the page's group, keeping its sanitiser and REST schema.
	 */
	public function test_registers_settings_in_the_business_group(): void {
		$this->run_closures( 'init' );

		$registered = $GLOBALS['jmc_test_settings']['settings'];

		self::assertSame( array_keys( jmc_business_contact_settings() ), array_keys( $registered ) );

		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			self::assertSame( JMC_BUSINESS_SETTINGS_PAGE, $registered[ $option ]['group'], $option );
			self::assertSame( $setting['sanitize'], $registered[ $option ]['sanitize_callback'], $option );
			self::assertSame( [ 'schema' => $setting['schema'] ], $registered[ $option ]['show_in_rest'], $option );
		}
	}

	/**
	 * The page sits under Settings and needs `manage_options`.
	 */
	public function test_adds_settings_page_for_administrators(): void {
		$this->run_closures( 'admin_menu' );

		$page = $GLOBALS['jmc_test_settings']['pages'][ JMC_BUSINESS_SETTINGS_PAGE ];

		self::assertSame( 'Business settings', $page['page_title'] );
		self::assertSame( 'Business', $page['menu_title'] );
		self::assertSame( 'manage_options', $page['capability'] );
		self::assertSame( 'jmc_business_settings_page', $page['callback'] );
	}

	/**
	 * Every setting has a field on the Business page, none on Settings → General.
	 */
	public function test_adds_fields_to_the_business_page_only(): void {
		$this->run_closures( 'admin_init' );

		$section = $GLOBALS['jmc_test_settings']['sections']['jmc_business_contact'];
		$fields  = $GLOBALS['jmc_test_settings']['fields'];

		self::assertSame( JMC_BUSINESS_SETTINGS_PAGE, $section['page'] );
		self::assertSame( array_keys( jmc_business_contact_settings() ), array_keys( $fields ) );

		foreach ( jmc_business_contact_settings() as $option => $setting ) {
			self::assertSame( JMC_BUSINESS_SETTINGS_PAGE, $fields[ $option ]['page'], $option );
			self::assertSame( 'jmc_business_contact', $fields[ $option ]['section'], $option );
			self::assertSame( $setting['label'], $fields[ $option ]['title'], $option );
		}
	}

	/**
	 * Row headings label their control; the hours fieldset labels itself with a legend.
	 */
	public function test_fields_are_labelled(): void {
		$this->run_closures( 'admin_init' );

		$fields = $GLOBALS['jmc_test_settings']['fields'];

		self::assertSame( [ 'label_for' => JMC_BUSINESS_PHONE_OPTION ], $fields[ JMC_BUSINESS_PHONE_OPTION ]['args'] );
		self::assertSame( [ 'label_for' => JMC_BUSINESS_SERVICE_AREA_OPTION ], $fields[ JMC_BUSINESS_SERVICE_AREA_OPTION ]['args'] );
		self::assertSame( [], $fields[ JMC_BUSINESS_HOURS_OPTION ]['args'] );

		$hours = $this->capture( $fields[ JMC_BUSINESS_HOURS_OPTION ]['callback'] );

		self::assertStringContainsString( '<legend class="screen-reader-text">Opening hours</legend>', $hours );
		self::assertStringContainsString( '<label for="jmc_business_hours-monday-opens">', $hours );
		self::assertStringContainsString( 'id="jmc_business_hours-monday-opens" type="time"', $hours );
	}

	/**
	 * Text inputs show the stored value, escaped, with their description.
	 */
	public function test_renders_input_field(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_EMAIL_OPTION ] = 'a"b@example.com';

		$html = $this->capture(
			static function (): void {
				jmc_business_setting_field( option: JMC_BUSINESS_EMAIL_OPTION, setting: jmc_business_contact_settings()[ JMC_BUSINESS_EMAIL_OPTION ] );
			}
		);

		self::assertStringContainsString(
			'<input name="jmc_business_email" id="jmc_business_email" type="email" value="a&quot;b@example.com" class="regular-text" aria-describedby="jmc_business_email-description" />',
			$html
		);
		self::assertStringContainsString( '<p class="description" id="jmc_business_email-description">Used by Email buttons.</p>', $html );
	}

	/**
	 * The service area shows one sanitised place per line.
	 */
	public function test_renders_service_area_textarea(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_SERVICE_AREA_OPTION ] = [ ' Leeds ', 'leeds', 'Bradford & Pudsey' ];

		$html = $this->capture(
			static function (): void {
				jmc_business_setting_field( option: JMC_BUSINESS_SERVICE_AREA_OPTION, setting: jmc_business_contact_settings()[ JMC_BUSINESS_SERVICE_AREA_OPTION ] );
			}
		);

		self::assertStringContainsString( ">Leeds\nBradford &amp; Pudsey</textarea>", $html );
		self::assertStringContainsString( 'id="jmc_business_service_area-description"', $html );
	}

	/**
	 * The address shows its sanitised lines in a textarea.
	 */
	public function test_renders_address_textarea(): void {
		$GLOBALS['jmc_test_options'][ JMC_BUSINESS_ADDRESS_OPTION ] = "1 High Street<br>Smith & Sons";

		$html = $this->capture(
			static function (): void {
				jmc_business_setting_field( option: JMC_BUSINESS_ADDRESS_OPTION, setting: jmc_business_contact_settings()[ JMC_BUSINESS_ADDRESS_OPTION ] );
			}
		);

		self::assertStringContainsString(
			'<textarea name="jmc_business_address" id="jmc_business_address" rows="5" class="regular-text" aria-describedby="jmc_business_address-description">1 High Street' . "\n" . 'Smith &amp; Sons</textarea>',
			$html
		);
	}

	/**
	 * The page posts the business group to options.php.
	 */
	public function test_renders_page_form(): void {
		$this->run_closures( 'admin_menu' );

		$html = $this->capture( 'jmc_business_settings_page' );

		self::assertStringContainsString( '<h1>Business settings</h1>', $html );
		self::assertStringContainsString( '<form action="options.php" method="post">', $html );
		self::assertStringContainsString( '<input type="hidden" name="option_page" value="jmc-business-settings" />', $html );
		self::assertStringContainsString( '<!-- sections:jmc-business-settings -->', $html );
		self::assertStringContainsString( 'type="submit"', $html );
	}

	/**
	 * Run the closures hooked onto an action by `inc/contact.php`.
	 *
	 * @param string $hook Action name.
	 *
	 * @return void
	 */
	private function run_closures( string $hook ): void {
		foreach ( $GLOBALS['jmc_test_actions'][ $hook ] ?? [] as [ $callback ] ) {
			if ( $callback instanceof Closure ) {
				$callback();
			}
		}
	}

	/**
	 * Capture a callback's output.
	 *
	 * @param callable $callback Callback that prints.
	 *
	 * @return string The printed output.
	 */
	private function capture( callable $callback ): string {
		ob_start();
		$callback();

		return (string) ob_get_clean();
	}
}
