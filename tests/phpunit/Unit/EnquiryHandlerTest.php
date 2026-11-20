<?php
/**
 * Enquiry submission handler tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Verify each submission path stores, sends and reports the right thing.
 */
final class EnquiryHandlerTest extends TestCase {

	private const KEY = 'test-signing-key';

	private const NOW = 1893456000;

	private const LINKS_MESSAGE = 'Photos: https://example.com/1 https://example.com/2 https://example.com/3';

	/**
	 * Values passed to the store callable.
	 *
	 * @var array<int, array<string,string>>
	 */
	private array $stored = [];

	/**
	 * Spam reasons passed to the store callable, in the same order as `$stored`.
	 *
	 * @var string[]
	 */
	private array $stored_reasons = [];

	/**
	 * IDs passed to the send callable.
	 *
	 * @var int[]
	 */
	private array $sent = [];

	/**
	 * Request fields for a genuine, valid submission.
	 *
	 * @param array<string,mixed> $overrides Fields to replace.
	 *
	 * @return array<string,mixed>
	 */
	private static function post( array $overrides = [] ): array {
		return array_replace(
			[
				'jmc_enquiry'              => [
					'name'     => 'Alex Smith',
					'email'    => 'alex@example.com',
					'postcode' => 'SW1A 1AA',
					'service'  => 'Rewiring',
					'message'  => 'Please quote for rewiring a two-bed flat.',
					'consent'  => '1',
				],
				JMC_ENQUIRY_HONEYPOT_FIELD => '',
				JMC_ENQUIRY_TIMING_FIELD   => jmc_enquiry_timing_token( self::NOW - 60, self::KEY ),
			],
			$overrides
		);
	}

	/**
	 * Run the handler with controllable dependencies.
	 *
	 * @param array<string,mixed> $post        Request fields.
	 * @param bool                $over_limit  Whether the visitor is over the rate limit.
	 * @param bool                $repeat      Whether the enquiry is a duplicate.
	 * @param int                 $stored_id   ID the store returns.
	 * @param bool                $send_result Whether sending succeeds.
	 *
	 * @return array<string,mixed> Handler result.
	 */
	private function handle(
		array $post,
		bool $over_limit = false,
		bool $repeat = false,
		int $stored_id = 7,
		bool $send_result = true
	): array {
		return jmc_handle_enquiry(
			post: $post,
			services: [ 'Rewiring' ],
			now: self::NOW,
			key: self::KEY,
			hit_limit: static fn (): bool => $over_limit,
			is_repeat: static fn ( string $fingerprint ): bool => $repeat && '' !== $fingerprint,
			store: function ( array $values, string $spam_reason ) use ( $stored_id ): int {
				$this->stored[]         = $values;
				$this->stored_reasons[] = $spam_reason;

				return $stored_id;
			},
			send: function ( int $post_id ) use ( $send_result ): bool {
				$this->sent[] = $post_id;

				return $send_result;
			}
		);
	}

	/**
	 * A valid submission is stored, sent and reported as sent.
	 */
	public function test_valid_submission_is_stored_and_sent(): void {
		$result = $this->handle( self::post() );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'sent', $result['outcome'] );
		self::assertSame( [], $result['errors'] );
		self::assertCount( 1, $this->stored );
		self::assertSame( 'SW1A 1AA', $this->stored[0]['postcode'] );
		self::assertSame( [ '' ], $this->stored_reasons );
		self::assertSame( [ 7 ], $this->sent );
	}

	/**
	 * An invalid submission is neither stored nor sent and reports its errors and values.
	 */
	public function test_invalid_submission_reports_errors(): void {
		$post                         = self::post();
		$post['jmc_enquiry']['email'] = 'alex@';

		$result = $this->handle( $post );

		self::assertSame( 'invalid', $result['status'] );
		self::assertSame( [ 'email' ], array_keys( $result['errors'] ) );
		self::assertSame( 'Alex Smith', $result['values']['name'] );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * Bot-only spam is silently dropped: reported as sent, never stored.
	 *
	 * @dataProvider spam_provider
	 *
	 * @param array<string,mixed> $overrides Request fields that make it look automated.
	 */
	public function test_spam_is_dropped_silently( array $overrides ): void {
		$result = $this->handle( self::post( $overrides ) );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'spam', $result['outcome'] );
		self::assertSame( [], $result['errors'] );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * Request fields that look automated.
	 *
	 * @return array<string, array{array<string,mixed>}>
	 */
	public function spam_provider(): array {
		return [
			'honeypot filled'    => [ [ JMC_ENQUIRY_HONEYPOT_FIELD => 'https://spam.test' ] ],
			'timing missing'     => [ [ JMC_ENQUIRY_TIMING_FIELD => '' ] ],
			'timing forged'      => [ [ JMC_ENQUIRY_TIMING_FIELD => jmc_enquiry_timing_token( self::NOW - 60, 'wrong-key' ) ] ],
			'submitted too fast' => [ [ JMC_ENQUIRY_TIMING_FIELD => jmc_enquiry_timing_token( self::NOW, self::KEY ) ] ],
			'form expired'       => [ [ JMC_ENQUIRY_TIMING_FIELD => jmc_enquiry_timing_token( self::NOW - JMC_ENQUIRY_MAX_SECONDS - 1, self::KEY ) ] ],
			'bot with links'     => [
				[
					JMC_ENQUIRY_HONEYPOT_FIELD => 'x',
					'jmc_enquiry'              => [
						'message' => self::LINKS_MESSAGE,
					],
				],
			],
			'invalid and spam'   => [
				[
					JMC_ENQUIRY_HONEYPOT_FIELD => 'x',
					'jmc_enquiry'              => [],
				],
			],
		];
	}

	/**
	 * A valid message with too many links is held as spam: stored with its
	 * reason, not sent, and reported to the visitor as sent.
	 */
	public function test_valid_spam_with_links_is_stored_and_not_sent(): void {
		$post                           = self::post();
		$post['jmc_enquiry']['message'] = self::LINKS_MESSAGE;

		$result = $this->handle( $post );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'spam_stored', $result['outcome'] );
		self::assertSame( [], $result['errors'] );
		self::assertCount( 1, $this->stored );
		self::assertSame( self::LINKS_MESSAGE, $this->stored[0]['message'] );
		self::assertSame( [ 'links' ], $this->stored_reasons );
		self::assertSame( [], $this->sent );
	}

	/**
	 * An invalid message with too many links gets the usual errors, so a person can correct it.
	 */
	public function test_invalid_spam_with_links_reports_errors(): void {
		$post                           = self::post();
		$post['jmc_enquiry']['message'] = self::LINKS_MESSAGE;
		$post['jmc_enquiry']['email']   = 'alex@';

		$result = $this->handle( $post );

		self::assertSame( 'invalid', $result['status'] );
		self::assertSame( [ 'email' ], array_keys( $result['errors'] ) );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * A repeat of a message held as spam isn't stored again.
	 */
	public function test_duplicate_spam_with_links_is_not_stored_again(): void {
		$post                           = self::post();
		$post['jmc_enquiry']['message'] = self::LINKS_MESSAGE;

		$result = $this->handle( post: $post, repeat: true );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'duplicate', $result['outcome'] );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * When a message held as spam can't be stored, the visitor is told, as for any other enquiry.
	 */
	public function test_spam_with_links_store_failure_is_an_error(): void {
		$post                           = self::post();
		$post['jmc_enquiry']['message'] = self::LINKS_MESSAGE;

		$result = $this->handle( post: $post, stored_id: 0 );

		self::assertSame( 'error', $result['status'] );
		self::assertSame( 'store_failed', $result['outcome'] );
		self::assertSame( [], $this->sent );
	}

	/**
	 * A visitor over the rate limit is refused before anything else happens.
	 */
	public function test_rate_limited_submission_is_refused(): void {
		$result = $this->handle( post: self::post(), over_limit: true );

		self::assertSame( 'rate_limited', $result['status'] );
		self::assertSame( 'rate_limited', $result['outcome'] );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * A repeat of a recent enquiry looks like a success but isn't stored or sent again.
	 */
	public function test_duplicate_is_not_stored_again(): void {
		$result = $this->handle( post: self::post(), repeat: true );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'duplicate', $result['outcome'] );
		self::assertSame( [], $this->stored );
		self::assertSame( [], $this->sent );
	}

	/**
	 * A failed delivery is still a success for the visitor: the stored enquiry can be resent.
	 */
	public function test_delivery_failure_is_reported_for_monitoring_only(): void {
		$result = $this->handle( post: self::post(), send_result: false );

		self::assertSame( 'sent', $result['status'] );
		self::assertSame( 'delivery_failed', $result['outcome'] );
		self::assertCount( 1, $this->stored );
		self::assertSame( [ 7 ], $this->sent );
	}

	/**
	 * When the enquiry can't be stored the visitor is told, and nothing is sent.
	 */
	public function test_store_failure_is_an_error(): void {
		$result = $this->handle( post: self::post(), stored_id: 0 );

		self::assertSame( 'error', $result['status'] );
		self::assertSame( 'store_failed', $result['outcome'] );
		self::assertSame( [], $this->sent );
	}

	/**
	 * Every status the visitor can see has a message.
	 */
	public function test_every_status_has_a_message(): void {
		$messages = jmc_enquiry_status_messages();

		foreach ( [ 'sent', 'invalid', 'rate_limited', 'expired', 'error' ] as $status ) {
			self::assertNotSame( '', $messages[ $status ] ?? '', $status );
		}
	}
}
