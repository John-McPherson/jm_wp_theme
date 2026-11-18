<?php
/**
 * Logo colour token contract tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests that every logo token resolves to a semantic palette colour.
 *
 * The logo tokens must alias `--jmc-color-*` tokens (which alias the
 * `theme.json` palette), never literal colours, so palette changes and global
 * style variations reach the logo.
 */
final class LogoTokensTest extends TestCase {

	/**
	 * Logo token mappings per palette and block style.
	 *
	 * @return array<string, array{0: string, 1: string, 2: array<string, string>}>
	 */
	public static function mapping_provider(): array {
		return [
			'default palette'   => [
				'src/scss/tokens/_semantic.scss',
				':root',
				[
					'primary'   => 'brand-900',
					'accent'    => 'accent-500',
					'secondary' => 'base-600',
				],
			],
			'secondary palette' => [
				'src/scss/palettes/_secondary.scss',
				'.jmc-palette--secondary',
				[
					'primary'   => 'brand-900',
					'accent'    => 'accent-600',
					'secondary' => 'base-600',
				],
			],
			'inverse palette'   => [
				'src/scss/palettes/_inverse.scss',
				'.jmc-palette--inverse',
				[
					'primary'   => 'base-50',
					'accent'    => 'accent-300',
					'secondary' => 'base-300',
				],
			],
			'brand style'       => [
				'src/blocks/site/site-logo/style.scss',
				'&.is-style-brand',
				[
					'primary'   => 'brand-900',
					'accent'    => 'accent-500',
					'secondary' => 'base-600',
				],
			],
			'mono dark style'   => [
				'src/blocks/site/site-logo/style.scss',
				'&.is-style-mono-dark',
				[
					'primary'   => 'brand-900',
					'accent'    => 'brand-900',
					'secondary' => 'brand-900',
				],
			],
			'mono light style'  => [
				'src/blocks/site/site-logo/style.scss',
				'&.is-style-mono-light',
				[
					'primary'   => 'base-50',
					'accent'    => 'base-50',
					'secondary' => 'base-50',
				],
			],
		];
	}

	/**
	 * Each palette and style sets all three logo tokens to semantic colours.
	 *
	 * @dataProvider mapping_provider
	 *
	 * @param string                $file     Stylesheet relative to the theme root.
	 * @param string                $selector Selector that opens the rule.
	 * @param array<string, string> $expected Expected colour per logo token.
	 */
	public function test_logo_tokens_alias_semantic_colours( string $file, string $selector, array $expected ): void {
		$scss = (string) file_get_contents( dirname( __DIR__, 3 ) . '/' . $file );

		$start = strpos( $scss, $selector . ' {' );

		self::assertNotFalse( $start, "{$selector} not found in {$file}." );

		$rule = substr( $scss, $start, (int) strpos( $scss, '}', $start ) - $start );

		preg_match_all( '/--jmc-logo-([a-z]+):\s*([^;]+);/', $rule, $matches, PREG_SET_ORDER );

		$actual = [];

		foreach ( $matches as $match ) {
			self::assertMatchesRegularExpression(
				'/^var\(--jmc-color-[a-z]+-\d+\)$/',
				trim( $match[2] ),
				"--jmc-logo-{$match[1]} in {$selector} must alias a --jmc-color-* token."
			);

			$actual[ $match[1] ] = (string) preg_replace( '/^var\(--jmc-color-(.+)\)$/', '$1', trim( $match[2] ) );
		}

		self::assertSame( $expected, $actual );
	}

	/**
	 * The theme's logo artwork contains no literal colours.
	 */
	public function test_logo_artwork_has_no_literal_colours(): void {
		$svg = (string) file_get_contents( dirname( __DIR__, 3 ) . '/src/assets/svgs/logo.svg' );

		self::assertDoesNotMatchRegularExpression( '/#[0-9a-f]{3,8}\b|rgba?\(|\b(fill|stroke|style)=|<style/i', $svg );
	}
}
