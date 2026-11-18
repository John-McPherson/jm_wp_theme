<?php
/**
 * Helpers for validating enquiry form submissions and spotting spam.
 *
 * Everything here is independent of WordPress so it can be unit tested in
 * isolation; the submission handler supplies the services list, the current
 * time and the signing key.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Name of the hidden honeypot field. People never see or fill it; bots that
 * fill every field do.
 */
const JMC_ENQUIRY_HONEYPOT_FIELD = 'jmc_enquiry_website';

/**
 * Name of the hidden field holding the signed time the form was rendered.
 */
const JMC_ENQUIRY_TIMING_FIELD = 'jmc_enquiry_started';

/**
 * Fewest seconds a person can plausibly take to complete the form.
 */
const JMC_ENQUIRY_MIN_SECONDS = 3;

/**
 * Most seconds a rendered form stays valid: two days, matching the longest
 * life of a WordPress nonce.
 */
const JMC_ENQUIRY_MAX_SECONDS = 2 * 86400;

/**
 * Most links a genuine enquiry message is expected to contain.
 */
const JMC_ENQUIRY_MAX_LINKS = 2;

/**
 * Describe the enquiry form fields.
 *
 * The keys are the field IDs used in the submitted `jmc_enquiry[...]` array,
 * the stored enquiry and the rendered form.
 *
 * @return array<string, array{
 *     label: string,
 *     required: bool,
 *     max_length: int
 * }> Field definitions, in form order.
 */
function jmc_enquiry_fields(): array {
	return [
		'name'     => [
			'label'      => __( 'Name', 'jmc-theme' ),
			'required'   => true,
			'max_length' => 100,
		],
		'email'    => [
			'label'      => __( 'Email address', 'jmc-theme' ),
			'required'   => true,
			'max_length' => 254,
		],
		'phone'    => [
			'label'      => __( 'Phone number', 'jmc-theme' ),
			'required'   => false,
			'max_length' => 30,
		],
		'postcode' => [
			'label'      => __( 'Postcode', 'jmc-theme' ),
			'required'   => false,
			'max_length' => 8,
		],
		'service'  => [
			'label'      => __( 'Service', 'jmc-theme' ),
			'required'   => false,
			'max_length' => 200,
		],
		'message'  => [
			'label'      => __( 'Message', 'jmc-theme' ),
			'required'   => true,
			'max_length' => 5000,
		],
		'consent'  => [
			'label'      => __( 'Consent', 'jmc-theme' ),
			'required'   => true,
			'max_length' => 1,
		],
	];
}

/**
 * Clean a single-line value: drop markup and control characters, collapse
 * whitespace and trim.
 *
 * @param mixed $value Submitted value.
 *
 * @return string Plain, single-line text (not escaped), or an empty string.
 */
function jmc_enquiry_clean_line( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$text = strip_tags( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Keeps the helper free of WordPress so it can be unit tested in isolation; the value is escaped at output.
	$text = (string) preg_replace( '/[\p{Cc}\p{Cf}\s]+/u', ' ', $text );

	return trim( $text );
}

/**
 * Clean a multi-line value: drop markup and control characters other than
 * line breaks, normalise line endings and trim each line, allowing at most
 * one blank line in a row.
 *
 * @param mixed $value Submitted value.
 *
 * @return string Plain text with lines separated by "\n" (not escaped), or an empty string.
 */
function jmc_enquiry_clean_text( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$text = strip_tags( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Keeps the helper free of WordPress so it can be unit tested in isolation; the value is escaped at output.
	$text = str_replace( [ "\r\n", "\r" ], "\n", $text );

	$lines = array_map(
		static fn ( string $line ): string => trim( (string) preg_replace( '/[\p{Cc}\p{Cf}\s]+/u', ' ', $line ) ),
		explode( "\n", $text )
	);

	$text = (string) preg_replace( '/\n{3,}/', "\n\n", implode( "\n", $lines ) );

	return trim( $text );
}

/**
 * Normalise a UK postcode to upper case with a single space before the inward code.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The normalised postcode, or an empty string when it isn't a valid UK postcode.
 */
function jmc_enquiry_postcode( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$postcode = strtoupper( (string) preg_replace( '/\s+/', '', $value ) );

	if ( 1 !== preg_match( '/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/', $postcode ) ) {
		return '';
	}

	return substr( $postcode, 0, -3 ) . ' ' . substr( $postcode, -3 );
}

/**
 * Describe how to fix each invalid field.
 *
 * Shared by the server validation and the form's browser validation so a
 * visitor sees the same message either way.
 *
 * @return array<string, string> Error key => message. Keys are field IDs, plus
 *                               `email_format` for a malformed email address.
 */
function jmc_enquiry_error_messages(): array {
	return [
		'name'         => __( 'Enter your name.', 'jmc-theme' ),
		'email'        => __( 'Enter your email address.', 'jmc-theme' ),
		'email_format' => __( 'Enter an email address in the correct format, like name@example.com.', 'jmc-theme' ),
		'phone'        => __( 'Enter a phone number using only digits, spaces, brackets, dashes and a leading +, or leave it blank.', 'jmc-theme' ),
		'postcode'     => __( 'Enter a full UK postcode, like LS1 4AP, or leave it blank.', 'jmc-theme' ),
		'service'      => __( 'Choose a service from the list.', 'jmc-theme' ),
		'message'      => __( 'Enter a message describing the work you need.', 'jmc-theme' ),
		'consent'      => __( 'Confirm you agree to us using your details to reply to your enquiry.', 'jmc-theme' ),
	];
}

/**
 * Validate and sanitise an enquiry submission.
 *
 * Every field in `jmc_enquiry_fields()` is present in `values`, cleaned and
 * normalised, whether or not it is valid, so a rejected form can be shown
 * again with the visitor's input. `errors` maps field IDs to a message
 * saying how to fix the field; it is empty when the submission is valid.
 *
 * @param mixed    $input    Submitted `jmc_enquiry` array.
 * @param string[] $services Services the visitor may choose from.
 *
 * @return array{
 *     values: array<string, string>,
 *     errors: array<string, string>
 * } Cleaned values and per-field errors.
 */
function jmc_validate_enquiry( mixed $input, array $services ): array {
	$input    = is_array( $input ) ? $input : [];
	$fields   = jmc_enquiry_fields();
	$messages = jmc_enquiry_error_messages();

	$values = [
		'name'     => jmc_enquiry_clean_line( $input['name'] ?? '' ),
		'email'    => jmc_enquiry_clean_line( $input['email'] ?? '' ),
		'phone'    => jmc_enquiry_clean_line( $input['phone'] ?? '' ),
		'postcode' => jmc_enquiry_clean_line( $input['postcode'] ?? '' ),
		'service'  => jmc_enquiry_clean_line( $input['service'] ?? '' ),
		'message'  => jmc_enquiry_clean_text( $input['message'] ?? '' ),
		'consent'  => '1' === ( $input['consent'] ?? null ) ? '1' : '',
	];

	$errors = [];

	if ( '' === $values['name'] ) {
		$errors['name'] = $messages['name'];
	}

	if ( '' === $values['email'] ) {
		$errors['email'] = $messages['email'];
	} elseif ( '' === jmc_sanitize_business_email( $values['email'] ) ) {
		$errors['email'] = $messages['email_format'];
	}

	if ( '' !== $values['phone'] && '' === jmc_sanitize_business_phone( $values['phone'] ) ) {
		$errors['phone'] = $messages['phone'];
	}

	if ( '' !== $values['postcode'] ) {
		$postcode = jmc_enquiry_postcode( $values['postcode'] );

		if ( '' === $postcode ) {
			$errors['postcode'] = $messages['postcode'];
		} else {
			$values['postcode'] = $postcode;
		}
	}

	if ( '' !== $values['service'] && ! in_array( $values['service'], $services, true ) ) {
		$errors['service'] = $messages['service'];
	}

	if ( '' === $values['message'] ) {
		$errors['message'] = $messages['message'];
	}

	if ( '' === $values['consent'] ) {
		$errors['consent'] = $messages['consent'];
	}

	foreach ( $fields as $id => $field ) {
		if ( ! isset( $errors[ $id ] ) && mb_strlen( $values[ $id ] ) > $field['max_length'] ) {
			$errors[ $id ] = sprintf(
				/* translators: 1: field label, 2: maximum number of characters. */
				__( '%1$s must be %2$d characters or fewer.', 'jmc-theme' ),
				$field['label'],
				$field['max_length']
			);
		}
	}

	// Report errors in form order so the error summary matches the page.
	$errors = array_intersect_key( array_replace( $fields, $errors ), $errors );

	return [
		'values' => $values,
		'errors' => $errors,
	];
}

/**
 * Create the signed token recording when the form was rendered.
 *
 * @param int    $time Unix time the form was rendered.
 * @param string $key  Secret signing key.
 *
 * @return string `<time>.<signature>`.
 */
function jmc_enquiry_timing_token( int $time, string $key ): string {
	return $time . '.' . hash_hmac( 'sha256', (string) $time, $key );
}

/**
 * Decide whether a submission looks automated.
 *
 * Checks, in order: the honeypot field is empty; the timing token is
 * present, unaltered and neither too fresh (filled in faster than a person
 * could) nor too old; and the message doesn't contain more links than a
 * genuine enquiry would.
 *
 * @param array<string,mixed> $post    Submitted request fields.
 * @param string              $message Cleaned message text.
 * @param int                 $now     Current Unix time.
 * @param string              $key     Secret signing key for the timing token.
 *
 * @return string Empty when the submission looks genuine, otherwise the reason:
 *                `honeypot`, `timing`, `too_fast`, `expired` or `links`.
 */
function jmc_enquiry_spam_reason(
	array $post,
	string $message,
	int $now,
	string $key
): string {
	$honeypot = $post[ JMC_ENQUIRY_HONEYPOT_FIELD ] ?? '';

	if ( ! is_string( $honeypot ) || '' !== trim( $honeypot ) ) {
		return 'honeypot';
	}

	$token = $post[ JMC_ENQUIRY_TIMING_FIELD ] ?? '';

	if ( ! is_string( $token ) || 1 !== preg_match( '/^(\d{1,12})\.([a-f0-9]{64})$/', $token, $matches ) ) {
		return 'timing';
	}

	if ( ! hash_equals( jmc_enquiry_timing_token( (int) $matches[1], $key ), $token ) ) {
		return 'timing';
	}

	$elapsed = $now - (int) $matches[1];

	if ( $elapsed < JMC_ENQUIRY_MIN_SECONDS ) {
		return 'too_fast';
	}

	if ( $elapsed > JMC_ENQUIRY_MAX_SECONDS ) {
		return 'expired';
	}

	if ( preg_match_all( '~(?:https?://|www\.)~i', $message ) > JMC_ENQUIRY_MAX_LINKS ) {
		return 'links';
	}

	return '';
}

/**
 * Fingerprint an enquiry so repeated submissions of the same message can be spotted.
 *
 * Built from the email address and message, ignoring case and whitespace, so
 * a resubmission after a reload or a double click matches the original.
 *
 * @param array<string,string> $values Cleaned enquiry values.
 *
 * @return string A SHA-256 hash.
 */
function jmc_enquiry_fingerprint( array $values ): string {
	$normalise = static fn ( string $text ): string => strtolower( (string) preg_replace( '/\s+/u', ' ', trim( $text ) ) );

	return hash(
		'sha256',
		$normalise( $values['email'] ?? '' ) . "\n" . $normalise( $values['message'] ?? '' )
	);
}

/**
 * Key that identifies a visitor for rate limiting without storing their IP address.
 *
 * @param string $ip  Visitor's IP address.
 * @param string $key Secret salt.
 *
 * @return string A SHA-256 HMAC of the address.
 */
function jmc_enquiry_rate_key( string $ip, string $key ): string {
	return hash_hmac( 'sha256', $ip, $key );
}

/**
 * Post type that stores enquiries.
 */
const JMC_ENQUIRY_POST_TYPE = 'jmc_enquiry';

/**
 * Meta key holding an enquiry's cleaned values, keyed by field ID.
 */
const JMC_ENQUIRY_VALUES_META = '_jmc_enquiry_values';

/**
 * Meta key holding the enquirer's lower-case email address, for duplicate
 * checks and personal data requests.
 */
const JMC_ENQUIRY_EMAIL_META = '_jmc_enquiry_email';

/**
 * Meta key holding the enquiry's duplicate-detection fingerprint.
 */
const JMC_ENQUIRY_FINGERPRINT_META = '_jmc_enquiry_fingerprint';

/**
 * Meta key holding the enquiry's delivery status.
 */
const JMC_ENQUIRY_STATUS_META = '_jmc_enquiry_status';

/**
 * Meta key holding the number of delivery attempts.
 */
const JMC_ENQUIRY_ATTEMPTS_META = '_jmc_enquiry_attempts';

/**
 * Meta key holding the last delivery error.
 */
const JMC_ENQUIRY_ERROR_META = '_jmc_enquiry_error';

/**
 * Days an enquiry is kept before it is deleted, unless filtered.
 */
const JMC_ENQUIRY_RETENTION_DAYS = 365;

/**
 * Describe the delivery statuses an enquiry can have.
 *
 * `accepted` means stored but not yet handed to the mail provider, `sent`
 * means the mail provider accepted it (not that it reached the inbox), and
 * `failed` means the hand-off failed and the enquiry needs resending.
 *
 * @return array<string, string> Status => label.
 */
function jmc_enquiry_statuses(): array {
	return [
		'accepted' => __( 'Not sent yet', 'jmc-theme' ),
		'sent'     => __( 'Sent', 'jmc-theme' ),
		'failed'   => __( 'Failed', 'jmc-theme' ),
	];
}

/**
 * Compose the notification email for an enquiry.
 *
 * The enquirer's address goes in `Reply-To` so the business can answer
 * directly. Values must already be cleaned by `jmc_validate_enquiry()`, which
 * removes the line breaks a header injection would need; the display name is
 * also stripped of characters with meaning in an address header.
 *
 * @param array<string,string> $values    Cleaned enquiry values.
 * @param string               $site_name Site name, for the subject.
 * @param string               $view_url  URL of the enquiry in WordPress admin.
 *
 * @return array{subject: string, body: string, headers: string[]} Email parts for `wp_mail()`.
 */
function jmc_enquiry_email( array $values, string $site_name, string $view_url ): array {
	$value = static fn ( string $key ): string => (string) ( $values[ $key ] ?? '' );

	$name     = $value( 'name' );
	$service  = '' === $value( 'service' ) ? __( 'Not specified', 'jmc-theme' ) : $value( 'service' );
	$phone    = '' === $value( 'phone' ) ? __( 'Not given', 'jmc-theme' ) : $value( 'phone' );
	$postcode = '' === $value( 'postcode' ) ? __( 'Not given', 'jmc-theme' ) : $value( 'postcode' );

	$lines = [
		__( 'You have a new enquiry from the website.', 'jmc-theme' ),
		'',
		__( 'Name', 'jmc-theme' ) . ': ' . $name,
		__( 'Email address', 'jmc-theme' ) . ': ' . $value( 'email' ),
		__( 'Phone number', 'jmc-theme' ) . ': ' . $phone,
		__( 'Postcode', 'jmc-theme' ) . ': ' . $postcode,
		__( 'Service', 'jmc-theme' ) . ': ' . $service,
		'',
		__( 'Message', 'jmc-theme' ) . ':',
		$value( 'message' ),
		'',
		__( 'Reply to this email to answer the enquiry.', 'jmc-theme' ),
		__( 'View it in WordPress', 'jmc-theme' ) . ': ' . $view_url,
	];

	$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
	$email   = jmc_sanitize_business_email( $value( 'email' ) );

	if ( '' !== $email ) {
		$display   = trim( (string) preg_replace( '/["<>,;:\\\\\r\n]+/', '', $name ) );
		$headers[] = '' === $display
			? 'Reply-To: ' . $email
			: sprintf( 'Reply-To: "%s" <%s>', $display, $email );
	}

	return [
		'subject' => sprintf(
			/* translators: 1: site name, 2: enquirer's name. */
			__( '[%1$s] New enquiry from %2$s', 'jmc-theme' ),
			$site_name,
			'' === $name ? __( 'a visitor', 'jmc-theme' ) : $name
		),
		'body'    => implode( "\n", $lines ),
		'headers' => $headers,
	];
}

/**
 * Most submissions one visitor can make in a rate-limit window.
 */
const JMC_ENQUIRY_RATE_LIMIT = 10;

/**
 * Length of the rate-limit window, in seconds (one hour).
 */
const JMC_ENQUIRY_RATE_WINDOW = 3600;

/**
 * Seconds within which a repeat of the same enquiry counts as a duplicate (one day).
 */
const JMC_ENQUIRY_DUPLICATE_WINDOW = 86400;

/**
 * Decide what happens to a submission and carry it out.
 *
 * Runs the checks in order — rate limit, spam, validation, duplicate — then
 * stores the enquiry and sends it. WordPress-specific work is passed in as
 * callables so every path can be tested in isolation. The nonce is checked
 * by the caller before this runs.
 *
 * The returned `outcome` is the operational state for monitoring. `status`
 * is what the visitor is told: spam and duplicates are reported as `sent`
 * so bots learn nothing and a repeated click looks like a success, and a
 * failed delivery is also `sent` because the stored enquiry is flagged for
 * resending in WordPress admin.
 *
 * @param array<string,mixed> $post      Submitted request fields.
 * @param string[]            $services  Services the visitor may choose from.
 * @param int                 $now       Current Unix time.
 * @param string              $key       Secret signing key for the timing token.
 * @param callable            $hit_limit `(): bool` Record the attempt; return whether the visitor is over the limit.
 * @param callable            $is_repeat `(string $fingerprint): bool` Whether a matching enquiry was stored recently.
 * @param callable            $store     `(array $values): int` Store the enquiry; return its ID, or 0 on failure.
 * @param callable            $send      `(int $post_id): bool` Send the stored enquiry; return whether it was handed off.
 *
 * @return array{
 *     status: 'sent'|'invalid'|'rate_limited'|'error',
 *     outcome: 'sent'|'delivery_failed'|'duplicate'|'spam'|'rate_limited'|'invalid'|'store_failed',
 *     errors: array<string,string>,
 *     values: array<string,string>
 * } What to tell the visitor, what happened, per-field errors, and the cleaned values.
 */
function jmc_handle_enquiry(
	array $post,
	array $services,
	int $now,
	string $key,
	callable $hit_limit,
	callable $is_repeat,
	callable $store,
	callable $send
): array {
	$result = static fn ( string $status, string $outcome, array $errors = [], array $values = [] ): array => [
		'status'  => $status,
		'outcome' => $outcome,
		'errors'  => $errors,
		'values'  => $values,
	];

	if ( $hit_limit() ) {
		return $result( 'rate_limited', 'rate_limited' );
	}

	$validated = jmc_validate_enquiry( $post['jmc_enquiry'] ?? [], $services );

	if ( '' !== jmc_enquiry_spam_reason( $post, $validated['values']['message'], $now, $key ) ) {
		return $result( 'sent', 'spam' );
	}

	if ( [] !== $validated['errors'] ) {
		return $result( 'invalid', 'invalid', $validated['errors'], $validated['values'] );
	}

	if ( $is_repeat( jmc_enquiry_fingerprint( $validated['values'] ) ) ) {
		return $result( 'sent', 'duplicate' );
	}

	$post_id = $store( $validated['values'] );

	if ( $post_id <= 0 ) {
		return $result( 'error', 'store_failed', [], $validated['values'] );
	}

	return $send( $post_id )
		? $result( 'sent', 'sent' )
		: $result( 'sent', 'delivery_failed' );
}

/**
 * Describe what the visitor is told after submitting the form.
 *
 * `expired` is reported by the submission wrapper when the nonce fails;
 * the rest come from `jmc_handle_enquiry()`.
 *
 * @return array<string, string> Status => message.
 */
function jmc_enquiry_status_messages(): array {
	return [
		'sent'         => __( 'Thank you. Your enquiry has been sent and we’ll be in touch soon.', 'jmc-theme' ),
		'invalid'      => __( 'There’s a problem with some of the details you entered. Check them and send your enquiry again.', 'jmc-theme' ),
		'rate_limited' => __( 'You’ve sent several enquiries in a short time. Wait an hour before trying again, or contact us by phone or email.', 'jmc-theme' ),
		'expired'      => __( 'This form has expired. Send your enquiry again.', 'jmc-theme' ),
		'error'        => __( 'Sorry, we couldn’t send your enquiry. Try again, or contact us by phone or email.', 'jmc-theme' ),
	];
}
