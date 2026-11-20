<?php
/**
 * Skip-link target tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/templates.php';
require_once dirname( __DIR__, 3 ) . '/inc/templates.php';

/**
 * Verify every template has one main landmark for the WordPress skip link,
 * and that the main group can take focus from it.
 */
final class SkipLinkTargetTest extends TestCase {

	/**
	 * Rendered main group.
	 */
	private const MAIN = '<main class="wp-block-group site-main"><p>Content</p></main>';

	/**
	 * Every block template, keyed by file name.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function templates(): array {
		$paths = glob( dirname( __DIR__, 3 ) . '/templates/*.html' );
		$cases = [];

		foreach ( false === $paths ? [] : $paths as $path ) {
			$cases[ basename( $path ) ] = [ $path ];
		}

		return $cases;
	}

	/**
	 * The filter is added to the Group block with both arguments.
	 */
	public function test_filters_the_group_block(): void {
		self::assertContains(
			[ 'jmc_main_skip_link_target', 10, 2 ],
			$GLOBALS['jmc_test_filters']['render_block_core/group'] ?? []
		);
	}

	/**
	 * The theme ships templates for the provider to check.
	 */
	public function test_templates_exist(): void {
		self::assertNotEmpty( self::templates() );
	}

	/**
	 * Every template has exactly one `<main>`, a Group block, for the skip link.
	 *
	 * @dataProvider templates
	 *
	 * @param string $path Template path.
	 */
	public function test_template_has_one_main_group( string $path ): void {
		$template = (string) file_get_contents( $path );

		self::assertSame( 1, preg_match_all( '/<main\b/', $template ), 'Exactly one <main> element.' );
		self::assertSame(
			1,
			preg_match_all( '/<!--\s+wp:group\s+\{[^}]*"tagName"\s*:\s*"main"/', $template ),
			'The <main> element is a Group block.'
		);
	}

	/**
	 * The main group takes `tabindex="-1"` and keeps its other attributes.
	 */
	public function test_main_group_can_take_focus(): void {
		self::assertSame(
			'<main tabindex="-1" class="wp-block-group site-main"><p>Content</p></main>',
			jmc_main_skip_link_target( self::MAIN, [ 'attrs' => [ 'tagName' => 'main' ] ] )
		);
	}

	/**
	 * Leading whitespace in the rendered block is kept.
	 */
	public function test_keeps_leading_whitespace(): void {
		self::assertSame(
			"\n<main tabindex=\"-1\" class=\"wp-block-group\"></main>",
			jmc_main_skip_link_target( "\n<main class=\"wp-block-group\"></main>", [ 'attrs' => [ 'tagName' => 'main' ] ] )
		);
	}

	/**
	 * Markup the filter leaves alone.
	 *
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function untouched_provider(): array {
		return [
			'div group'             => [ '<div class="wp-block-group"></div>', [ 'attrs' => [] ] ],
			'section group'         => [ '<section class="wp-block-group"></section>', [ 'attrs' => [ 'tagName' => 'section' ] ] ],
			'malformed tag name'    => [ self::MAIN, [ 'attrs' => [ 'tagName' => [ 'main' ] ] ] ],
			'malformed attributes'  => [ self::MAIN, [ 'attrs' => 'main' ] ],
			'existing tabindex'     => [ '<main class="wp-block-group" tabindex="0"></main>', [ 'attrs' => [ 'tagName' => 'main' ] ] ],
			'main not first'        => [ '<div><main></main></div>', [ 'attrs' => [ 'tagName' => 'main' ] ] ],
			'similar element name'  => [ '<mainly></mainly>', [ 'attrs' => [ 'tagName' => 'main' ] ] ],
			'empty rendered output' => [ '', [ 'attrs' => [ 'tagName' => 'main' ] ] ],
		];
	}

	/**
	 * Other groups, a main with its own tabindex and unexpected markup are
	 * returned unchanged.
	 *
	 * @dataProvider untouched_provider
	 *
	 * @param string               $content Rendered markup.
	 * @param array<string, mixed> $block   Parsed block.
	 */
	public function test_leaves_other_markup_unchanged( string $content, array $block ): void {
		self::assertSame( $content, jmc_main_skip_link_target( $content, $block ) );
	}
}
