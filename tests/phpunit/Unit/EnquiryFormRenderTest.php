<?php
/**
 * Enquiry form render tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/enquiry.php';

/**
 * Verify the enquiry form renders accessible fields, tokens and result notices.
 */
final class EnquiryFormRenderTest extends TestCase {

	/**
	 * Start every test with an empty site and no submission result.
	 */
	protected function setUp(): void {
		jmc_test_wp_fixture();
		unset( $_GET[ JMC_ENQUIRY_RESULT_ARG ] );
	}

	/**
	 * Clear the submission result.
	 */
	protected function tearDown(): void {
		unset( $_GET[ JMC_ENQUIRY_RESULT_ARG ] );
	}

	/**
	 * Configure a Services page with two published services.
	 *
	 * @param array<string, mixed> $overrides Extra fixture values.
	 */
	private static function site_with_services( array $overrides = [] ): void {
		$service = static fn ( int $id, string $title ): object => (object) [
			'ID'          => $id,
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_parent' => 10,
			'permalink'   => 'https://example.com/services/' . $id . '/',
		];

		jmc_test_wp_fixture(
			array_merge(
				[
					'pages'         => [
						(object) [
							'ID'          => 10,
							'post_type'   => 'page',
							'post_status' => 'publish',
							'post_name'   => 'services',
						],
					],
					'query_results' => [ $service( 11, 'Rewiring' ), $service( 12, 'EV & solar' ) ],
				],
				$overrides
			)
		);
	}

	/**
	 * Execute the real render template, converting warnings into failures.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Rendered inner blocks.
	 *
	 * @throws \Throwable If rendering fails.
	 *
	 * @return string Rendered markup.
	 */
	private function render( array $attributes = [], string $content = '' ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/layouts/enquiry-form/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Parse rendered markup for querying.
	 *
	 * @param string $html Rendered markup.
	 *
	 * @return \DOMXPath Query object.
	 */
	private static function dom( string $html ): \DOMXPath {
		$document = new \DOMDocument();
		$document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR );

		return new \DOMXPath( $document );
	}

	/**
	 * Every visible control has a label, and required fields are marked.
	 */
	public function test_fields_are_labelled_with_autocomplete(): void {
		self::site_with_services();

		$xpath = self::dom( $this->render() );

		$expected = [
			'name'     => [ 'name', true ],
			'email'    => [ 'email', true ],
			'phone'    => [ 'tel', false ],
			'postcode' => [ 'postal-code', false ],
			'message'  => [ '', true ],
		];

		foreach ( $expected as $key => [ $autocomplete, $required ] ) {
			$control = $xpath->query( "//*[@name='jmc_enquiry[{$key}]']" )->item( 0 );

			self::assertInstanceOf( \DOMElement::class, $control, $key );
			self::assertSame( 1, $xpath->query( "//label[@for='{$control->getAttribute( 'id' )}']" )->length, $key );
			self::assertSame( $autocomplete, $control->getAttribute( 'autocomplete' ), $key );
			self::assertSame( $required, $control->hasAttribute( 'required' ), $key );
			self::assertStringContainsString( "enquiry-form-{$key}-error", $control->getAttribute( 'aria-describedby' ), $key );
		}

		$consent = $xpath->query( "//input[@name='jmc_enquiry[consent]']" )->item( 0 );
		self::assertSame( 'checkbox', $consent->getAttribute( 'type' ) );
		self::assertTrue( $consent->hasAttribute( 'required' ) );
		self::assertSame( 1, $xpath->query( "//label[@for='{$consent->getAttribute( 'id' )}']" )->length );
	}

	/**
	 * The service select lists the Services pages after a "not sure" option.
	 */
	public function test_service_select_lists_services(): void {
		self::site_with_services();

		$xpath   = self::dom( $this->render() );
		$options = $xpath->query( "//select[@name='jmc_enquiry[service]']/option" );

		self::assertSame( 3, $options->length );
		self::assertSame( '', $options->item( 0 )->getAttribute( 'value' ) );
		self::assertSame( 'Rewiring', $options->item( 1 )->getAttribute( 'value' ) );
		self::assertSame( 'EV & solar', $options->item( 2 )->textContent );
	}

	/**
	 * Without any services the select is left out.
	 */
	public function test_service_select_omitted_without_services(): void {
		$xpath = self::dom( $this->render() );

		self::assertSame( 0, $xpath->query( "//select[@name='jmc_enquiry[service]']" )->length );
	}

	/**
	 * The form posts to admin-post with the action, tokens, honeypot and REST endpoints.
	 */
	public function test_form_carries_submission_fields(): void {
		$xpath = self::dom( $this->render() );
		$form  = $xpath->query( '//form' )->item( 0 );

		self::assertSame( 'post', $form->getAttribute( 'method' ) );
		self::assertSame( 'https://example.com/wp-admin/admin-post.php', $form->getAttribute( 'action' ) );
		self::assertSame( 'https://example.com/wp-json/jmc/v1/enquiries', $form->getAttribute( 'data-jmc-enquiry-endpoint' ) );
		self::assertSame( 'https://example.com/wp-json/jmc/v1/enquiries/token', $form->getAttribute( 'data-jmc-enquiry-token-endpoint' ) );
		self::assertFalse( $form->hasAttribute( 'novalidate' ) );

		$hidden = static fn ( string $name ): string => $xpath->query( "//input[@name='{$name}']" )->item( 0 )->getAttribute( 'value' );

		self::assertSame( JMC_ENQUIRY_FORM_ACTION, $hidden( 'action' ) );
		self::assertSame( 'nonce-' . JMC_ENQUIRY_NONCE_ACTION, $hidden( JMC_ENQUIRY_NONCE_FIELD ) );
		self::assertMatchesRegularExpression( '/^\d+\.[a-f0-9]{64}$/', $hidden( JMC_ENQUIRY_TIMING_FIELD ) );
		self::assertSame( 'enquiry-form-notice', $hidden( JMC_ENQUIRY_RETURN_FIELD ) );

		$trap = $xpath->query( "//input[@name='" . JMC_ENQUIRY_HONEYPOT_FIELD . "']" )->item( 0 );
		self::assertSame( '-1', $trap->getAttribute( 'tabindex' ) );
		self::assertSame( 'true', $trap->parentNode->getAttribute( 'aria-hidden' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.

		$messages = json_decode( $form->getAttribute( 'data-jmc-enquiry-messages' ), true );
		self::assertArrayHasKey( 'network', $messages );
		self::assertArrayHasKey( 'invalid', $messages );
	}

	/**
	 * An anchor names the section and prefixes every ID.
	 */
	public function test_anchor_prefixes_ids(): void {
		$xpath = self::dom( $this->render( [ 'anchor' => 'contact' ] ) );

		self::assertSame( 1, $xpath->query( "//section[@id='contact']" )->length );
		self::assertSame( 1, $xpath->query( "//input[@id='contact-email']" )->length );
		self::assertSame( 1, $xpath->query( "//*[@id='contact-notice']" )->length );
	}

	/**
	 * Palettes map to classes; anything else falls back to the default.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param mixed       $palette  Palette attribute.
	 * @param string|null $expected Palette class, or null for none.
	 */
	public function test_palette( mixed $palette, ?string $expected ): void {
		$html = $this->render( [ 'palette' => $palette ] );

		foreach ( [ 'jmc-palette--secondary', 'jmc-palette--inverse' ] as $class ) {
			self::assertSame( $class === $expected, str_contains( $html, $class ), $class );
		}
	}

	/**
	 * Palette attributes and their classes.
	 *
	 * @return array<string, array{mixed, string|null}>
	 */
	public function palette_provider(): array {
		return [
			'omitted'   => [ null, null ],
			'default'   => [ 'default', null ],
			'secondary' => [ 'secondary', 'jmc-palette--secondary' ],
			'inverse'   => [ 'inverse', 'jmc-palette--inverse' ],
			'malformed' => [ 'neon"><script>', null ],
		];
	}

	/**
	 * The submit button text is editable, with a default when empty.
	 */
	public function test_submit_text(): void {
		self::assertStringContainsString( '>Send enquiry</button>', $this->render() );
		self::assertStringContainsString( '>Get a quote</button>', $this->render( [ 'submitText' => 'Get a quote' ] ) );
		self::assertStringContainsString( '>Send enquiry</button>', $this->render( [ 'submitText' => '<b></b>' ] ) );
	}

	/**
	 * The introduction column only renders when it has content.
	 */
	public function test_intro_column(): void {
		self::assertStringNotContainsString( 'jmc-enquiry-form__intro', $this->render() );
		self::assertStringContainsString( '<p>Tell us about your project.</p>', $this->render( [], '<p>Tell us about your project.</p>' ) );
	}

	/**
	 * With no result the notice is hidden and the form shown.
	 */
	public function test_notice_hidden_by_default(): void {
		$xpath  = self::dom( $this->render() );
		$notice = $xpath->query( "//*[@id='enquiry-form-notice']" )->item( 0 );

		self::assertTrue( $notice->hasAttribute( 'hidden' ) );
		self::assertSame( '-1', $notice->getAttribute( 'tabindex' ) );
		self::assertSame( 1, $xpath->query( '//form' )->length );
	}

	/**
	 * After a submission without JavaScript the result is shown in the notice.
	 *
	 * @dataProvider result_provider
	 *
	 * @param string $result           Result query value.
	 * @param string $modifier         Expected notice modifier class.
	 * @param bool   $shows_form       Whether the form is shown.
	 * @param bool   $shows_contact    Whether the phone and email alternatives are shown.
	 */
	public function test_result_notice( string $result, string $modifier, bool $shows_form, bool $shows_contact ): void {
		jmc_test_wp_fixture(
			[
				'options' => [
					JMC_BUSINESS_PHONE_OPTION => '01234 567890',
					JMC_BUSINESS_EMAIL_OPTION => 'hello@example.com',
				],
			]
		);
		$_GET[ JMC_ENQUIRY_RESULT_ARG ] = $result;

		$xpath  = self::dom( $this->render() );
		$notice = $xpath->query( "//*[@id='enquiry-form-notice']" )->item( 0 );

		self::assertFalse( $notice->hasAttribute( 'hidden' ) );
		self::assertStringContainsString( $modifier, $notice->getAttribute( 'class' ) );
		self::assertSame( jmc_enquiry_status_messages()[ $result ], trim( $notice->getElementsByTagName( 'p' )->item( 0 )->textContent ) );
		self::assertSame( $shows_form ? 1 : 0, $xpath->query( '//form' )->length );

		$alternative = $xpath->query( "//*[contains(@class,'jmc-enquiry-form__alternative')]" )->item( 0 );
		self::assertSame( ! $shows_contact, $alternative->hasAttribute( 'hidden' ) );
		self::assertSame( 1, $xpath->query( "//a[@href='tel:01234567890']" )->length );
	}

	/**
	 * Result values and how they are shown.
	 *
	 * @return array<string, array{string, string, bool, bool}>
	 */
	public function result_provider(): array {
		return [
			'sent'         => [ 'sent', 'jmc-enquiry-form__notice--success', false, false ],
			'invalid'      => [ 'invalid', 'jmc-enquiry-form__notice--error', true, false ],
			'expired'      => [ 'expired', 'jmc-enquiry-form__notice--error', true, false ],
			'rate limited' => [ 'rate_limited', 'jmc-enquiry-form__notice--error', true, true ],
			'error'        => [ 'error', 'jmc-enquiry-form__notice--error', true, true ],
		];
	}

	/**
	 * Unknown or malformed result values are ignored.
	 *
	 * @dataProvider unknown_result_provider
	 *
	 * @param mixed $result Result query value.
	 */
	public function test_unknown_result_is_ignored( mixed $result ): void {
		$_GET[ JMC_ENQUIRY_RESULT_ARG ] = $result;

		$xpath = self::dom( $this->render() );

		self::assertTrue( $xpath->query( "//*[@id='enquiry-form-notice']" )->item( 0 )->hasAttribute( 'hidden' ) );
		self::assertSame( 1, $xpath->query( '//form' )->length );
	}

	/**
	 * Result values that aren't statuses.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function unknown_result_provider(): array {
		return [
			'unknown' => [ 'hacked' ],
			'markup'  => [ '<script>alert(1)</script>' ],
			'array'   => [ [ 'sent' ] ],
			'outcome' => [ 'spam' ],
		];
	}

	/**
	 * A privacy policy link is shown beside the consent checkbox when one is set.
	 */
	public function test_privacy_link(): void {
		self::assertStringNotContainsString( 'privacy', $this->render() );

		jmc_test_wp_fixture( [ 'privacy_url' => 'https://example.com/privacy/' ] );

		$xpath = self::dom( $this->render() );
		$link  = $xpath->query( "//a[@href='https://example.com/privacy/']" );

		self::assertSame( 1, $link->length );
		self::assertSame( 'privacy policy', $link->item( 0 )->textContent );
		self::assertSame(
			'Read how we use and store your details in our privacy policy.',
			trim( $link->item( 0 )->parentNode->textContent ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOM property.
		);
	}
}
