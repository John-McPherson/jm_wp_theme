<?php
/**
 * Helpers for the site-wide business contact details.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Option holding the business (trading) name.
 */
const JMC_BUSINESS_NAME_OPTION = 'jmc_business_name';

/**
 * Option holding the business postal address, one line per line of the address.
 */
const JMC_BUSINESS_ADDRESS_OPTION = 'jmc_business_address';

/**
 * Option holding the business telephone number, as it should be displayed.
 */
const JMC_BUSINESS_PHONE_OPTION = 'jmc_business_phone';

/**
 * Option holding the business email address.
 */
const JMC_BUSINESS_EMAIL_OPTION = 'jmc_business_email';

/**
 * Option holding the business WhatsApp number, in international format.
 */
const JMC_BUSINESS_WHATSAPP_OPTION = 'jmc_business_whatsapp';

/**
 * Option holding the business opening hours, keyed by day.
 */
const JMC_BUSINESS_HOURS_OPTION = 'jmc_business_hours';

/**
 * Option holding the places the business serves.
 */
const JMC_BUSINESS_SERVICE_AREA_OPTION = 'jmc_business_service_area';

/**
 * Days of the week in display order, as stored keys.
 */
const JMC_BUSINESS_DAYS = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];

/**
 * Longest place name kept in the service area, in characters.
 */
const JMC_SERVICE_AREA_MAX_LENGTH = 100;

/**
 * Sanitise the business name for storage.
 *
 * Plain text on one line: tags are removed, entities decoded and whitespace
 * (including line breaks) collapsed.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The name, or an empty string when it is missing or blank.
 */
function jmc_sanitize_business_name( mixed $value ): string {
	return trim( (string) preg_replace( '/\s+/u', ' ', jmc_plain_text( $value ) ) );
}

/**
 * Sanitise the business address for storage.
 *
 * Plain text, one address line per line: tags are removed (a `<br>` becomes a
 * new line), entities decoded, whitespace within each line collapsed and
 * blank lines dropped.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The address lines joined by `\n`, or an empty string when it is missing or blank.
 */
function jmc_sanitize_business_address( mixed $value ): string {
	return implode(
		"\n",
		array_map(
			static fn ( string $line ): string => (string) preg_replace( '/\s+/u', ' ', $line ),
			explode( "\n", jmc_plain_text( $value ) )
		)
	);
}

/**
 * Sanitise a business telephone number for storage.
 *
 * Keeps the number as written (so it can be displayed) with whitespace
 * collapsed, but only when it can also be dialled.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The number, or an empty string when it is missing or can't be dialled.
 */
function jmc_sanitize_business_phone( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$phone = trim( (string) preg_replace( '/\s+/', ' ', $value ) );

	return '' !== jmc_safe_link_url( 'tel:' . $phone )
		? $phone
		: '';
}

/**
 * Sanitise a business WhatsApp number for storage.
 *
 * Keeps the number as written (so it can be displayed) with whitespace
 * collapsed, but only when it's in international format: an optional `+`, then
 * a country code and number of 8–15 digits that doesn't start with 0.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The number, or an empty string when it is missing or not international.
 */
function jmc_sanitize_business_whatsapp( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$number = trim( (string) preg_replace( '/\s+/', ' ', $value ) );

	return '' !== jmc_whatsapp_digits( $number )
		? $number
		: '';
}

/**
 * Reduce an international WhatsApp number to the digits `wa.me` links expect.
 *
 * @param string $number Number as written, e.g. `+44 7700 900123`.
 *
 * @return string Digits only, e.g. `447700900123`, or an empty string when the
 *                number isn't in international format.
 */
function jmc_whatsapp_digits( string $number ): string {
	if ( 1 !== preg_match( '/^\+?[\d\s().-]+$/', $number ) ) {
		return '';
	}

	$digits = (string) preg_replace( '/\D/', '', $number );

	return 1 === preg_match( '/^[1-9]\d{7,14}$/', $digits )
		? $digits
		: '';
}

/**
 * Sanitise a business email address for storage.
 *
 * @param mixed $value Submitted value.
 *
 * @return string The address, or an empty string when it is missing or invalid.
 */
function jmc_sanitize_business_email( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$email = trim( $value );

	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL )
		? $email
		: '';
}

/**
 * Translated day names, keyed by stored day.
 *
 * @return array<string, string>
 */
function jmc_business_day_labels(): array {
	return array_combine(
		JMC_BUSINESS_DAYS,
		[
			__( 'Monday', 'jmc-theme' ),
			__( 'Tuesday', 'jmc-theme' ),
			__( 'Wednesday', 'jmc-theme' ),
			__( 'Thursday', 'jmc-theme' ),
			__( 'Friday', 'jmc-theme' ),
			__( 'Saturday', 'jmc-theme' ),
			__( 'Sunday', 'jmc-theme' ),
		]
	);
}

/**
 * Sanitise the business opening hours for storage.
 *
 * Each open day needs a 24-hour `HH:MM` opening time and a later closing time.
 * Days that are missing, incomplete or malformed are dropped, which means closed.
 *
 * @param mixed $value Submitted value, keyed by lowercase day name.
 *
 * @return array<string, array{opens: string, closes: string}> Open days in week order.
 */
function jmc_sanitize_business_hours( mixed $value ): array {
	if ( ! is_array( $value ) ) {
		return [];
	}

	$hours = [];

	foreach ( JMC_BUSINESS_DAYS as $day ) {
		$times = $value[ $day ] ?? null;

		if ( ! is_array( $times ) ) {
			continue;
		}

		$opens  = jmc_sanitize_business_time( $times['opens'] ?? null );
		$closes = jmc_sanitize_business_time( $times['closes'] ?? null );

		// Zero-padded 24-hour times compare correctly as strings.
		if ( '' === $opens || '' === $closes || $closes <= $opens ) {
			continue;
		}

		$hours[ $day ] = [
			'opens'  => $opens,
			'closes' => $closes,
		];
	}

	return $hours;
}

/**
 * Validate a 24-hour `HH:MM` time.
 *
 * @param mixed $value Submitted time.
 *
 * @return string The time, or an empty string when it isn't a valid `HH:MM` time.
 */
function jmc_sanitize_business_time( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$time = trim( $value );

	return 1 === preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time )
		? $time
		: '';
}

/**
 * Sanitise the business service area for storage.
 *
 * Accepts a list of place names, or text with one place per line (as the
 * settings textarea submits it). Whitespace is collapsed, and blank, duplicate,
 * over-long or markup-bearing entries are dropped.
 *
 * @param mixed $value Submitted list or text.
 *
 * @return list<string> Place names in the order given.
 */
function jmc_sanitize_business_service_area( mixed $value ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\R/', $value );
	}

	if ( ! is_array( $value ) ) {
		return [];
	}

	$places = [];

	foreach ( $value as $place ) {
		if ( ! is_string( $place ) ) {
			continue;
		}

		$place = trim( (string) preg_replace( '/\s+/u', ' ', $place ) );

		if (
			'' === $place
			|| mb_strlen( $place ) > JMC_SERVICE_AREA_MAX_LENGTH
			|| 1 === preg_match( '/[<>]/', $place )
		) {
			continue;
		}

		$places[ mb_strtolower( $place ) ] ??= $place;
	}

	return array_values( $places );
}

/**
 * Read a list of places as one phrase, as the footer and Contact Details show it.
 *
 * One place reads "Pudsey", two read "Pudsey and Leeds", and three or more read
 * "Pudsey, Leeds and Bradford". The "and" is translatable. The phrase is not
 * escaped; escape it at output.
 *
 * @param string[] $places Place names, e.g. from jmc_business_contact_details().
 *
 * @return string The phrase, or an empty string when there are no places.
 */
function jmc_service_area_phrase( array $places ): string {
	$last_place = (string) array_pop( $places );

	return [] === $places
		? $last_place
		: sprintf(
			/* translators: 1: comma-separated list of places, 2: the last place. */
			__( '%1$s and %2$s', 'jmc-theme' ),
			implode( ', ', $places ),
			$last_place
		);
}

/**
 * Read the site-wide business contact details, validated for display.
 *
 * Stored values are re-sanitised in case they were written around the
 * sanitisers. A phone number, email address or WhatsApp number is only
 * returned with a working link; otherwise the value and its link are empty.
 *
 * @return array{
 *     phone: string,
 *     phone_url: string,
 *     email: string,
 *     email_url: string,
 *     whatsapp: string,
 *     whatsapp_url: string,
 *     hours: array<string, array{opens: string, closes: string}|null>,
 *     service_area: list<string>
 * } Opening hours list every day in week order (`null` when closed), or are
 *   empty when no day has hours.
 */
function jmc_business_contact_details(): array {
	$phone_url    = jmc_business_contact_url( 'phone' );
	$email_url    = jmc_business_contact_url( 'email' );
	$whatsapp_url = jmc_business_contact_url( 'whatsapp' );
	$open_days    = jmc_sanitize_business_hours( get_option( JMC_BUSINESS_HOURS_OPTION, [] ) );

	return [
		'phone'        => '' === $phone_url ? '' : jmc_sanitize_business_phone( get_option( JMC_BUSINESS_PHONE_OPTION, '' ) ),
		'phone_url'    => $phone_url,
		'email'        => '' === $email_url ? '' : jmc_sanitize_business_email( get_option( JMC_BUSINESS_EMAIL_OPTION, '' ) ),
		'email_url'    => $email_url,
		'whatsapp'     => '' === $whatsapp_url ? '' : jmc_sanitize_business_whatsapp( get_option( JMC_BUSINESS_WHATSAPP_OPTION, '' ) ),
		'whatsapp_url' => $whatsapp_url,
		'hours'        => [] === $open_days
			? []
			: array_map(
				static fn ( string $day ): ?array => $open_days[ $day ] ?? null,
				array_combine( JMC_BUSINESS_DAYS, JMC_BUSINESS_DAYS )
			),
		'service_area' => jmc_sanitize_business_service_area( get_option( JMC_BUSINESS_SERVICE_AREA_OPTION, [] ) ),
	];
}

/**
 * Format a stored `HH:MM` time with the site's time format.
 *
 * @param string $time 24-hour `HH:MM` time.
 *
 * @return string The formatted time, or an empty string when `$time` is invalid.
 */
function jmc_format_business_time( string $time ): string {
	$time = jmc_sanitize_business_time( $time );

	if ( '' === $time ) {
		return '';
	}

	$format = get_option( 'time_format', '' );

	return \DateTimeImmutable::createFromFormat( '!H:i', $time )->format(
		is_string( $format ) && '' !== $format ? $format : 'g:i a'
	);
}

/**
 * Clean a pre-filled message (email subject or body, WhatsApp text) for a link.
 *
 * Line breaks are normalised to `\n` and kept, with at most one blank line in a
 * row; other runs of whitespace collapse to single spaces. The text is encoded
 * into the link, so markup in it is harmless and is kept as typed.
 *
 * @param mixed $value Submitted text.
 *
 * @return string Plain text, or an empty string.
 */
function jmc_contact_message_text( mixed $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	$lines = array_map(
		static fn ( string $line ): string => trim( (string) preg_replace( '/[^\S\n]+/u', ' ', $line ) ),
		explode( "\n", str_replace( [ "\r\n", "\r" ], "\n", $value ) )
	);

	return trim( (string) preg_replace( '/\n{3,}/', "\n\n", implode( "\n", $lines ) ) );
}

/**
 * Add a pre-filled message to a site-wide contact link.
 *
 * Email links take a `subject` and `body`; WhatsApp links take `text`. Other
 * keys, empty values and other link types are ignored. Line breaks are encoded
 * as `%0D%0A` for email (RFC 6068) and `%0A` for WhatsApp, and kept at output
 * by jmc_esc_href().
 *
 * @param string               $url     A `mailto:` or `https://wa.me/` link from jmc_business_contact_url().
 * @param array<string, mixed> $message Message parts, e.g. `[ 'subject' => 'Quote', 'body' => '…' ]`.
 *
 * @return string The link with its message query, or `$url` unchanged.
 */
function jmc_contact_url_with_message( string $url, array $message ): string {
	[ $keys, $line_break ] = match ( true ) {
		str_starts_with( $url, 'mailto:' )        => [ [ 'subject', 'body' ], "\r\n" ],
		str_starts_with( $url, 'https://wa.me/' ) => [ [ 'text' ], "\n" ],
		default                                   => [ [], "\n" ],
	};

	$query = [];

	foreach ( $keys as $key ) {
		$text = jmc_contact_message_text( $message[ $key ] ?? null );

		// A subject is a single line.
		if ( 'subject' === $key ) {
			$text = str_replace( "\n", ' ', $text );
		}

		if ( '' !== $text ) {
			// rawurlencode() writes spaces as %20, which mail apps read; `+` is literal in mailto.
			$query[] = $key . '=' . rawurlencode( str_replace( "\n", $line_break, $text ) );
		}
	}

	return [] === $query || str_contains( $url, '?' )
		? $url
		: $url . '?' . implode( '&', $query );
}

/**
 * Build a WhatsApp chat link for an international number.
 *
 * @param string $number Number as written, e.g. `+44 7700 900123`.
 *
 * @return string A `https://wa.me/` URL, or an empty string when the number isn't international.
 */
function jmc_whatsapp_url( string $number ): string {
	$digits = jmc_whatsapp_digits( $number );

	return '' === $digits
		? ''
		: 'https://wa.me/' . $digits;
}

/**
 * Build a link to the site-wide business telephone number, email address or WhatsApp number.
 *
 * @param string $type `phone`, `email` or `whatsapp`.
 *
 * @return string A validated `tel:`, `mailto:` or `https://wa.me/` URL, or an empty string when the
 *                type is unknown or the detail is missing or invalid.
 */
function jmc_business_contact_url( string $type ): string {
	return match ( $type ) {
		'phone' => jmc_safe_link_url(
			'tel:' . jmc_sanitize_business_phone( get_option( JMC_BUSINESS_PHONE_OPTION, '' ) )
		),
		'email' => jmc_safe_link_url(
			url: 'mailto:' . jmc_sanitize_business_email( get_option( JMC_BUSINESS_EMAIL_OPTION, '' ) ),
			allow_mailto: true
		),
		'whatsapp' => jmc_whatsapp_url(
			jmc_sanitize_business_whatsapp( get_option( JMC_BUSINESS_WHATSAPP_OPTION, '' ) )
		),
		default => '',
	};
}
