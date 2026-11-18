<?php
/**
 * Service list render tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/services.php';

/**
 * Verify the service list renders cards from the Services child pages.
 */
final class ServiceListRenderTest extends TestCase {

	/**
	 * Start every test with an empty site.
	 */
	protected function setUp(): void {
		jmc_test_wp_fixture();
	}

	/**
	 * Build a page record.
	 *
	 * @param int                  $id     Page ID.
	 * @param array<string, mixed> $fields Additional fields.
	 *
	 * @return object Page record.
	 */
	private static function page( int $id, array $fields = [] ): object {
		return (object) array_merge(
			[
				'ID'          => $id,
				'post_type'   => 'page',
				'post_status' => 'publish',
			],
			$fields
		);
	}

	/**
	 * Build a service page record.
	 *
	 * @param int    $id      Page ID.
	 * @param string $title   Title.
	 * @param string $excerpt Excerpt.
	 *
	 * @return object Service page record.
	 */
	private static function service( int $id, string $title, string $excerpt = '' ): object {
		return self::page(
			$id,
			[
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
				'post_parent'  => 10,
				'permalink'    => 'https://example.test/services/' . strtolower( str_replace( ' ', '-', $title ) ) . '/',
			]
		);
	}

	/**
	 * Configure a resolvable Services page with the given query results.
	 *
	 * @param array<int, object>   $services  Services returned by the query.
	 * @param array<string, mixed> $overrides Extra fixture values.
	 *
	 * @return void
	 */
	private static function site_with_services( array $services, array $overrides = [] ): void {
		jmc_test_wp_fixture(
			array_merge(
				[
					'pages'         => [ self::page( 10, [ 'post_name' => 'services' ] ) ],
					'query_results' => $services,
				],
				$overrides
			)
		);
	}

	/**
	 * Execute the real render callback, converting warnings into failures.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 *
	 * @throws \Throwable If rendering fails.
	 *
	 * @return string Rendered markup.
	 */
	private function render( array $attributes = [] ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/content/service-list/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Many services render once each, in the order the query returns them.
	 */
	public function test_renders_each_service_in_query_order(): void {
		self::site_with_services(
			[
				self::service( 21, 'Rewiring', 'Full and partial rewires.' ),
				self::service( 22, 'EV Chargers', 'Home charge points.' ),
				self::service( 23, 'Emergency Callouts', 'Around the clock.' ),
			]
		);

		$html = $this->render();

		self::assertSame( 1, substr_count( $html, '<ul' ) );
		self::assertSame( 3, substr_count( $html, '<li' ) );
		self::assertSame( 1, substr_count( $html, 'Rewiring' ) );
		self::assertLessThan( strpos( $html, 'EV Chargers' ), strpos( $html, 'Rewiring' ) );
		self::assertLessThan( strpos( $html, 'Emergency Callouts' ), strpos( $html, 'EV Chargers' ) );
		self::assertStringContainsString(
			'<a class="jmc-service-card__link" href="https://example.test/services/rewiring/">Rewiring</a>',
			$html
		);
		self::assertStringContainsString( 'Full and partial rewires.', $html );
	}

	/**
	 * A single service renders a one-item list.
	 */
	public function test_renders_single_service(): void {
		self::site_with_services( [ self::service( 21, 'Rewiring', 'Rewires.' ) ] );

		self::assertSame( 1, substr_count( $this->render(), '<li' ) );
	}

	/**
	 * The query is scoped to the resolved Services page.
	 */
	public function test_queries_children_of_resolved_services_page(): void {
		self::site_with_services( [] );

		$this->render();

		self::assertSame(
			jmc_services_query_args( 10 ),
			jmc_test_wp( 'last_query' )
		);
	}

	/**
	 * A configured Services page takes precedence over the slug.
	 */
	public function test_queries_children_of_configured_services_page(): void {
		self::site_with_services(
			[],
			[
				'options' => [ JMC_SERVICES_PAGE_OPTION => 55 ],
				'pages'   => [
					self::page( 10, [ 'post_name' => 'services' ] ),
					self::page( 55 ),
				],
			]
		);

		$this->render();

		self::assertSame( 55, jmc_test_wp( 'last_query' )['post_parent'] ?? null );
	}

	/**
	 * Card titles use the configured heading level, falling back to h3.
	 *
	 * @dataProvider heading_levels
	 *
	 * @param array<string, mixed> $attributes   Block attributes.
	 * @param string               $expected_tag Expected heading tag.
	 */
	public function test_heading_level( array $attributes, string $expected_tag ): void {
		self::site_with_services( [ self::service( 21, 'Rewiring' ) ] );

		$html = $this->render( $attributes );

		self::assertStringContainsString( "<{$expected_tag} class=\"jmc-service-card__title\">", $html );
		self::assertStringContainsString( "</{$expected_tag}>", $html );
	}

	/**
	 * Heading level cases.
	 *
	 * @return array<string, array{0:array<string, mixed>,1:string}>
	 */
	public static function heading_levels(): array {
		return [
			'omitted'     => [ [], 'h3' ],
			'supported'   => [ [ 'headingLevel' => '2' ], 'h2' ],
			'deepest'     => [ [ 'headingLevel' => '6' ], 'h6' ],
			'h1 rejected' => [ [ 'headingLevel' => '1' ], 'h3' ],
			'malformed'   => [ [ 'headingLevel' => [ '4' ] ], 'h3' ],
			'integer'     => [ [ 'headingLevel' => 4 ], 'h3' ],
		];
	}

	/**
	 * Registered icons render through the core Icon block as decoration.
	 */
	public function test_renders_registered_icon_as_decoration(): void {
		self::site_with_services(
			[ self::service( 21, 'Rewiring' ) ],
			[
				'icons' => [ 'core/plug' ],
				'meta'  => [ 21 => [ JMC_SERVICE_ICON_META => 'core/plug' ] ],
			]
		);

		$html = $this->render();

		self::assertStringContainsString( '<div class="jmc-service-card__icon" aria-hidden="true">', $html );
		self::assertStringContainsString( 'data-icon="core/plug"', $html );
	}

	/**
	 * Each card shows a decorative arrow when the arrow icon is registered,
	 * and omits it cleanly when it is not.
	 */
	public function test_renders_decorative_arrow(): void {
		self::site_with_services(
			[ self::service( 21, 'Rewiring' ), self::service( 22, 'EV Chargers' ) ],
			[ 'icons' => [ 'jmc/arrow-right' ] ]
		);

		$html = $this->render();

		self::assertSame( 2, substr_count( $html, '<div class="jmc-service-card__arrow" aria-hidden="true">' ) );
		self::assertSame( 2, substr_count( $html, 'data-icon="jmc/arrow-right"' ) );

		self::site_with_services( [ self::service( 21, 'Rewiring' ) ] );

		self::assertStringNotContainsString( 'jmc-service-card__arrow', $this->render() );
	}

	/**
	 * Legacy records without an excerpt or a usable icon still render a card.
	 *
	 * @dataProvider incomplete_icons
	 *
	 * @param mixed $icon Stored icon meta.
	 */
	public function test_legacy_record_without_excerpt_or_icon( mixed $icon ): void {
		self::site_with_services(
			[ self::service( 21, 'Rewiring' ) ],
			[ 'meta' => [ 21 => [ JMC_SERVICE_ICON_META => $icon ] ] ]
		);

		$html = $this->render();

		self::assertStringContainsString( 'jmc-service-card__link', $html );
		self::assertStringNotContainsString( 'jmc-service-card__icon', $html );
		self::assertStringNotContainsString( 'jmc-service-card__summary', $html );
	}

	/**
	 * Icon meta values that must not render an icon.
	 *
	 * @return array<string, array{0:mixed}>
	 */
	public static function incomplete_icons(): array {
		return [
			'missing'      => [ '' ],
			'unregistered' => [ 'core/unknown' ],
			'malformed'    => [ '<svg onload=alert(1)>' ],
			'non-string'   => [ [ 'core/plug' ] ],
		];
	}

	/**
	 * Records without a title or URL are skipped rather than rendering an empty link.
	 */
	public function test_skips_records_without_title_or_url(): void {
		$no_url = self::service( 22, 'No URL' );
		unset( $no_url->permalink );

		self::site_with_services(
			[
				self::service( 21, 'Rewiring' ),
				self::service( 23, '   ' ),
				$no_url,
			]
		);

		self::assertSame( 1, substr_count( $this->render(), '<li' ) );
	}

	/**
	 * Page titles and excerpts are escaped.
	 */
	public function test_escapes_page_content(): void {
		$service            = self::service( 21, '<script>alert(1)</script>', 'Fast & <b>safe</b>' );
		$service->permalink = 'https://example.test/services/safe/';

		self::site_with_services( [ $service ] );

		$html = $this->render();

		self::assertStringNotContainsString( '<script>', $html );
		self::assertStringNotContainsString( '<b>', $html );
		self::assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
		self::assertStringContainsString( 'Fast &amp; &lt;b&gt;safe&lt;/b&gt;', $html );
	}

	/**
	 * Visitors see nothing when there is nothing to list.
	 *
	 * @dataProvider empty_sites
	 *
	 * @param array<string, mixed> $fixture Site fixture.
	 */
	public function test_empty_states_render_nothing_publicly( array $fixture ): void {
		jmc_test_wp_fixture( $fixture );

		self::assertSame( '', trim( $this->render() ) );
	}

	/**
	 * Editors previewing the block see why it is empty.
	 *
	 * @dataProvider empty_sites
	 *
	 * @param array<string, mixed> $fixture  Site fixture.
	 * @param string               $expected Expected notice fragment.
	 */
	public function test_empty_states_explain_in_editor_preview( array $fixture, string $expected ): void {
		jmc_test_wp_fixture(
			array_merge(
				$fixture,
				[
					'rest'     => true,
					'can_edit' => true,
				]
			)
		);

		$html = $this->render();

		self::assertStringContainsString( 'jmc-service-list__notice', $html );
		self::assertStringContainsString( $expected, $html );
		self::assertStringNotContainsString( '<ul', $html );
	}

	/**
	 * Empty and misconfigured sites.
	 *
	 * @return array<string, array{0:array<string, mixed>,1:string}>
	 */
	public static function empty_sites(): array {
		$services_page = (object) [
			'ID'          => 10,
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'services',
		];

		return [
			'no services'        => [
				[ 'pages' => [ $services_page ] ],
				'No services are published yet.',
			],
			'no services page'   => [
				[],
				'No Services page found.',
			],
			'invalid configured' => [
				[
					'options' => [ JMC_SERVICES_PAGE_OPTION => 99 ],
					'pages'   => [ $services_page ],
				],
				'is missing or unpublished',
			],
			'draft configured'   => [
				[
					'options' => [ JMC_SERVICES_PAGE_OPTION => 10 ],
					'pages'   => [
						(object) [
							'ID'          => 10,
							'post_type'   => 'page',
							'post_status' => 'draft',
						],
					],
				],
				'is missing or unpublished',
			],
			'unresolved parent never queries top-level pages' => [
				[
					'query_results' => [
						(object) [
							'ID'         => 1,
							'post_title' => 'Home',
							'permalink'  => '/',
						],
					],
				],
				'No Services page found.',
			],
		];
	}

	/**
	 * Logged-out REST requests never see editor notices.
	 */
	public function test_rest_request_without_capability_renders_nothing(): void {
		jmc_test_wp_fixture( [ 'rest' => true ] );

		self::assertSame( '', trim( $this->render() ) );
	}
}
