<?php
/**
 * Contact and 404 template contract tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests the landmarks, heading flow, the absence of search, recovery actions and block
 * contracts of `page-contact.html` and `404.html`.
 */
final class ContactNotFoundTemplatesTest extends TestCase {

	/**
	 * Attributes WordPress adds to every block through supports or locking.
	 */
	private const SHARED_ATTRIBUTES = [ 'anchor', 'className', 'lock', 'metadata' ];

	/**
	 * Starter patterns that stand in for a template's page content, so the
	 * page a template governs can be checked as a whole.
	 */
	private const PAGE_CONTENT = [
		'templates/page-contact.html' => 'jmc/contact-page',
	];

	/**
	 * Theme root directory.
	 *
	 * @var string
	 */
	private static string $theme_root;

	/**
	 * Theme block metadata keyed by block name.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $blocks = [];

	/**
	 * Load block metadata once for every test.
	 */
	public static function setUpBeforeClass(): void {
		self::$theme_root = dirname( __DIR__, 3 );

		foreach ( glob( self::$theme_root . '/src/blocks/*/*/block.json' ) as $path ) {
			$metadata = json_decode( (string) file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );

			self::$blocks[ $metadata['name'] ] = $metadata;
		}
	}

	/**
	 * Template files under test.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function template_files(): array {
		return [
			'contact' => [ 'templates/page-contact.html' ],
			'404'     => [ 'templates/404.html' ],
		];
	}

	/**
	 * Read a required theme file.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 * @return string
	 */
	private function read_theme_file( string $relative_path ): string {
		$path = self::$theme_root . '/' . $relative_path;

		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Read a pattern's block markup, after its PHP header.
	 *
	 * @param string $slug Pattern slug, such as `jmc/contact-details`.
	 * @return string
	 */
	private function pattern_markup( string $slug ): string {
		foreach ( glob( self::$theme_root . '/patterns/*.php' ) as $path ) {
			$contents = (string) file_get_contents( $path );

			if ( 1 === preg_match( '/^\s*\*\s*Slug:\s*' . preg_quote( $slug, '/' ) . '\s*$/m', $contents ) ) {
				$parts = explode( '?>', $contents, 2 );

				return trim( $parts[1] ?? '' );
			}
		}

		$this->fail( "Pattern {$slug} is not registered by a file in patterns/." );
	}

	/**
	 * Parse block comment delimiters into a flat list in document order, with
	 * each block's ancestors. Core blocks get their implicit `core/` namespace.
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
			$name = str_contains( $match[2], '/' ) ? $match[2] : 'core/' . $match[2];

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
	 * Parse a template, replacing each pattern reference with the pattern's
	 * blocks, nested beneath the reference's ancestors. Page content is
	 * replaced with the template's starter pattern, when it has one.
	 *
	 * @param string $relative_path Template path relative to the theme root.
	 * @return array<int, array{name: string, attributes: array<string, mixed>, ancestors: array<int, string>}>
	 */
	private function parse_template( string $relative_path ): array {
		$blocks = [];

		foreach ( $this->parse( $this->read_theme_file( $relative_path ) ) as $block ) {
			$slug = match ( true ) {
				'core/pattern' === $block['name']      => (string) ( $block['attributes']['slug'] ?? '' ),
				'core/post-content' === $block['name'] => self::PAGE_CONTENT[ $relative_path ] ?? null,
				default                                => null,
			};

			if ( null === $slug ) {
				$blocks[] = $block;
				continue;
			}

			foreach ( $this->parse( $this->pattern_markup( $slug ) ) as $inner ) {
				$inner['ancestors'] = array_merge( $block['ancestors'], $inner['ancestors'] );
				$blocks[]           = $inner;
			}
		}

		return $blocks;
	}

	/**
	 * Blocks of one type.
	 *
	 * @param array<int, array{name: string, attributes: array<string, mixed>, ancestors: array<int, string>}> $blocks Parsed blocks.
	 * @param string                                                                                           $name   Block name.
	 * @return array<int, array{name: string, attributes: array<string, mixed>, ancestors: array<int, string>}>
	 */
	private function blocks_named( array $blocks, string $name ): array {
		return array_values(
			array_filter(
				$blocks,
				static fn ( array $block ): bool => $name === $block['name']
			)
		);
	}

	/**
	 * Heading levels in document order, with each block's default level.
	 *
	 * @param array<int, array{name: string, attributes: array<string, mixed>, ancestors: array<int, string>}> $blocks Parsed blocks.
	 * @return array<int, int>
	 */
	private function heading_levels( array $blocks ): array {
		$defaults = [
			'jmc/heading'      => 2,
			'core/heading'     => 2,
			'core/post-title'  => 2,
			'core/query-title' => 1,
		];

		$levels = [];

		foreach ( $blocks as $block ) {
			if ( isset( $defaults[ $block['name'] ] ) ) {
				$levels[] = (int) ( $block['attributes']['level'] ?? $defaults[ $block['name'] ] );
			}
		}

		return $levels;
	}

	/**
	 * Every template opens with the header, closes with the footer, and puts
	 * everything else in a single `<main>` landmark.
	 *
	 * @dataProvider template_files
	 *
	 * @param string $path Template path.
	 */
	public function test_has_one_main_landmark_between_header_and_footer( string $path ): void {
		$markup = $this->read_theme_file( $path );
		$blocks = $this->parse( $markup );

		$top_level = array_values(
			array_filter(
				$blocks,
				static fn ( array $block ): bool => [] === $block['ancestors']
			)
		);

		$this->assertCount( 3, $top_level, 'Expected the header, the main group and the footer.' );

		$this->assertSame( 'core/template-part', $top_level[0]['name'] );
		$this->assertSame( 'header', $top_level[0]['attributes']['slug'] ?? null );
		$this->assertSame( 'header', $top_level[0]['attributes']['tagName'] ?? null );

		$this->assertSame( 'core/group', $top_level[1]['name'] );
		$this->assertSame( 'main', $top_level[1]['attributes']['tagName'] ?? null );

		$this->assertSame( 'core/template-part', $top_level[2]['name'] );
		$this->assertSame( 'footer', $top_level[2]['attributes']['slug'] ?? null );
		$this->assertSame( 'footer', $top_level[2]['attributes']['tagName'] ?? null );

		$this->assertSame( 1, preg_match_all( '/<main\b/', $markup ), 'Exactly one <main> element.' );
		$this->assertMatchesRegularExpression( '/<main class="wp-block-group jmc-main\b/', $markup );
		$this->assertDoesNotMatchRegularExpression( '/role="main"/', $markup );
	}

	/**
	 * Every template has one level-one heading, first, and never skips a level.
	 *
	 * @dataProvider template_files
	 *
	 * @param string $path Template path.
	 */
	public function test_heading_flow_is_valid( string $path ): void {
		$levels = $this->heading_levels( $this->parse_template( $path ) );

		$this->assertNotEmpty( $levels );
		$this->assertSame( 1, $levels[0], 'The first heading must be the level-one page heading.' );
		$this->assertSame( 1, count( array_keys( $levels, 1, true ) ), 'Exactly one level-one heading.' );

		for ( $i = 1, $count = count( $levels ); $i < $count; $i++ ) {
			$this->assertLessThanOrEqual(
				$levels[ $i - 1 ] + 1,
				$levels[ $i ],
				sprintf( 'Heading level %d follows level %d.', $levels[ $i ], $levels[ $i - 1 ] )
			);
		}
	}

	/**
	 * Theme blocks use only declared attributes and supported enum values, and
	 * respect their parent and ancestor contracts.
	 *
	 * @dataProvider template_files
	 *
	 * @param string $path Template path.
	 */
	public function test_theme_blocks_match_their_contracts( string $path ): void {
		foreach ( $this->parse_template( $path ) as $block ) {
			if ( ! str_starts_with( $block['name'], 'jmc/' ) ) {
				continue;
			}

			$this->assertArrayHasKey( $block['name'], self::$blocks, "{$block['name']} is not a theme block." );

			$metadata = self::$blocks[ $block['name'] ];

			foreach ( $block['attributes'] as $key => $value ) {
				if ( in_array( $key, self::SHARED_ATTRIBUTES, true ) ) {
					continue;
				}

				$this->assertArrayHasKey( $key, $metadata['attributes'] ?? [], "{$block['name']} has no {$key} attribute." );

				if ( isset( $metadata['attributes'][ $key ]['enum'] ) ) {
					$this->assertContains( $value, $metadata['attributes'][ $key ]['enum'], "{$block['name']} {$key} is not supported." );
				}
			}

			if ( isset( $metadata['parent'] ) ) {
				$this->assertContains( end( $block['ancestors'] ), $metadata['parent'], "{$block['name']} is outside its parent." );
			}

			if ( isset( $metadata['ancestor'] ) ) {
				$this->assertNotEmpty(
					array_intersect( $metadata['ancestor'], $block['ancestors'] ),
					"{$block['name']} is outside its allowed ancestors."
				);
			}
		}
	}

	/**
	 * Site search is turned off, so no template offers a search form.
	 *
	 * @dataProvider template_files
	 *
	 * @param string $path Template path.
	 */
	public function test_does_not_offer_search( string $path ): void {
		$this->assertSame( [], $this->blocks_named( $this->parse_template( $path ), 'core/search' ) );
	}

	/**
	 * Business details come from Settings → Business, never the template.
	 *
	 * @dataProvider template_files
	 *
	 * @param string $path Template path.
	 */
	public function test_does_not_hard_code_contact_details( string $path ): void {
		$markup = $this->read_theme_file( $path );

		$this->assertDoesNotMatchRegularExpression( '/tel:|mailto:|wa\.me|@[a-z0-9-]+\./i', $markup );
		$this->assertDoesNotMatchRegularExpression( '/\d{3}[\s-]?\d{3,4}[\s-]?\d{3,4}/', $markup );
		$this->assertDoesNotMatchRegularExpression( '/https?:\/\//', $markup, 'Use site-relative paths.' );
		$this->assertStringNotContainsStringIgnoringCase( 'amc electrical', $markup );
	}

	/**
	 * The contact template is selectable for pages, and WordPress also applies
	 * it to the page with the `contact` slug.
	 */
	public function test_theme_json_registers_contact_template(): void {
		$theme_json = json_decode( $this->read_theme_file( 'theme.json' ), true, 512, JSON_THROW_ON_ERROR );

		$contact = array_values(
			array_filter(
				$theme_json['customTemplates'] ?? [],
				static fn ( array $template ): bool => 'page-contact' === ( $template['name'] ?? null )
			)
		);

		$this->assertCount( 1, $contact );
		$this->assertNotEmpty( $contact[0]['title'] );
		$this->assertSame( [ 'page' ], $contact[0]['postTypes'] );

		foreach ( $theme_json['customTemplates'] as $template ) {
			$this->assertFileExists( self::$theme_root . '/templates/' . $template['name'] . '.html' );
		}
	}

	/**
	 * The contact template holds only the page's own content, so editors own
	 * the copy; its starter pattern seeds the page heading, the enquiry form,
	 * the business contact details and a closing call to action, in that
	 * order, each locked in place.
	 */
	public function test_contact_page_content_has_the_contact_sections(): void {
		$template = $this->parse( $this->read_theme_file( 'templates/page-contact.html' ) );
		$in_main  = array_values(
			array_filter(
				$template,
				static fn ( array $block ): bool => [ 'core/group' ] === $block['ancestors']
			)
		);

		$this->assertSame( [ 'core/post-content' ], array_column( $in_main, 'name' ), 'The main group holds nothing but the page content.' );
		$this->assertSame(
			[
				'move'   => true,
				'remove' => true,
			],
			$in_main[0]['attributes']['lock'] ?? null
		);

		$pattern = $this->read_theme_file( 'patterns/contact-page.php' );

		$this->assertMatchesRegularExpression( '/^\s*\*\s*Block Types:\s*core\/post-content\s*$/m', $pattern );
		$this->assertMatchesRegularExpression( '/^\s*\*\s*Post Types:\s*page\s*$/m', $pattern );

		$sections = array_values(
			array_filter(
				$this->parse( $this->pattern_markup( 'jmc/contact-page' ) ),
				static fn ( array $block ): bool => [] === $block['ancestors']
			)
		);

		$this->assertSame(
			[ 'jmc/hero', 'jmc/enquiry-form', 'jmc/cta-banner', 'jmc/cta-banner' ],
			array_column( $sections, 'name' )
		);
		$this->assertSame( 'contact', $sections[2]['attributes']['layout'] ?? null );

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

		$expanded = $this->parse_template( 'templates/page-contact.html' );

		$this->assertCount( 1, $this->blocks_named( $expanded, 'jmc/contact-details' ) );
		$this->assertCount( 1, $this->blocks_named( $expanded, 'jmc/enquiry-form' ), 'The enquiry form allows one per page.' );
	}

	/**
	 * The 404 template explains the problem and offers a way home and a call
	 * to action.
	 */
	public function test_not_found_template_offers_recovery(): void {
		$blocks = $this->parse_template( 'templates/404.html' );

		$this->assertCount( 1, $this->blocks_named( $blocks, 'jmc/hero' ) );

		$home_links = array_filter(
			$this->blocks_named( $blocks, 'jmc/button' ),
			static fn ( array $button ): bool => '/' === ( $button['attributes']['link']['url'] ?? null )
		);
		$this->assertCount( 1, $home_links, 'Offer a link to the homepage.' );

		$this->assertNotEmpty( $this->blocks_named( $blocks, 'jmc/cta-banner' ), 'Offer the call to action.' );

		$markup = $this->read_theme_file( 'templates/404.html' );

		$this->assertStringNotContainsString( 'wp:pattern', $markup, 'Patterns carry placeholder copy; the 404 has its own.' );
		$this->assertStringNotContainsStringIgnoringCase( 'lorem ipsum', $markup );
	}
}
