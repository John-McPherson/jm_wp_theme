<?php
/**
 * Front-page and page template contract tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/templates.php';
require_once dirname( __DIR__, 3 ) . '/inc/templates.php';

/**
 * Tests the landmarks, heading and content structure of the page templates,
 * and the homepage's starter pattern.
 */
final class PageTemplatesTest extends TestCase {

	/**
	 * Page templates keyed by file name.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function page_templates(): array {
		return [
			'front-page.html' => [ 'templates/front-page.html' ],
			'page.html'       => [ 'templates/page.html' ],
		];
	}

	/**
	 * Read a required theme file.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 * @return string
	 */
	private function read_theme_file( string $relative_path ): string {
		$path = dirname( __DIR__, 3 ) . '/' . $relative_path;

		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Parse block comment delimiters into a flat list with each block's ancestors.
	 *
	 * Fails the test on unbalanced delimiters or invalid attribute JSON.
	 *
	 * @param string $markup Block markup.
	 * @return array<int, array{name: string, attributes: array<string, mixed>, ancestors: array<int, string>}>
	 */
	private function parse( string $markup ): array {
		preg_match_all(
			'/<!--\s+(\/)?wp:([a-z0-9-]+(?:\/[a-z0-9-]+)?)(?:\s+(\{.*?\}))?\s+(\/)?-->/s',
			$markup,
			$matches,
			PREG_SET_ORDER
		);

		$blocks = [];
		$stack  = [];

		foreach ( $matches as $match ) {
			$name = $match[2];

			if ( '/' === $match[1] ) {
				$this->assertSame( $name, array_pop( $stack ), "Closing {$name} does not match its opening block." );
				continue;
			}

			$attributes = [];

			if ( '' !== ( $match[3] ?? '' ) ) {
				$attributes = json_decode( $match[3], true );
				$this->assertIsArray( $attributes, "{$name} has invalid attribute JSON." );
			}

			$blocks[] = [
				'name'       => $name,
				'attributes' => $attributes,
				'ancestors'  => $stack,
			];

			if ( '/' !== ( $match[4] ?? '' ) ) {
				$stack[] = $name;
			}
		}

		$this->assertSame( [], $stack, 'Every block must be closed.' );

		return $blocks;
	}

	/**
	 * The template is the header part, one main landmark, then the footer part.
	 *
	 * @dataProvider page_templates
	 *
	 * @param string $path Template path.
	 */
	public function test_has_header_main_and_footer_landmarks( string $path ): void {
		$template = $this->read_theme_file( $path );
		$roots    = array_values(
			array_filter(
				$this->parse( $template ),
				static fn ( array $block ): bool => [] === $block['ancestors']
			)
		);

		$this->assertCount( 3, $roots );

		$this->assertSame( 'template-part', $roots[0]['name'] );
		$this->assertSame( 'header', $roots[0]['attributes']['slug'] ?? null );
		$this->assertSame( 'header', $roots[0]['attributes']['tagName'] ?? null );

		$this->assertSame( 'group', $roots[1]['name'] );
		$this->assertSame( 'main', $roots[1]['attributes']['tagName'] ?? null );
		$this->assertStringContainsString( 'site-main', $roots[1]['attributes']['className'] ?? '' );

		$this->assertSame( 'template-part', $roots[2]['name'] );
		$this->assertSame( 'footer', $roots[2]['attributes']['slug'] ?? null );
		$this->assertSame( 'footer', $roots[2]['attributes']['tagName'] ?? null );

		$this->assertSame( 1, preg_match_all( '/<main\b/', $template ), 'Exactly one <main> element.' );
		$this->assertSame( 1, preg_match_all( '/"tagName"\s*:\s*"main"/', $template ), 'Exactly one main group.' );
	}

	/**
	 * The main landmark holds the level-one page title then the page content.
	 *
	 * @dataProvider page_templates
	 *
	 * @param string $path Template path.
	 */
	public function test_main_holds_title_then_content( string $path ): void {
		$main = array_values(
			array_filter(
				$this->parse( $this->read_theme_file( $path ) ),
				static fn ( array $block ): bool => [ 'group' ] === $block['ancestors']
			)
		);

		$this->assertSame( [ 'post-title', 'post-content' ], array_column( $main, 'name' ) );
		$this->assertSame( 1, $main[0]['attributes']['level'] ?? null, 'The page title is the H1.' );
		$this->assertStringContainsString( 'jmc-page-title', $main[0]['attributes']['className'] ?? '' );
	}

	/**
	 * The page title is the only heading the template supplies, and the template
	 * holds no copy, placeholder or prototype content of its own.
	 *
	 * @dataProvider page_templates
	 *
	 * @param string $path Template path.
	 */
	public function test_has_no_hard_coded_content( string $path ): void {
		$template = $this->read_theme_file( $path );
		$names    = array_column( $this->parse( $template ), 'name' );

		$this->assertSame( 1, count( array_keys( $names, 'post-title', true ) ) );
		$this->assertSame( [], array_values( array_diff( $names, [ 'template-part', 'group', 'post-title', 'post-content' ] ) ) );
		$this->assertDoesNotMatchRegularExpression( '/<h[1-6]\b/i', $template );
		$this->assertDoesNotMatchRegularExpression( '/\b(todo|fixme|lorem|ipsum|placeholder|prototype)\b/i', $template );
		$this->assertDoesNotMatchRegularExpression( '/\b(href|src)\s*=/i', $template );

		// Only the main element's tags are written out; everything else is a block delimiter.
		$this->assertSame(
			'<main class="wp-block-group site-main"> </main>',
			trim( (string) preg_replace( '/\s+/', ' ', (string) preg_replace( '/<!--.*?-->/s', '', $template ) ) )
		);
	}

	/**
	 * The homepage's sections come from the `jmc/home-page` starter pattern,
	 * offered for new pages: the hero (whose H1 replaces the template title),
	 * an introduction, the services, reasons to choose the business and the
	 * contact details, in that order, each locked in place.
	 */
	public function test_home_page_starter_pattern(): void {
		$pattern = $this->read_theme_file( 'patterns/home-page.php' );

		$this->assertMatchesRegularExpression( '/^\s*\*\s*Slug:\s*jmc\/home-page\s*$/m', $pattern );
		$this->assertMatchesRegularExpression( '/^\s*\*\s*Block Types:\s*core\/post-content\s*$/m', $pattern );
		$this->assertMatchesRegularExpression( '/^\s*\*\s*Post Types:\s*page\s*$/m', $pattern );

		$blocks   = $this->parse( explode( '?>', $pattern, 2 )[1] ?? '' );
		$sections = array_values(
			array_filter(
				$blocks,
				static fn ( array $block ): bool => [] === $block['ancestors']
			)
		);

		$this->assertSame(
			[ 'jmc/hero', 'jmc/text-with-image', 'jmc/service-grid', 'jmc/text-with-image', 'jmc/cta-banner' ],
			array_column( $sections, 'name' )
		);
		$this->assertSame( 'contact', $sections[4]['attributes']['layout'] ?? null );

		foreach ( $sections as $section ) {
			$this->assertSame(
				[
					'move'   => true,
					'remove' => true,
				],
				$section['attributes']['lock'] ?? null,
				"{$section['name']} is locked in place."
			);
		}

		$names = array_column( $blocks, 'name' );

		$this->assertContains( 'jmc/service-list', $names );
		$this->assertContains( 'jmc/contact-details', $names );
		$this->assertTrue( jmc_content_has_page_heading( $pattern ), 'The hero supplies the H1, so the template title is hidden.' );
	}
}
