<?php
/**
 * Hero breadcrumb home icon tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/blocks.php';
require_once dirname( __DIR__ ) . '/doubles/templates.php';

/**
 * Verify that the hero trail starts with a home icon link.
 */
final class BreadcrumbsTest extends TestCase {

	/**
	 * Trail as core renders it without the home item.
	 */
	private const TRAIL = '<nav class="wp-block-breadcrumbs jmc-hero__breadcrumbs" aria-label="Breadcrumbs"><ol><li><a href="https://example.com/services/">Services</a></li><li><span aria-current="page">Domestic</span></li></ol></nav>';

	/**
	 * Load the breadcrumbs hooks once without leaking them to other tests.
	 */
	public static function setUpBeforeClass(): void {
		$filters = $GLOBALS['jmc_test_filters'] ?? [];

		require_once dirname( __DIR__, 3 ) . '/inc/breadcrumbs.php';

		$GLOBALS['jmc_test_filters'] = $filters;
	}

	/**
	 * The hero trail gets a labelled home icon link as its first item.
	 */
	public function test_hero_trail_starts_with_the_home_icon(): void {
		$html = jmc_breadcrumbs_home_icon(
			self::TRAIL,
			[ 'attrs' => [ 'className' => 'is-style-x jmc-hero__breadcrumbs' ] ]
		);

		self::assertStringStartsWith(
			'<nav class="wp-block-breadcrumbs jmc-hero__breadcrumbs" aria-label="Breadcrumbs"><ol>'
			. '<li class="jmc-hero__breadcrumbs-home"><a href="https://example.com/" aria-label="Home">'
			. '<svg class="jmc-hero__breadcrumbs-icon" data-icon="jmc/house" aria-hidden="true" focusable="false"></svg>'
			. '</a></li><li><a href="https://example.com/services/">Services</a></li>',
			$html
		);
		self::assertSame( 1, substr_count( $html, '<ol>' ) );
		self::assertSame( 3, substr_count( $html, '<li' ) );
	}

	/**
	 * Markup the filter leaves alone.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function untouched_provider(): array {
		return [
			'other breadcrumbs block' => [ self::TRAIL, [ 'attrs' => [ 'className' => 'jmc-breadcrumbs' ] ] ],
			'no class name'           => [ self::TRAIL, [ 'attrs' => [] ] ],
			'malformed class name'    => [ self::TRAIL, [ 'attrs' => [ 'className' => [ 'jmc-hero__breadcrumbs' ] ] ] ],
			'class name prefix only'  => [ self::TRAIL, [ 'attrs' => [ 'className' => 'jmc-hero__breadcrumbs-extra' ] ] ],
			'empty trail'             => [ '', [ 'attrs' => [ 'className' => 'jmc-hero__breadcrumbs' ] ] ],
		];
	}

	/**
	 * Other Breadcrumbs blocks and empty trails are returned unchanged.
	 *
	 * @dataProvider untouched_provider
	 *
	 * @param string               $content Rendered markup.
	 * @param array<string, mixed> $block   Parsed block.
	 */
	public function test_leaves_other_markup_unchanged( string $content, array $block ): void {
		self::assertSame( $content, jmc_breadcrumbs_home_icon( $content, $block ) );
	}
}
