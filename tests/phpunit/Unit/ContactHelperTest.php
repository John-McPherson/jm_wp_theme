<?php
/**
 * Business contact helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/contact.php';

/**
 * Verify the site-wide business contact details are sanitised and linked safely.
 */
final class ContactHelperTest extends TestCase {

	/**
	 * Reset the option fixtures between tests.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_options'] );
	}

	/**
	 * Diallable numbers are kept as written, with whitespace collapsed.
	 *
	 * @dataProvider phone_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 */
	public function test_sanitize_business_phone( mixed $value, string $expected ): void {
		self::assertSame( $expected, jmc_sanitize_business_phone( $value ) );
	}

	/**
	 * Submitted phone numbers and their stored values.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function phone_provider(): array {
		return [
			'local number'         => [ '01234 567890', '01234 567890' ],
			'international format' => [ '+44 (0)1234 567-890', '+44 (0)1234 567-890' ],
			'extra whitespace'     => [ "  01234\t 567890 ", '01234 567890' ],
			'empty'                => [ '', '' ],
			'letters'              => [ '0800 ELECTRIC', '' ],
			'too short'            => [ '12', '' ],
			'markup'               => [ '<b>01234 567890</b>', '' ],
			'non-string'           => [ 1234567890, '' ],
			'null'                 => [ null, '' ],
		];
	}

	/**
	 * Valid addresses are kept; anything else is cleared.
	 *
	 * @dataProvider email_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 */
	public function test_sanitize_business_email( mixed $value, string $expected ): void {
		self::assertSame( $expected, jmc_sanitize_business_email( $value ) );
	}

	/**
	 * Submitted email addresses and their stored values.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function email_provider(): array {
		return [
			'address'           => [ 'hello@example.com', 'hello@example.com' ],
			'surrounding space' => [ ' hello@example.com ', 'hello@example.com' ],
			'empty'             => [ '', '' ],
			'missing domain'    => [ 'hello@', '' ],
			'mailto prefix'     => [ 'mailto:hello@example.com', '' ],
			'two addresses'     => [ 'a@example.com,b@example.com', '' ],
			'non-string'        => [ [ 'hello@example.com' ], '' ],
		];
	}

	/**
	 * Stored details become normalised telephone and email links.
	 */
	public function test_contact_url_links_stored_details(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION => '+44 (0)1234 567-890',
			JMC_BUSINESS_EMAIL_OPTION => 'hello@example.com',
		];

		self::assertSame( 'tel:+4401234567890', jmc_business_contact_url( 'phone' ) );
		self::assertSame( 'mailto:hello@example.com', jmc_business_contact_url( 'email' ) );
	}

	/**
	 * Missing, invalid or unknown details produce no link.
	 */
	public function test_contact_url_is_empty_without_valid_details(): void {
		self::assertSame( '', jmc_business_contact_url( 'phone' ) );
		self::assertSame( '', jmc_business_contact_url( 'email' ) );

		// Stored values are re-validated, in case they were written around the sanitiser.
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION => 'javascript:alert(1)',
			JMC_BUSINESS_EMAIL_OPTION => 'hello@example.com?bcc=x@example.com',
		];

		self::assertSame( '', jmc_business_contact_url( 'phone' ) );
		self::assertSame( '', jmc_business_contact_url( 'email' ) );
		self::assertSame( '', jmc_business_contact_url( 'fax' ) );
	}
}
