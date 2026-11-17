<?php
/**
 * Helpers for the site-wide business contact details.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Option holding the business telephone number, as it should be displayed.
 */
const JMC_BUSINESS_PHONE_OPTION = 'jmc_business_phone';

/**
 * Option holding the business email address.
 */
const JMC_BUSINESS_EMAIL_OPTION = 'jmc_business_email';

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
 * Build a link to the site-wide business telephone number or email address.
 *
 * @param string $type `phone` or `email`.
 *
 * @return string A validated `tel:` or `mailto:` URL, or an empty string when the
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
		default => '',
	};
}
