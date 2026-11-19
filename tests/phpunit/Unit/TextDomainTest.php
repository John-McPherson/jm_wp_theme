<?php
/**
 * Text domain metadata tests.
 *
 * PHP translation calls are checked by the WordPress.WP.I18n sniff and
 * TypeScript calls by the @wordpress/i18n-text-domain ESLint rule. These tests
 * cover the metadata neither linter reads.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests that theme and block metadata declare the canonical text domain.
 */
final class TextDomainTest extends TestCase {

	/**
	 * The theme's canonical text domain.
	 */
	private const TEXT_DOMAIN = 'jmc-theme';

	/**
	 * Block metadata files keyed by path relative to the theme root.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function block_metadata_files(): array {
		$theme_root = dirname( __DIR__, 3 );
		$files      = [];

		foreach ( glob( $theme_root . '/src/blocks/*/*/block.json' ) as $path ) {
			$files[ substr( $path, strlen( $theme_root ) + 1 ) ] = [ $path ];
		}

		return $files;
	}

	/**
	 * The theme header declares the canonical text domain.
	 */
	public function test_theme_header_declares_the_text_domain(): void {
		$stylesheet = (string) file_get_contents( dirname( __DIR__, 3 ) . '/style.css' );

		$this->assertSame( 1, preg_match( '/^Text Domain:\s*(.+)$/m', $stylesheet, $matches ) );
		$this->assertSame( self::TEXT_DOMAIN, trim( $matches[1] ) );
	}

	/**
	 * Every block declares the canonical text domain.
	 *
	 * @dataProvider block_metadata_files
	 *
	 * @param string $path Block metadata file path.
	 */
	public function test_block_metadata_declares_the_text_domain( string $path ): void {
		$metadata = json_decode( (string) file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

		$this->assertSame( self::TEXT_DOMAIN, $metadata['textdomain'] ?? null );
	}
}
