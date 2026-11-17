// Digits with optional leading +, spaces, dots, dashes and brackets; 3–15 digits (E.164 maximum).
const PHONE = /^\+?[\d\s().-]+$/;
const EMAIL = /^[^\s@/?#:]+@[^\s@/?#:]+\.[^\s@/?#:]+$/;

/**
 * Turn a bare phone number or email address into a `tel:` or `mailto:` link.
 *
 * `LinkControl` prefixes anything without a scheme with `https://`, so
 * `01234 567890` arrives as `https://01234 567890` and `hello@example.com` as
 * `https://hello@example.com` (a valid link to example.com). Both forms are
 * converted; every other value is returned unchanged for the server to validate.
 *
 * @param url Destination saved by the link control.
 *
 * @return The contact link, or `url` unchanged.
 */
export default function toContactUrl( url: string ): string {
	const value = url.trim().replace( /^https?:\/\//i, '' );

	if ( PHONE.test( value ) ) {
		const digits = value.replace( /\D/g, '' ).length;

		if ( digits >= 3 && digits <= 15 ) {
			return `tel:${ value.replace( /[\s().-]/g, '' ) }`;
		}
	}

	if ( EMAIL.test( value ) ) {
		return `mailto:${ value }`;
	}

	return url;
}
