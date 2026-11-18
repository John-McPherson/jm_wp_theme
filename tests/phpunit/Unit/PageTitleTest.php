<?php
/**
 * Page title filter tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/templates.php';
require_once dirname( __DIR__, 3 ) . '/inc/templates.php';

/**
 * Verify the page templates keep one page-level heading.
 */
final class PageTitleTest extends TestCase {

	/**
	 * Rendered level-one post title.
	 */
	private const TITLE = '<h1 class="wp-block-post-title jmc-page-title">About us</h1>';

	/**
	 * Start with no posts.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_posts'] = [];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_posts'] );
	}

	/**
	 * Store a post with the given content.
	 *
	 * @param int   $id      Post ID.
	 * @param mixed $content Post content.
	 */
	private function add_post( int $id, mixed $content ): void {
		$GLOBALS['jmc_test_posts'][ $id ] = (object) [
			'ID'           => $id,
			'post_content' => $content,
		];
	}

	/**
	 * Render the title through the filter.
	 *
	 * @param array<string,mixed> $attrs   Block attributes.
	 * @param mixed               $context Block context.
	 * @return string
	 */
	private function render( array $attrs = [ 'level' => 1 ], mixed $context = [ 'postId' => 7 ] ): string {
		return jmc_filter_page_title(
			self::TITLE,
			[
				'blockName' => 'core/post-title',
				'attrs'     => $attrs,
			],
			(object) [ 'context' => $context ]
		);
	}

	/**
	 * The filter is added to the post title with all three arguments.
	 */
	public function test_filters_the_post_title(): void {
		self::assertContains(
			[ 'jmc_filter_page_title', 10, 3 ],
			$GLOBALS['jmc_test_filters']['render_block_core/post-title'] ?? []
		);
	}

	/**
	 * Only a hero section supplies the page heading.
	 */
	public function test_detects_hero_sections(): void {
		self::assertTrue( jmc_content_has_page_heading( "<!-- wp:jmc/hero -->\n<!-- /wp:jmc/hero -->" ) );
		self::assertTrue( jmc_content_has_page_heading( '<!-- wp:jmc/hero {"palette":"inverse"} -->' ) );
		self::assertTrue( jmc_content_has_page_heading( '<!-- wp:jmc/hero /-->' ) );
		self::assertFalse( jmc_content_has_page_heading( '' ) );
		self::assertFalse( jmc_content_has_page_heading( '<!-- wp:jmc/text-with-image -->' ) );
		self::assertFalse( jmc_content_has_page_heading( '<!-- wp:jmc/hero-banner -->' ) );
		self::assertFalse( jmc_content_has_page_heading( '<p>jmc/hero</p>' ) );
	}

	/**
	 * A page without a hero keeps the title as its heading, including long
	 * content made of other sections.
	 */
	public function test_keeps_title_without_hero(): void {
		$this->add_post(
			7,
			str_repeat( "<!-- wp:jmc/text-with-image -->\n<!-- /wp:jmc/text-with-image -->\n", 20 )
		);

		self::assertSame( self::TITLE, $this->render() );
	}

	/**
	 * A page with no content keeps the title, so it still has a heading.
	 */
	public function test_keeps_title_for_empty_content(): void {
		$this->add_post( 7, '' );

		self::assertSame( self::TITLE, $this->render() );
	}

	/**
	 * A page that opens with a hero drops the title, leaving the hero's H1.
	 */
	public function test_omits_title_when_hero_supplies_heading(): void {
		$this->add_post( 7, "<!-- wp:jmc/hero -->\n<!-- /wp:jmc/hero -->\n<!-- wp:jmc/cta-banner /-->" );

		self::assertSame( '', $this->render() );
		self::assertSame( '', $this->render( [ 'level' => '1' ] ) );
	}

	/**
	 * Titles at other levels, or with the default level, are left alone.
	 */
	public function test_keeps_titles_at_other_levels(): void {
		$this->add_post( 7, '<!-- wp:jmc/hero /-->' );

		self::assertSame( self::TITLE, $this->render( [] ) );
		self::assertSame( self::TITLE, $this->render( [ 'level' => 2 ] ) );
		self::assertSame( self::TITLE, $this->render( [ 'level' => [ 1 ] ] ) );
		self::assertSame( self::TITLE, $this->render( [ 'level' => 'one' ] ) );
	}

	/**
	 * Missing or malformed context, missing posts and non-string content keep the title.
	 */
	public function test_keeps_title_without_usable_post(): void {
		$this->add_post( 7, '<!-- wp:jmc/hero /-->' );
		$this->add_post( 8, null );

		self::assertSame( self::TITLE, $this->render( context: [] ) );
		self::assertSame( self::TITLE, $this->render( context: 'postId' ) );
		self::assertSame( self::TITLE, $this->render( context: [ 'postId' => 0 ] ) );
		self::assertSame( self::TITLE, $this->render( context: [ 'postId' => 'abc' ] ) );
		self::assertSame( self::TITLE, $this->render( context: [ 'postId' => 99 ] ) );
		self::assertSame( self::TITLE, $this->render( context: [ 'postId' => 8 ] ) );
		self::assertSame(
			self::TITLE,
			jmc_filter_page_title( self::TITLE, [ 'attrs' => [ 'level' => 1 ] ] )
		);
		self::assertSame(
			self::TITLE,
			jmc_filter_page_title( self::TITLE, [ 'attrs' => 'level' ], (object) [ 'context' => [ 'postId' => 7 ] ] )
		);
	}
}
