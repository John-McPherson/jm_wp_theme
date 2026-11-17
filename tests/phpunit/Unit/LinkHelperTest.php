<?php
/**
 * Link helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/link.php';

/**
 * Verify link destinations are validated and normalised.
 */
final class LinkHelperTest extends TestCase {

	/**
	 * Supported destinations are returned, normalised where needed.
	 *
	 * @dataProvider valid_destination_provider
	 *
	 * @param string $url      Candidate destination.
	 * @param string $expected Expected normalised destination.
	 */
	public function test_accepts_supported_destinations( string $url, string $expected ): void {
		self::assertSame( $expected, jmc_safe_link_url( $url ) );
	}

	/**
	 * Supported destinations and their expected output.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function valid_destination_provider(): array {
		return [
			'https URL'                 => [ 'https://example.com/contact/', 'https://example.com/contact/' ],
			'http URL'                  => [ 'http://example.com', 'http://example.com' ],
			'uppercase scheme'          => [ 'HTTPS://example.com', 'HTTPS://example.com' ],
			'URL with query and anchor' => [ 'https://example.com/?a=1&b=2#form', 'https://example.com/?a=1&b=2#form' ],
			'surrounding whitespace'    => [ '  https://example.com  ', 'https://example.com' ],
			'site-relative path'        => [ '/contact/', '/contact/' ],
			'site root'                 => [ '/', '/' ],
			'telephone'                 => [ 'tel:01234567890', 'tel:01234567890' ],
			'international telephone'   => [ 'tel:+441234567890', 'tel:+441234567890' ],
			'formatted telephone'       => [ 'tel:+44 (0)1234 567-890', 'tel:+4401234567890' ],
			'uppercase tel scheme'      => [ 'TEL:01234 567890', 'tel:01234567890' ],
		];
	}

	/**
	 * Empty, unsupported and unsafe destinations are rejected.
	 *
	 * @dataProvider invalid_destination_provider
	 *
	 * @param mixed $url Candidate destination.
	 */
	public function test_rejects_invalid_destinations( mixed $url ): void {
		self::assertSame( '', jmc_safe_link_url( $url ) );
	}

	/**
	 * Destinations that must be rejected.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function invalid_destination_provider(): array {
		return [
			'empty string'               => [ '' ],
			'whitespace only'            => [ '   ' ],
			'null'                       => [ null ],
			'array'                      => [ [ 'url' => 'https://example.com' ] ],
			'javascript scheme'          => [ 'javascript:alert(1)' ],
			'mixed-case javascript'      => [ 'JaVaScRiPt:alert(1)' ],
			'data scheme'                => [ 'data:text/html,<script>alert(1)</script>' ],
			'mailto scheme'              => [ 'mailto:hello@example.com' ],
			'ftp scheme'                 => [ 'ftp://example.com' ],
			'protocol-relative URL'      => [ '//example.com' ],
			'backslash host trick'       => [ '/\\example.com' ],
			'scheme without host'        => [ 'https:///contact' ],
			'bare domain'                => [ 'example.com' ],
			'relative path without root' => [ 'contact' ],
			'embedded whitespace'        => [ 'https://exa mple.com' ],
			'embedded newline'           => [ "https://example.com/\njavascript:alert(1)" ],
			'telephone without number'   => [ 'tel:' ],
			'telephone with letters'     => [ 'tel:0800-ELECTRIC' ],
			'telephone too short'        => [ 'tel:12' ],
			'telephone too long'         => [ 'tel:+1234567890123456' ],
			'telephone with injection'   => [ 'tel:123"onclick="alert(1)' ],
		];
	}
}
