<?php
/**
 * Theme favicon tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/favicons.php';

/**
 * Verify the fallback favicon links and when they are printed.
 */
final class FaviconsTest extends TestCase {

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_has_site_icon'] );

		parent::tearDown();
	}

	/**
	 * Without a Site Icon, the theme links its ICO, SVG and touch icons in that order.
	 */
	public function test_links_theme_favicons_without_a_site_icon(): void {
		$base = 'https://example.com/wp-content/themes/amc_electrical/build/assets/favicons/';

		self::assertSame(
			'<link rel="icon" href="' . $base . 'favicon.ico" sizes="32x32">' . "\n"
			. '<link rel="icon" href="' . $base . 'favicon.svg" type="image/svg+xml">' . "\n"
			. '<link rel="apple-touch-icon" href="' . $base . 'apple-touch-icon.png">' . "\n",
			jmc_get_favicon_links()
		);
	}

	/**
	 * A Site Icon chosen in WordPress replaces the theme favicons.
	 */
	public function test_prints_nothing_when_a_site_icon_is_set(): void {
		$GLOBALS['jmc_test_has_site_icon'] = true;

		self::assertSame( '', jmc_get_favicon_links() );
	}

	/**
	 * The favicons print wherever core prints the Site Icon.
	 */
	public function test_hooks_front_end_admin_and_login_heads(): void {
		foreach ( [ 'wp_head', 'admin_head', 'login_head' ] as $hook ) {
			self::assertContains(
				[ 'jmc_print_favicons', 10 ],
				$GLOBALS['jmc_test_actions'][ $hook ] ?? [],
				$hook
			);
		}
	}

	/**
	 * Every linked favicon ships in the theme's source assets.
	 */
	public function test_ships_every_linked_favicon(): void {
		foreach ( [ 'favicon.ico', 'favicon.svg', 'apple-touch-icon.png' ] as $file ) {
			self::assertFileExists( dirname( __DIR__, 3 ) . '/src/assets/favicons/' . $file );
		}
	}
}
