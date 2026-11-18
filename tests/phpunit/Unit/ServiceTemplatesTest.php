<?php
/**
 * Services template routing and template contract tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/services.php';
require_once dirname( __DIR__ ) . '/doubles/templates.php';

/**
 * Verify that the Services page and its direct children are routed to their
 * governed templates, and that those templates follow the content model.
 */
final class ServiceTemplatesTest extends TestCase {

	/**
	 * Services page ID used by the fixtures.
	 */
	private const SERVICES_ID = 10;

	/**
	 * Theme root directory.
	 *
	 * @var string
	 */
	private static string $theme_root;

	/**
	 * Filters registered by inc/services.php, keyed by hook name.
	 *
	 * @var array<string, callable>
	 */
	private static array $filters = [];

	/**
	 * Load the services hooks once without leaking their actions to other tests.
	 */
	public static function setUpBeforeClass(): void {
		self::$theme_root = dirname( __DIR__, 3 );

		$actions = $GLOBALS['jmc_test_actions'] ?? [];
		$filters = $GLOBALS['jmc_test_filters'] ?? [];

		require_once self::$theme_root . '/inc/services.php';

		foreach ( [ 'page_template_hierarchy', 'theme_page_templates' ] as $hook ) {
			$registered = $GLOBALS['jmc_test_filters'][ $hook ] ?? [];

			if ( [] !== $registered ) {
				self::$filters[ $hook ] = end( $registered )[0];
			}
		}

		// Other tests run every init callback; keep the services ones out of them.
		$GLOBALS['jmc_test_actions'] = $actions;
		$GLOBALS['jmc_test_filters'] = $filters;
	}

	/**
	 * Reset the queried object.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['jmc_test_queried_object'] );

		parent::tearDown();
	}

	/**
	 * Build a page-like record.
	 *
	 * @param int    $id        Page ID.
	 * @param int    $parent_id Parent page ID.
	 * @param string $status    Post status.
	 * @param string $type      Post type.
	 * @param string $slug      Page slug.
	 *
	 * @return object Page record.
	 */
	private static function page(
		int $id,
		int $parent_id = 0,
		string $status = 'publish',
		string $type = 'page',
		string $slug = ''
	): object {
		return (object) [
			'ID'          => $id,
			'post_parent' => $parent_id,
			'post_status' => $status,
			'post_type'   => $type,
			'post_name'   => '' === $slug ? "page-{$id}" : $slug,
		];
	}

	/**
	 * Read a required theme file.
	 *
	 * @param string $relative_path Path relative to the theme root.
	 *
	 * @return string File contents.
	 */
	private function read_theme_file( string $relative_path ): string {
		$path = self::$theme_root . '/' . $relative_path;

		$this->assertFileExists( $path );

		return (string) file_get_contents( $path );
	}

	/**
	 * Pages and their role beneath the Services page.
	 *
	 * @return array<string, array{0: object|null, 1: int, 2: string}>
	 */
	public function roles(): array {
		return [
			'services page'              => [ self::page( self::SERVICES_ID ), self::SERVICES_ID, 'landing' ],
			'published direct child'     => [ self::page( 11, self::SERVICES_ID ), self::SERVICES_ID, 'service' ],
			'draft direct child'         => [ self::page( 12, self::SERVICES_ID, 'draft' ), self::SERVICES_ID, 'service' ],
			'grandchild'                 => [ self::page( 13, 11 ), self::SERVICES_ID, 'none' ],
			'unrelated page'             => [ self::page( 14 ), self::SERVICES_ID, 'none' ],
			'child of another post type' => [ self::page( 15, self::SERVICES_ID, 'publish', 'post' ), self::SERVICES_ID, 'none' ],
			'no queried page'            => [ null, self::SERVICES_ID, 'none' ],
			'record without an ID'       => [ (object) [ 'post_type' => 'page' ], self::SERVICES_ID, 'none' ],
			'unresolved services page'   => [ self::page( 16 ), 0, 'none' ],
			'top level, no services'     => [ self::page( 17, 0 ), 0, 'none' ],
		];
	}

	/**
	 * Only the Services page and its direct children have a role.
	 *
	 * @dataProvider roles
	 *
	 * @param object|null $page        Page record.
	 * @param int         $services_id Resolved Services page ID.
	 * @param string      $expected    Expected role.
	 */
	public function test_page_role( ?object $page, int $services_id, string $expected ): void {
		self::assertSame( $expected, jmc_service_page_role( $page, $services_id ) );
	}

	/**
	 * The template for the role goes first, ahead of an assigned template.
	 */
	public function test_hierarchy_puts_role_template_first(): void {
		$hierarchy = [ 'custom-template', 'page-rewiring.php', 'page-11.php', 'page.php' ];

		self::assertSame(
			[ 'service', 'custom-template', 'page-rewiring.php', 'page-11.php', 'page.php' ],
			jmc_service_template_hierarchy( $hierarchy, 'service' )
		);
		self::assertSame(
			[ 'services-landing', 'page-services.php', 'page-10.php', 'page.php' ],
			jmc_service_template_hierarchy( [ 'page-services.php', 'page-10.php', 'page.php' ], 'landing' )
		);
	}

	/**
	 * A services template assigned to a page it does not govern is ignored.
	 */
	public function test_hierarchy_drops_services_templates_without_a_role(): void {
		self::assertSame(
			[ 'page-wiring-faq.php', 'page-13.php', 'page.php' ],
			jmc_service_template_hierarchy( [ 'service', 'page-wiring-faq.php', 'page-13.php', 'page.php' ], 'none' )
		);
		self::assertSame(
			[ 'page.php' ],
			jmc_service_template_hierarchy( [ 'services-landing.php', 'service.html', 'page.php' ], 'none' )
		);
	}

	/**
	 * The other role's template never applies, and nothing is duplicated.
	 */
	public function test_hierarchy_replaces_the_other_role_template(): void {
		self::assertSame(
			[ 'service', 'page.php' ],
			jmc_service_template_hierarchy( [ 'services-landing', 'service.php', 'page.php' ], 'service' )
		);
	}

	/**
	 * Each page is offered only the services template that governs it.
	 */
	public function test_selectable_templates(): void {
		$templates = [
			'services-landing' => 'Services landing page',
			'service'          => 'Service page',
			'wide'             => 'Wide page',
		];

		self::assertSame(
			[
				'services-landing' => 'Services landing page',
				'wide'             => 'Wide page',
			],
			jmc_service_selectable_templates( $templates, 'landing' )
		);
		self::assertSame(
			[
				'service' => 'Service page',
				'wide'    => 'Wide page',
			],
			jmc_service_selectable_templates( $templates, 'service' )
		);
		self::assertSame( [ 'wide' => 'Wide page' ], jmc_service_selectable_templates( $templates, 'none' ) );
	}

	/**
	 * Requests and their routed template, through the registered filter.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: object|null, 2: string}>
	 */
	public function routed_requests(): array {
		$services = self::page( self::SERVICES_ID, 0, 'publish', 'page', 'services' );

		return [
			'services page by slug'    => [ [], $services, 'services-landing' ],
			'service page'             => [ [], self::page( 11, self::SERVICES_ID ), 'service' ],
			'deeper descendant'        => [ [], self::page( 13, 11 ), 'page.php' ],
			'unrelated page'           => [ [], self::page( 14 ), 'page.php' ],
			'configured services page' => [
				[ JMC_SERVICES_PAGE_OPTION => 20 ],
				self::page( 21, 20 ),
				'service',
			],
			'invalid configured page'  => [
				[ JMC_SERVICES_PAGE_OPTION => 99 ],
				self::page( 11, self::SERVICES_ID ),
				'page.php',
			],
			'missing services page'    => [ [], null, 'page.php' ],
		];
	}

	/**
	 * The page template hierarchy filter routes by the resolved Services page.
	 *
	 * @dataProvider routed_requests
	 *
	 * @param array<string, mixed> $options  Site options.
	 * @param object|null          $queried  Queried page.
	 * @param string               $expected First template candidate.
	 */
	public function test_filter_routes_requests( array $options, ?object $queried, string $expected ): void {
		$pages = [ self::page( 20 ) ];

		// The "missing" case has no services page at all.
		if ( null !== $queried ) {
			$pages[] = self::page( self::SERVICES_ID, 0, 'publish', 'page', 'services' );
		}

		jmc_test_wp_fixture(
			[
				'options' => $options,
				'pages'   => $pages,
			]
		);

		$GLOBALS['jmc_test_queried_object'] = $queried;

		self::assertArrayHasKey( 'page_template_hierarchy', self::$filters );

		$hierarchy = ( self::$filters['page_template_hierarchy'] )( [ 'page.php' ] );

		self::assertSame( $expected, $hierarchy[0] );
		self::assertSame( 'page.php', end( $hierarchy ) );
	}

	/**
	 * The page templates filter hides services templates from other pages.
	 */
	public function test_filter_limits_selectable_templates(): void {
		jmc_test_wp_fixture(
			[
				'pages' => [ self::page( self::SERVICES_ID, 0, 'publish', 'page', 'services' ) ],
			]
		);

		self::assertArrayHasKey( 'theme_page_templates', self::$filters );

		$templates = [
			'services-landing' => 'Services landing page',
			'service'          => 'Service page',
		];
		$filter    = self::$filters['theme_page_templates'];

		self::assertSame( [ 'service' => 'Service page' ], $filter( $templates, null, self::page( 11, self::SERVICES_ID ) ) );
		self::assertSame( [], $filter( $templates, null, self::page( 13, 11 ) ) );
		self::assertSame( [], $filter( $templates, null, null ) );
	}

	/**
	 * Both services templates are registered for pages and exist.
	 */
	public function test_theme_json_registers_services_templates(): void {
		$theme_json = json_decode( $this->read_theme_file( 'theme.json' ), true, 512, JSON_THROW_ON_ERROR );
		$registered = array_column( $theme_json['customTemplates'] ?? [], null, 'name' );

		foreach ( jmc_service_templates() as $slug ) {
			self::assertArrayHasKey( $slug, $registered, "{$slug} is not a custom template." );
			self::assertNotEmpty( $registered[ $slug ]['title'] ?? '' );
			self::assertSame( [ 'page' ], $registered[ $slug ]['postTypes'] ?? null );
			self::assertFileExists( self::$theme_root . "/templates/{$slug}.html" );
		}
	}

	/**
	 * Services template slugs.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function template_slugs(): array {
		return [
			'landing' => [ 'services-landing' ],
			'service' => [ 'service' ],
		];
	}

	/**
	 * Each template wraps breadcrumbs and the page content in one main landmark.
	 *
	 * @dataProvider template_slugs
	 *
	 * @param string $slug Template slug.
	 */
	public function test_template_structure( string $slug ): void {
		$template = $this->read_theme_file( "templates/{$slug}.html" );

		self::assertMatchesRegularExpression(
			'/^<!--\s+wp:template-part\s+\{[^}]*"slug":"header"[^}]*\}\s+\/-->'
			. '\s*<!--\s+wp:group\s+\{"tagName":"main"[^}]*\}[^}]*\}\s+-->\s*<main\b[^>]*>'
			. '\s*<!--\s+wp:breadcrumbs\s+\{[^}]*"lock":\{"move":true,"remove":true\}\}\s+\/-->'
			. '\s*<!--\s+wp:post-content\s+\{"lock":\{"move":true,"remove":true\}\}\s+\/-->'
			. '\s*<\/main>\s*<!--\s+\/wp:group\s+-->'
			. '\s*<!--\s+wp:template-part\s+\{[^}]*"slug":"footer"[^}]*\}\s+\/-->\s*$/',
			$template
		);
		self::assertSame( 1, substr_count( $template, '<main' ), 'One main landmark.' );
	}

	/**
	 * Templates own layout only: no page fields, copy or links of their own.
	 *
	 * @dataProvider template_slugs
	 *
	 * @param string $slug Template slug.
	 */
	public function test_template_holds_no_editorial_content( string $slug ): void {
		$template = $this->read_theme_file( "templates/{$slug}.html" );

		self::assertDoesNotMatchRegularExpression( '/\bhref\s*=|"url"\s*:|<img\b|\bsrc\s*=/i', $template );
		self::assertDoesNotMatchRegularExpression( '/"text"\s*:/', $template, 'Copy belongs to the page.' );
		self::assertDoesNotMatchRegularExpression( '/wp:jmc\/service-(grid|list)|wp:(post-title|post-excerpt|post-featured-image|query)\b/', $template );
		self::assertStringNotContainsString( 'application/ld+json', $template, 'Structured data is left to the SEO plugin.' );
	}

	/**
	 * The starter patterns for both page types lock the governed sections, and
	 * only the landing page lists the services, through the shared grid.
	 */
	public function test_starter_patterns(): void {
		$landing = $this->read_theme_file( 'patterns/services-landing.php' );
		$service = $this->read_theme_file( 'patterns/service-page.php' );

		foreach ( [ $landing, $service ] as $pattern ) {
			self::assertMatchesRegularExpression( '/^\s*\*\s*Block Types:\s*core\/post-content\s*$/m', $pattern );
			self::assertMatchesRegularExpression( '/^\s*\*\s*Post Types:\s*page\s*$/m', $pattern );
			self::assertMatchesRegularExpression( '/<!--\s+wp:jmc\/hero\s+\{"lock":\{"move":true,"remove":true\}\}\s+-->/', $pattern );
			self::assertMatchesRegularExpression( '/<!--\s+wp:jmc\/cta-banner\s+\{[^}]*"lock":\{"move":true,"remove":true\}\}\s+-->/', $pattern );
		}

		self::assertMatchesRegularExpression(
			'/<!--\s+wp:jmc\/service-grid\s+\{"lock":\{"move":true,"remove":true\}\}\s+-->'
			. '.*<!--\s+wp:jmc\/service-list\s+\/-->.*<!--\s+\/wp:jmc\/service-grid\s+-->/s',
			$landing
		);
		self::assertSame( 1, substr_count( $landing, 'wp:jmc/service-list' ) );
		self::assertStringNotContainsString( 'wp:jmc/service-', $service );
	}
}
