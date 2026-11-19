<?php
/**
 * Block pattern contract tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/patterns.php';
require_once dirname( __DIR__, 3 ) . '/inc/patterns.php';

/**
 * Tests every theme pattern against the pattern taxonomy and registration
 * conventions and against the block contracts in each block.json.
 */
final class PatternsTest extends TestCase {

	/**
	 * Attributes WordPress adds to every block through supports or locking.
	 */
	private const SHARED_ATTRIBUTES = [ 'anchor', 'className', 'lock', 'metadata' ];

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
	 * Pattern files keyed by file name.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function pattern_files(): array {
		$files = [];

		foreach ( glob( dirname( __DIR__, 3 ) . '/patterns/*.php' ) as $path ) {
			$files[ basename( $path ) ] = [ $path ];
		}

		return $files;
	}

	/**
	 * Read a pattern file's header fields.
	 *
	 * @param string $path Pattern file path.
	 * @return array<string, string>
	 */
	private function headers( string $path ): array {
		preg_match_all(
			'/^\s*\*\s*([A-Za-z ]+):\s*(.+)$/m',
			(string) file_get_contents( $path ),
			$matches,
			PREG_SET_ORDER
		);

		$headers = [];

		foreach ( $matches as $match ) {
			$headers[ trim( $match[1] ) ] = trim( $match[2] );
		}

		return $headers;
	}

	/**
	 * Read a pattern file's block markup, after its PHP header.
	 *
	 * @param string $path Pattern file path.
	 * @return string
	 */
	private function markup( string $path ): string {
		$parts = explode( '?>', (string) file_get_contents( $path ), 2 );

		return trim( $parts[1] ?? '' );
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
			'/<!--\s+(\/)?wp:([a-z0-9-]+\/[a-z0-9-]+)(?:\s+(\{.*?\}))?\s+(\/)?-->/s',
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
	 * At least one pattern ships with the theme.
	 */
	public function test_theme_has_patterns(): void {
		$this->assertNotEmpty( self::pattern_files() );
	}

	/**
	 * Registered categories use the jmc- namespace from the documented taxonomy.
	 */
	public function test_categories_follow_the_taxonomy(): void {
		$taxonomy = [
			'jmc-navigation',
			'jmc-cta',
			'jmc-hero',
			'jmc-services',
			'jmc-content',
			'jmc-trust',
			'jmc-contact',
			'jmc-footer',
		];

		foreach ( jmc_pattern_categories() as $slug => $label ) {
			$this->assertContains( $slug, $taxonomy );
			$this->assertNotSame( '', $label );
		}
	}

	/**
	 * The init hook registers every category with its editor label.
	 */
	public function test_init_registers_categories(): void {
		$GLOBALS['jmc_test_pattern_categories'] = [];

		foreach ( $GLOBALS['jmc_test_actions']['init'] ?? [] as [ $callback ] ) {
			if ( $callback instanceof Closure ) {
				$callback();
			}
		}

		foreach ( jmc_pattern_categories() as $slug => $label ) {
			$this->assertSame( [ 'label' => $label ], $GLOBALS['jmc_test_pattern_categories'][ $slug ] ?? null );
		}
	}

	/**
	 * Headers name the pattern after its file and use registered categories.
	 *
	 * @dataProvider pattern_files
	 *
	 * @param string $path Pattern file path.
	 */
	public function test_header_follows_conventions( string $path ): void {
		$headers = $this->headers( $path );

		foreach ( [ 'Title', 'Slug', 'Categories', 'Description' ] as $field ) {
			$this->assertNotEmpty( $headers[ $field ] ?? '', "Missing {$field} header." );
		}

		$this->assertSame( 'jmc/' . basename( $path, '.php' ), $headers['Slug'] );
		$this->assertMatchesRegularExpression( '/^jmc\/[a-z0-9]+(-[a-z0-9]+)*$/', $headers['Slug'] );

		foreach ( array_map( 'trim', explode( ',', $headers['Categories'] ) ) as $category ) {
			$this->assertArrayHasKey( $category, jmc_pattern_categories(), "{$category} is not registered." );
		}
	}

	/**
	 * Pattern markup holds only static block markup, with no executable PHP.
	 *
	 * @dataProvider pattern_files
	 *
	 * @param string $path Pattern file path.
	 */
	public function test_markup_is_static( string $path ): void {
		$markup = $this->markup( $path );

		$this->assertNotSame( '', $markup );
		$this->assertStringNotContainsString( '<?php', $markup );
		$this->assertStringNotContainsString( 'style=', $markup );
	}

	/**
	 * Every block exists and every attribute matches its block.json contract.
	 *
	 * @dataProvider pattern_files
	 *
	 * @param string $path Pattern file path.
	 */
	public function test_blocks_match_their_contracts( string $path ): void {
		$blocks = $this->parse( $this->markup( $path ) );

		$this->assertNotEmpty( $blocks );

		foreach ( $blocks as $block ) {
			$name = $block['name'];

			$this->assertArrayHasKey( $name, self::$blocks, "{$name} is not a theme block." );

			$metadata = self::$blocks[ $name ];
			$declared = $metadata['attributes'] ?? [];

			foreach ( $block['attributes'] as $key => $value ) {
				if ( in_array( $key, self::SHARED_ATTRIBUTES, true ) ) {
					continue;
				}

				$this->assertArrayHasKey( $key, $declared, "{$name} does not declare {$key}." );

				if ( isset( $declared[ $key ]['enum'] ) ) {
					$this->assertContains( $value, $declared[ $key ]['enum'], "{$name} {$key} is outside its enum." );
				}
			}

			if ( isset( $metadata['parent'] ) ) {
				$this->assertContains( end( $block['ancestors'] ), $metadata['parent'], "{$name} has the wrong parent." );
			}

			if ( isset( $metadata['ancestor'] ) ) {
				$this->assertNotEmpty(
					array_intersect( $block['ancestors'], $metadata['ancestor'] ),
					"{$name} is outside its allowed ancestors."
				);
			}
		}
	}

	/**
	 * Placeholder copy contains no dead links or invented contact details.
	 *
	 * @dataProvider pattern_files
	 *
	 * @param string $path Pattern file path.
	 */
	public function test_placeholders_are_safe( string $path ): void {
		$markup = $this->markup( $path );

		$this->assertDoesNotMatchRegularExpression( '/example\.com/i', $markup );
		$this->assertDoesNotMatchRegularExpression( '/"url"\s*:\s*"(#|https?:)/', $markup, 'Use a site-relative path or a contact button.' );
		$this->assertDoesNotMatchRegularExpression( '/tel:|mailto:|wa\.me|@[a-z0-9-]+\./i', $markup, 'Contact details come from Settings → Business.' );
		$this->assertDoesNotMatchRegularExpression( '/\d{3}[\s-]?\d{3,4}[\s-]?\d{3,4}/', $markup, 'Do not invent phone numbers.' );
	}

	/**
	 * Each heading level is set explicitly, and only the hero uses a level-one heading.
	 *
	 * @dataProvider pattern_files
	 *
	 * @param string $path Pattern file path.
	 */
	public function test_headings_fit_beneath_the_page( string $path ): void {
		foreach ( $this->parse( $this->markup( $path ) ) as $block ) {
			if ( 'jmc/heading' !== $block['name'] ) {
				continue;
			}

			$this->assertArrayHasKey( 'level', $block['attributes'], 'Set the heading level explicitly.' );

			if ( '1' === $block['attributes']['level'] ) {
				$this->assertContains( 'jmc/hero', $block['ancestors'], 'Only the page hero may use a level-one heading.' );
			}
		}
	}
}
