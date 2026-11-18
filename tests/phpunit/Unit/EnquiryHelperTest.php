<?php
/**
 * Enquiry helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Verify enquiry submissions are validated, cleaned and screened for spam.
 */
final class EnquiryHelperTest extends TestCase {

	private const KEY = 'test-signing-key';

	private const NOW = 1893456000;

	private const SERVICES = [ 'EV charger installation', 'Rewiring' ];

	/**
	 * A complete, valid submission.
	 *
	 * @return array<string,string>
	 */
	private static function valid_input(): array {
		return [
			'name'     => 'Alex Smith',
			'email'    => 'alex@example.com',
			'phone'    => '01234 567890',
			'postcode' => 'sw1a1aa',
			'service'  => 'Rewiring',
			'message'  => 'Please quote for rewiring a two-bed flat.',
			'consent'  => '1',
		];
	}

	/**
	 * Request fields from a person who took a minute to fill in the form.
	 *
	 * @param array<string,mixed> $overrides Fields to replace.
	 *
	 * @return array<string,mixed>
	 */
	private static function genuine_post( array $overrides = [] ): array {
		return array_replace(
			[
				JMC_ENQUIRY_HONEYPOT_FIELD => '',
				JMC_ENQUIRY_TIMING_FIELD   => jmc_enquiry_timing_token( self::NOW - 60, self::KEY ),
			],
			$overrides
		);
	}

	/**
	 * A valid submission has no errors and is normalised.
	 */
	public function test_valid_submission(): void {
		$result = jmc_validate_enquiry( self::valid_input(), self::SERVICES );

		self::assertSame( [], $result['errors'] );
		self::assertSame( 'SW1A 1AA', $result['values']['postcode'] );
		self::assertSame( 'Alex Smith', $result['values']['name'] );
		self::assertSame( '1', $result['values']['consent'] );
	}

	/**
	 * Optional fields can be left blank.
	 */
	public function test_optional_fields_can_be_blank(): void {
		$input = array_replace(
			self::valid_input(),
			[
				'phone'    => '',
				'postcode' => '',
				'service'  => '',
			]
		);

		self::assertSame( [], jmc_validate_enquiry( $input, self::SERVICES )['errors'] );
	}

	/**
	 * An empty or malformed submission reports every required field, in form order.
	 *
	 * @dataProvider empty_input_provider
	 *
	 * @param mixed $input Submitted value.
	 */
	public function test_empty_submission_reports_required_fields( mixed $input ): void {
		$result = jmc_validate_enquiry( $input, self::SERVICES );

		self::assertSame(
			[ 'name', 'email', 'message', 'consent' ],
			array_keys( $result['errors'] )
		);
		self::assertSame( array_keys( jmc_enquiry_fields() ), array_keys( $result['values'] ) );
	}

	/**
	 * Submissions with nothing usable in them.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function empty_input_provider(): array {
		return [
			'empty array'     => [ [] ],
			'not an array'    => [ 'name=Alex' ],
			'null'            => [ null ],
			'non-string data' => [
				[
					'name'    => [ 'Alex' ],
					'email'   => 42,
					'message' => false,
					'consent' => true,
				],
			],
			'markup only'     => [
				[
					'name'    => '<b></b>',
					'message' => '<script></script>',
				],
			],
		];
	}

	/**
	 * Invalid values produce an error on the right field.
	 *
	 * @dataProvider invalid_field_provider
	 *
	 * @param string $field Field ID.
	 * @param mixed  $value Submitted value.
	 */
	public function test_invalid_field( string $field, mixed $value ): void {
		$input           = self::valid_input();
		$input[ $field ] = $value;

		$errors = jmc_validate_enquiry( $input, self::SERVICES )['errors'];

		self::assertSame( [ $field ], array_keys( $errors ) );
		self::assertNotSame( '', $errors[ $field ] );
	}

	/**
	 * Fields and invalid values for them.
	 *
	 * @return array<string, array{string, mixed}>
	 */
	public function invalid_field_provider(): array {
		return [
			'email missing domain' => [ 'email', 'alex@' ],
			'email with mailto'    => [ 'email', 'mailto:alex@example.com' ],
			'phone with letters'   => [ 'phone', '0800 ELECTRIC' ],
			'postcode partial'     => [ 'postcode', 'SW1A' ],
			'postcode not UK'      => [ 'postcode', '90210' ],
			'service not offered'  => [ 'service', 'Plumbing' ],
			'consent not ticked'   => [ 'consent', '' ],
			'consent wrong value'  => [ 'consent', 'yes' ],
			'name too long'        => [ 'name', str_repeat( 'a', 101 ) ],
			'message too long'     => [ 'message', str_repeat( 'a', 5001 ) ],
		];
	}

	/**
	 * Markup and control characters are removed; message line breaks are kept.
	 */
	public function test_values_are_cleaned(): void {
		$input            = self::valid_input();
		$input['name']    = "  <b>Alex</b>\t\x00Smith ";
		$input['message'] = "Line one<script>x</script>\r\n\r\n\r\n\r\n  Line two  ";

		$values = jmc_validate_enquiry( $input, self::SERVICES )['values'];

		self::assertSame( 'Alex Smith', $values['name'] );
		self::assertSame( "Line onex\n\nLine two", $values['message'] );
	}

	/**
	 * UK postcodes are normalised; anything else is rejected.
	 *
	 * @dataProvider postcode_provider
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $expected Normalised postcode.
	 */
	public function test_postcode( mixed $value, string $expected ): void {
		self::assertSame( $expected, jmc_enquiry_postcode( $value ) );
	}

	/**
	 * Submitted postcodes and their normalised form.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function postcode_provider(): array {
		return [
			'spaced'       => [ 'SW1A 1AA', 'SW1A 1AA' ],
			'lower, tight' => [ 'm11ae', 'M1 1AE' ],
			'extra spaces' => [ ' B33  8TH ', 'B33 8TH' ],
			'outward only' => [ 'SW1A', '' ],
			'zip code'     => [ '90210', '' ],
			'non-string'   => [ 12345, '' ],
		];
	}

	/**
	 * A genuine submission passes the spam checks.
	 */
	public function test_genuine_submission_is_not_spam(): void {
		self::assertSame(
			'',
			jmc_enquiry_spam_reason( self::genuine_post(), 'See https://example.com/photo', self::NOW, self::KEY )
		);
	}

	/**
	 * Spam-like submissions are rejected with the reason.
	 *
	 * @dataProvider spam_provider
	 *
	 * @param array<string,mixed> $post     Request fields.
	 * @param string              $message  Cleaned message.
	 * @param string              $expected Rejection reason.
	 */
	public function test_spam_reason( array $post, string $message, string $expected ): void {
		self::assertSame( $expected, jmc_enquiry_spam_reason( $post, $message, self::NOW, self::KEY ) );
	}

	/**
	 * Spam-like request fields and the reason they are rejected.
	 *
	 * @return array<string, array{array<string,mixed>, string, string}>
	 */
	public function spam_provider(): array {
		$signed = static fn ( int $time ): string => jmc_enquiry_timing_token( $time, self::KEY );

		return [
			'honeypot filled'     => [ self::genuine_post( [ JMC_ENQUIRY_HONEYPOT_FIELD => 'https://spam.test' ] ), '', 'honeypot' ],
			'honeypot non-string' => [ self::genuine_post( [ JMC_ENQUIRY_HONEYPOT_FIELD => [ 'x' ] ] ), '', 'honeypot' ],
			'timing missing'      => [ [ JMC_ENQUIRY_HONEYPOT_FIELD => '' ], '', 'timing' ],
			'timing unsigned'     => [ self::genuine_post( [ JMC_ENQUIRY_TIMING_FIELD => (string) ( self::NOW - 60 ) ] ), '', 'timing' ],
			'timing forged'       => [ self::genuine_post( [ JMC_ENQUIRY_TIMING_FIELD => jmc_enquiry_timing_token( self::NOW - 60, 'wrong-key' ) ] ), '', 'timing' ],
			'timing time altered' => [ self::genuine_post( [ JMC_ENQUIRY_TIMING_FIELD => ( self::NOW - 600 ) . substr( $signed( self::NOW - 60 ), 10 ) ] ), '', 'timing' ],
			'submitted too fast'  => [ self::genuine_post( [ JMC_ENQUIRY_TIMING_FIELD => $signed( self::NOW - 1 ) ] ), '', 'too_fast' ],
			'form expired'        => [ self::genuine_post( [ JMC_ENQUIRY_TIMING_FIELD => $signed( self::NOW - JMC_ENQUIRY_MAX_SECONDS - 1 ) ] ), '', 'expired' ],
			'too many links'      => [ self::genuine_post(), 'http://a.test https://b.test www.c.test', 'links' ],
		];
	}

	/**
	 * Resubmitting the same enquiry gives the same fingerprint, ignoring case and spacing.
	 */
	public function test_fingerprint_matches_duplicates(): void {
		$original = jmc_enquiry_fingerprint(
			[
				'email'   => 'alex@example.com',
				'message' => 'Rewire my flat',
			]
		);

		self::assertSame(
			$original,
			jmc_enquiry_fingerprint(
				[
					'email'   => 'ALEX@example.com ',
					'message' => "Rewire  my\nflat",
				]
			)
		);
		self::assertNotSame(
			$original,
			jmc_enquiry_fingerprint(
				[
					'email'   => 'alex@example.com',
					'message' => 'Rewire my house',
				]
			)
		);
	}

	/**
	 * The rate-limit key never contains the IP address.
	 */
	public function test_rate_key_hides_ip(): void {
		$key = jmc_enquiry_rate_key( '203.0.113.7', self::KEY );

		self::assertStringNotContainsString( '203.0.113.7', $key );
		self::assertSame( $key, jmc_enquiry_rate_key( '203.0.113.7', self::KEY ) );
		self::assertNotSame( $key, jmc_enquiry_rate_key( '203.0.113.8', self::KEY ) );
	}

	/**
	 * The notification email lists every field and replies go to the enquirer.
	 */
	public function test_email_lists_enquiry_and_replies_to_enquirer(): void {
		$values = jmc_validate_enquiry( self::valid_input(), self::SERVICES )['values'];
		$email  = jmc_enquiry_email( $values, 'AMC Electrical', 'https://example.com/wp-admin/post.php?post=7&action=edit' );

		self::assertSame( '[AMC Electrical] New enquiry from Alex Smith', $email['subject'] );
		self::assertStringContainsString( 'Email address: alex@example.com', $email['body'] );
		self::assertStringContainsString( 'Phone number: 01234 567890', $email['body'] );
		self::assertStringContainsString( 'Postcode: SW1A 1AA', $email['body'] );
		self::assertStringContainsString( 'Service: Rewiring', $email['body'] );
		self::assertStringContainsString( "Message:\nPlease quote for rewiring a two-bed flat.", $email['body'] );
		self::assertStringContainsString( 'post.php?post=7&action=edit', $email['body'] );
		self::assertContains( 'Reply-To: "Alex Smith" <alex@example.com>', $email['headers'] );
		self::assertContains( 'Content-Type: text/plain; charset=UTF-8', $email['headers'] );
	}

	/**
	 * Optional fields left blank are labelled rather than left empty.
	 */
	public function test_email_labels_blank_optional_fields(): void {
		$email = jmc_enquiry_email(
			[
				'name'  => 'Alex',
				'email' => 'alex@example.com',
			],
			'Site',
			''
		);

		self::assertStringContainsString( 'Phone number: Not given', $email['body'] );
		self::assertStringContainsString( 'Postcode: Not given', $email['body'] );
		self::assertStringContainsString( 'Service: Not specified', $email['body'] );
	}

	/**
	 * Characters with meaning in an address header are removed from the display name,
	 * and an invalid address gets no Reply-To header at all.
	 *
	 * @dataProvider reply_to_provider
	 *
	 * @param string      $name     Enquirer's name.
	 * @param string      $address  Enquirer's email address.
	 * @param string|null $expected Expected Reply-To header, or null for none.
	 */
	public function test_email_reply_to_header( string $name, string $address, ?string $expected ): void {
		$headers  = jmc_enquiry_email(
			[
				'name'  => $name,
				'email' => $address,
			],
			'Site',
			''
		)['headers'];
		$reply_to = array_values( array_filter( $headers, static fn ( string $header ): bool => str_starts_with( $header, 'Reply-To:' ) ) );

		self::assertSame( null === $expected ? [] : [ $expected ], $reply_to );
	}

	/**
	 * Names and addresses and the Reply-To header they produce.
	 *
	 * @return array<string, array{string, string, string|null}>
	 */
	public function reply_to_provider(): array {
		return [
			'plain name'          => [ 'Alex Smith', 'alex@example.com', 'Reply-To: "Alex Smith" <alex@example.com>' ],
			'header injection'    => [ "Alex\r\nBcc: spam@example.com", 'alex@example.com', 'Reply-To: "AlexBcc spam@example.com" <alex@example.com>' ],
			'quotes and brackets' => [ 'Alex "<x>", Smith', 'alex@example.com', 'Reply-To: "Alex x Smith" <alex@example.com>' ],
			'empty name'          => [ '', 'alex@example.com', 'Reply-To: alex@example.com' ],
			'invalid address'     => [ 'Alex', "alex@example.com\r\nBcc: x@y.z", null ],
		];
	}

	/**
	 * Every delivery status has a label.
	 */
	public function test_statuses(): void {
		self::assertSame( [ 'accepted', 'sent', 'failed' ], array_keys( jmc_enquiry_statuses() ) );
	}
}
