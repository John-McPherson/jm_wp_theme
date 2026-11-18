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
	 * The business name is stored as one line of plain text.
	 *
	 * @dataProvider name_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 */
	public function test_sanitize_business_name( mixed $value, string $expected ): void {
		self::assertSame( $expected, jmc_sanitize_business_name( $value ) );
	}

	/**
	 * Submitted business names and their stored values.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function name_provider(): array {
		return [
			'plain'            => [ 'Example Electrical Ltd', 'Example Electrical Ltd' ],
			'extra whitespace' => [ "  Example \t Electrical\n Ltd ", 'Example Electrical Ltd' ],
			'line break'       => [ 'Example<br>Electrical', 'Example Electrical' ],
			'markup'           => [ '<b>Smith</b> &amp; Sons', 'Smith & Sons' ],
			'blank'            => [ " <br>\n ", '' ],
			'empty'            => [ '', '' ],
			'non-string'       => [ [ 'Example' ], '' ],
			'null'             => [ null, '' ],
		];
	}

	/**
	 * The business address is stored as plain-text lines.
	 *
	 * @dataProvider address_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 */
	public function test_sanitize_business_address( mixed $value, string $expected ): void {
		self::assertSame( $expected, jmc_sanitize_business_address( $value ) );
	}

	/**
	 * Submitted business addresses and their stored values.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function address_provider(): array {
		return [
			'lines'            => [ "1 High Street\nExample Town\nAB1 2CD", "1 High Street\nExample Town\nAB1 2CD" ],
			'windows newlines' => [ "1 High Street\r\nExample Town", "1 High Street\nExample Town" ],
			'extra whitespace' => [ "  1  High\tStreet \n\n\n Example Town ", "1 High Street\nExample Town" ],
			'rich text'        => [ '<strong>1</strong> High Street<br />Smith &amp; Sons', "1 High Street\nSmith & Sons" ],
			'blank'            => [ " <br>\n ", '' ],
			'non-string'       => [ 12345, '' ],
			'null'             => [ null, '' ],
		];
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
	 * International numbers are kept as written; anything else is cleared.
	 *
	 * @dataProvider whatsapp_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Stored value.
	 * @param string $url      Resulting WhatsApp link.
	 */
	public function test_sanitize_business_whatsapp( mixed $value, string $expected, string $url ): void {
		self::assertSame( $expected, jmc_sanitize_business_whatsapp( $value ) );
		self::assertSame( $url, jmc_whatsapp_url( $expected ) );
	}

	/**
	 * Submitted WhatsApp numbers, their stored values and links.
	 *
	 * @return array<string, array{mixed, string, string}>
	 */
	public function whatsapp_provider(): array {
		return [
			'plus and spaces'     => [ '+44 7700 900123', '+44 7700 900123', 'https://wa.me/447700900123' ],
			'digits only'         => [ '447700900123', '447700900123', 'https://wa.me/447700900123' ],
			'separators'          => [ "  +44 (7700) 900-123\t", '+44 (7700) 900-123', 'https://wa.me/447700900123' ],
			'local, leading zero' => [ '07700 900123', '', '' ],
			'international 00'    => [ '0044 7700 900123', '', '' ],
			'too short'           => [ '+44 1234', '', '' ],
			'too long'            => [ '+44 1234 5678 9012 345', '', '' ],
			'letters'             => [ '+44 7700 WHATSAPP', '', '' ],
			'wa.me link'          => [ 'https://wa.me/447700900123', '', '' ],
			'empty'               => [ '', '', '' ],
			'non-string'          => [ 447700900123, '', '' ],
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
	 * Complete, valid days are kept in week order; anything else is dropped as closed.
	 */
	public function test_sanitize_business_hours(): void {
		$submitted = [
			'saturday'  => [
				'opens'  => '09:00',
				'closes' => '12:30',
			],
			'monday'    => [
				'opens'  => ' 08:00 ',
				'closes' => '17:30',
			],
			'tuesday'   => [
				'opens'  => '',
				'closes' => '',
			],
			'wednesday' => [ 'opens' => '08:00' ],
			'thursday'  => [
				'opens'  => '17:00',
				'closes' => '08:00',
			],
			'friday'    => [
				'opens'  => '8:00',
				'closes' => '24:00',
			],
			'sunday'    => [
				'opens'  => '<b>09:00</b>',
				'closes' => '12:00',
			],
			'funday'    => [
				'opens'  => '09:00',
				'closes' => '17:00',
			],
		];

		self::assertSame(
			[
				'monday'   => [
					'opens'  => '08:00',
					'closes' => '17:30',
				],
				'saturday' => [
					'opens'  => '09:00',
					'closes' => '12:30',
				],
			],
			jmc_sanitize_business_hours( $submitted )
		);
	}

	/**
	 * Malformed opening-hours submissions store no hours.
	 *
	 * @dataProvider malformed_hours_provider
	 *
	 * @param mixed $value Submitted value.
	 */
	public function test_sanitize_business_hours_rejects_malformed_values( mixed $value ): void {
		self::assertSame( [], jmc_sanitize_business_hours( $value ) );
	}

	/**
	 * Malformed opening-hours submissions.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_hours_provider(): array {
		return [
			'null'          => [ null ],
			'string'        => [ 'Mon–Fri 8–5' ],
			'empty'         => [ [] ],
			'day as string' => [ [ 'monday' => '08:00-17:00' ] ],
			'list of times' => [ [ [ '08:00', '17:00' ] ] ],
			'numeric times' => [
				[
					'monday' => [
						'opens'  => 800,
						'closes' => 1700,
					],
				],
			],
			'same time'     => [
				[
					'monday' => [
						'opens'  => '09:00',
						'closes' => '09:00',
					],
				],
			],
		];
	}

	/**
	 * Places are trimmed, de-duplicated and kept in order; invalid entries are dropped.
	 *
	 * @dataProvider service_area_provider
	 *
	 * @param mixed    $value    Submitted value.
	 * @param string[] $expected Stored places.
	 */
	public function test_sanitize_business_service_area( mixed $value, array $expected ): void {
		self::assertSame( $expected, jmc_sanitize_business_service_area( $value ) );
	}

	/**
	 * Submitted service areas and their stored places.
	 *
	 * @return array<string, array{mixed, list<string>}>
	 */
	public function service_area_provider(): array {
		return [
			'textarea lines'       => [ "Glasgow\r\n  East   Kilbride \n\nPaisley\n", [ 'Glasgow', 'East Kilbride', 'Paisley' ] ],
			'list'                 => [ [ 'Glasgow', 'Paisley' ], [ 'Glasgow', 'Paisley' ] ],
			'case-insensitive dup' => [ "Glasgow\nglasgow\nGLASGOW", [ 'Glasgow' ] ],
			'markup'               => [ "Glasgow\n<script>alert(1)</script>", [ 'Glasgow' ] ],
			'too long'             => [ [ str_repeat( 'a', 101 ), 'Paisley' ], [ 'Paisley' ] ],
			'non-string entries'   => [ [ 'Glasgow', 42, [ 'Paisley' ], null ], [ 'Glasgow' ] ],
			'empty'                => [ '', [] ],
			'null'                 => [ null, [] ],
		];
	}

	/**
	 * Pre-filled messages are encoded into email and WhatsApp links only.
	 *
	 * @dataProvider message_provider
	 *
	 * @param string               $url      Contact link.
	 * @param array<string, mixed> $message  Message parts.
	 * @param string               $expected Resulting link.
	 */
	public function test_contact_url_with_message( string $url, array $message, string $expected ): void {
		self::assertSame( $expected, jmc_contact_url_with_message( url: $url, message: $message ) );
	}

	/**
	 * Contact links, messages and the links they produce.
	 *
	 * @return array<string, array{string, array<string, mixed>, string}>
	 */
	public function message_provider(): array {
		$email    = 'mailto:hello@example.com';
		$whatsapp = 'https://wa.me/447700900123';

		return [
			'email subject and body'   => [
				$email,
				[
					'subject' => 'Quote request',
					'body'    => 'Hi, I need a rewire & a new consumer unit?',
				],
				$email . '?subject=Quote%20request&body=Hi%2C%20I%20need%20a%20rewire%20%26%20a%20new%20consumer%20unit%3F',
			],
			'email subject only'       => [ $email, [ 'subject' => 'Quote' ], $email . '?subject=Quote' ],
			'email ignores text'       => [ $email, [ 'text' => 'Hello' ], $email ],
			'whatsapp text'            => [ $whatsapp, [ 'text' => 'Hi! Can you call me?' ], $whatsapp . '?text=Hi%21%20Can%20you%20call%20me%3F' ],
			'whatsapp ignores subject' => [ $whatsapp, [ 'subject' => 'Quote' ], $whatsapp ],
			'whatsapp line breaks'     => [ $whatsapp, [ 'text' => "Hi,\r\nCan you  call?\n\n\n\nThanks" ], $whatsapp . '?text=Hi%2C%0ACan%20you%20call%3F%0A%0AThanks' ],
			'email body line breaks'   => [ $email, [ 'body' => "Hi,\nThanks" ], $email . '?body=Hi%2C%0D%0AThanks' ],
			'subject is one line'      => [ $email, [ 'subject' => "Quote\nrequest" ], $email . '?subject=Quote%20request' ],
			'markup is encoded'        => [ $whatsapp, [ 'text' => '<b>Hi</b>' ], $whatsapp . '?text=%3Cb%3EHi%3C%2Fb%3E' ],
			'blank message'            => [
				$email,
				[
					'subject' => " \n ",
					'body'    => '',
				],
				$email,
			],
			'non-string parts'         => [
				$email,
				[
					'subject' => [ 'Quote' ],
					'body'    => 42,
				],
				$email,
			],
			'phone link'               => [ 'tel:01234567890', [ 'text' => 'Hello' ], 'tel:01234567890' ],
			'no link'                  => [ '', [ 'subject' => 'Quote' ], '' ],
		];
	}

	/**
	 * Stored times are formatted with the site's time format, defaulting to `g:i a`.
	 */
	public function test_format_business_time(): void {
		self::assertSame( '8:05 am', jmc_format_business_time( '08:05' ) );
		self::assertSame( '', jmc_format_business_time( '8:05' ) );

		$GLOBALS['jmc_test_options'] = [ 'time_format' => 'H:i' ];

		self::assertSame( '17:30', jmc_format_business_time( '17:30' ) );
	}

	/**
	 * Contact details are validated for display, with every day listed once any day is open.
	 */
	public function test_contact_details(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION        => '01234 567890',
			JMC_BUSINESS_EMAIL_OPTION        => 'not-an-email',
			JMC_BUSINESS_WHATSAPP_OPTION     => '+44 7700 900123',
			JMC_BUSINESS_HOURS_OPTION        => [
				'friday' => [
					'opens'  => '08:00',
					'closes' => '16:00',
				],
			],
			JMC_BUSINESS_SERVICE_AREA_OPTION => "Glasgow\nPaisley",
		];

		$details = jmc_business_contact_details();

		self::assertSame( '01234 567890', $details['phone'] );
		self::assertSame( 'tel:01234567890', $details['phone_url'] );
		self::assertSame( '', $details['email'] );
		self::assertSame( '', $details['email_url'] );
		self::assertSame( '+44 7700 900123', $details['whatsapp'] );
		self::assertSame( 'https://wa.me/447700900123', $details['whatsapp_url'] );
		self::assertSame( JMC_BUSINESS_DAYS, array_keys( $details['hours'] ) );
		self::assertNull( $details['hours']['monday'] );
		self::assertSame(
			[
				'opens'  => '08:00',
				'closes' => '16:00',
			],
			$details['hours']['friday']
		);
		self::assertSame( [ 'Glasgow', 'Paisley' ], $details['service_area'] );
	}

	/**
	 * Without any open day, no opening hours are returned.
	 */
	public function test_contact_details_without_hours(): void {
		self::assertSame( [], jmc_business_contact_details()['hours'] );
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
