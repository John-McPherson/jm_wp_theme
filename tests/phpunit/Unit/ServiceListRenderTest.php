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
 * Verify the service list renders cards from a parent's child pages or chosen pages.
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
	 * @param array<string, mixed> $context    Block context.
	 *
	 * @throws \Throwable If rendering fails.
	 *
	 * @return string Rendered markup.
	 */
	private function render( array $attributes = [], array $context = [] ): string {
		$block = (object) [ 'context' => $context ];

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
	 * The cue beside the arrow uses the block's text, falling back to "Learn more".
	 *
	 * @dataProvider more_texts
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $expected   Expected cue markup.
	 */
	public function test_more_text( array $attributes, string $expected ): void {
		self::site_with_services( [ self::service( 21, 'Rewiring' ) ] );

		self::assertStringContainsString(
			'<span class="jmc-service-card__more-text">' . $expected . '</span>',
			$this->render( $attributes )
		);
	}

	/**
	 * Cue text cases.
	 *
	 * @return array<string, array{0:array<string, mixed>,1:string}>
	 */
	public static function more_texts(): array {
		return [
			'omitted'   => [ [], 'Learn more' ],
			'empty'     => [ [ 'moreText' => '' ], 'Learn more' ],
			'blank'     => [ [ 'moreText' => '   ' ], 'Learn more' ],
			'custom'    => [ [ 'moreText' => ' View services ' ], 'View services' ],
			'escaped'   => [ [ 'moreText' => '<b>See</b> & go' ], '&lt;b&gt;See&lt;/b&gt; &amp; go' ],
			'malformed' => [ [ 'moreText' => [ 'View' ] ], 'Learn more' ],
		];
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
	 * Each card shows a decorative "Learn more" cue, with an arrow when the
	 * arrow icon is registered and without one when it is not.
	 */
	public function test_renders_decorative_arrow(): void {
		self::site_with_services(
			[ self::service( 21, 'Rewiring' ), self::service( 22, 'EV Chargers' ) ],
			[ 'icons' => [ 'jmc/arrow-right' ] ]
		);

		$html = $this->render();

		self::assertSame( 2, substr_count( $html, '<div class="jmc-service-card__more" aria-hidden="true">' ) );
		self::assertSame( 2, substr_count( $html, '<span class="jmc-service-card__more-text">Learn more</span>' ) );
		self::assertSame( 2, substr_count( $html, '<span class="jmc-service-card__arrow">' ) );
		self::assertSame( 2, substr_count( $html, 'data-icon="jmc/arrow-right"' ) );

		self::site_with_services( [ self::service( 21, 'Rewiring' ) ] );

		$html = $this->render();

		self::assertStringContainsString( 'Learn more', $html );
		self::assertStringNotContainsString( 'jmc-service-card__arrow', $html );
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
				'No services to list.',
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
	 * Build a hierarchy: Services (10) → Domestic (30) → Rewires (31), and
	 * Services → Commercial (40) → Fit-outs (41), plus loose pages.
	 *
	 * @param array<int, object> $extra Extra pages.
	 *
	 * @return void
	 */
	private static function site_with_categories( array $extra = [] ): void {
		jmc_test_wp_fixture(
			[
				'pages' => array_merge(
					[
						self::page( 10, [ 'post_name' => 'services' ] ),
						self::page(
							30,
							[
								'post_title'  => 'Domestic Services',
								'post_parent' => 10,
								'permalink'   => '/services/domestic/',
							]
						),
						self::page(
							31,
							[
								'post_title'  => 'Rewires',
								'post_parent' => 30,
								'permalink'   => '/services/domestic/rewires/',
							]
						),
						self::page(
							40,
							[
								'post_title'  => 'Commercial Services',
								'post_parent' => 10,
								'permalink'   => '/services/commercial/',
							]
						),
						self::page(
							41,
							[
								'post_title'  => 'Fit-outs',
								'post_parent' => 40,
								'permalink'   => '/services/commercial/fit-outs/',
							]
						),
						self::page( 50, [ 'post_title' => 'About' ] ),
					],
					$extra
				),
			]
		);
	}

	/**
	 * Page context for a page ID.
	 *
	 * @param int $id Page ID.
	 *
	 * @return array{postId:int,postType:string} Block context.
	 */
	private static function on_page( int $id ): array {
		return [
			'postId'   => $id,
			'postType' => 'page',
		];
	}

	/**
	 * A list lists the children of the page it is rendered on.
	 *
	 * @dataProvider category_pages
	 *
	 * @param int    $page_id  Current page.
	 * @param string $expected Listed title.
	 * @param string $absent   Title that must not be listed.
	 */
	public function test_lists_children_of_current_page( int $page_id, string $expected, string $absent ): void {
		self::site_with_categories();

		$html = $this->render( [], self::on_page( $page_id ) );

		self::assertStringContainsString( $expected, $html );
		self::assertStringNotContainsString( $absent, $html );
		self::assertSame( $page_id, jmc_test_wp( 'last_query' )['post_parent'] ?? null );
	}

	/**
	 * Category, landing and service pages.
	 *
	 * @return array<string, array{0:int,1:string,2:string}>
	 */
	public static function category_pages(): array {
		return [
			'domestic'   => [ 30, 'Rewires', 'Fit-outs' ],
			'commercial' => [ 40, 'Fit-outs', 'Rewires' ],
			'services'   => [ 10, 'Domestic Services', 'Rewires' ],
		];
	}

	/**
	 * Without page context the list falls back to the Services page.
	 *
	 * @dataProvider contexts_without_a_page
	 *
	 * @param array<string, mixed> $context Block context.
	 */
	public function test_falls_back_to_services_page_without_page_context( array $context ): void {
		self::site_with_categories();

		$html = $this->render( [], $context );

		self::assertStringContainsString( 'Domestic Services', $html );
		self::assertSame( 10, jmc_test_wp( 'last_query' )['post_parent'] ?? null );
	}

	/**
	 * Contexts that do not identify a page.
	 *
	 * @return array<string, array{0:array<string, mixed>}>
	 */
	public static function contexts_without_a_page(): array {
		return [
			'none'           => [ [] ],
			'post type post' => [
				[
					'postId'   => 30,
					'postType' => 'post',
				],
			],
			'string id'      => [
				[
					'postId'   => '30',
					'postType' => 'page',
				],
			],
			'missing page'   => [ self::on_page( 999 ) ],
		];
	}

	/**
	 * A chosen parent page wins over the current page.
	 */
	public function test_parent_setting_wins_over_current_page(): void {
		self::site_with_categories();

		$html = $this->render( [ 'parentId' => 40 ], self::on_page( 30 ) );

		self::assertStringContainsString( 'Fit-outs', $html );
		self::assertStringNotContainsString( 'Rewires', $html );
	}

	/**
	 * An invalid chosen parent lists nothing rather than falling back.
	 *
	 * @dataProvider invalid_parents
	 *
	 * @param mixed $parent_id Stored parent ID.
	 */
	public function test_invalid_parent_does_not_fall_back( mixed $parent_id ): void {
		self::site_with_categories(
			[ self::page( 60, [ 'post_status' => 'draft' ] ) ]
		);

		self::assertSame( '', trim( $this->render( [ 'parentId' => $parent_id ], self::on_page( 30 ) ) ) );

		jmc_test_wp_fixture(
			array_merge(
				$GLOBALS['jmc_test_wp'],
				[
					'rest'     => true,
					'can_edit' => true,
				]
			)
		);

		self::assertStringContainsString(
			'The parent page chosen for this list is missing or unpublished.',
			$this->render( [ 'parentId' => $parent_id ], self::on_page( 30 ) )
		);
	}

	/**
	 * Parent IDs that do not resolve to a published page.
	 *
	 * @return array<string, array{0:mixed}>
	 */
	public static function invalid_parents(): array {
		return [
			'missing' => [ 999 ],
			'draft'   => [ 60 ],
		];
	}

	/**
	 * Malformed parent IDs are ignored in favour of the current page.
	 *
	 * @dataProvider ignored_parents
	 *
	 * @param mixed $parent_id Stored parent ID.
	 */
	public function test_ignored_parent_uses_current_page( mixed $parent_id ): void {
		self::site_with_categories();

		self::assertStringContainsString(
			'Rewires',
			$this->render( [ 'parentId' => $parent_id ], self::on_page( 30 ) )
		);
	}

	/**
	 * Parent values treated as unset.
	 *
	 * @return array<string, array{0:mixed}>
	 */
	public static function ignored_parents(): array {
		return [
			'zero'     => [ 0 ],
			'negative' => [ -5 ],
			'array'    => [ [ 40 ] ],
			'text'     => [ 'commercial' ],
		];
	}

	/**
	 * A page without children lists the chosen pages in the chosen order.
	 */
	public function test_page_without_children_lists_chosen_pages_in_order(): void {
		self::site_with_categories(
			[
				self::page(
					70,
					[
						'post_title'  => 'Draft service',
						'post_status' => 'draft',
						'permalink'   => '/draft/',
					]
				),
				self::page(
					71,
					[
						'post_title'    => 'Protected service',
						'post_password' => 'secret',
						'permalink'     => '/protected/',
					]
				),
			]
		);

		$html = $this->render(
			[ 'pageIds' => [ 41, 70, 999, 71, 31, 41 ] ],
			self::on_page( 50 )
		);

		self::assertSame( 2, substr_count( $html, '<li' ) );
		self::assertLessThan( strpos( $html, 'Rewires' ), strpos( $html, 'Fit-outs' ) );
		self::assertStringNotContainsString( 'Draft service', $html );
		self::assertStringNotContainsString( 'Protected service', $html );
		self::assertSame( 'post__in', jmc_test_wp( 'last_query' )['orderby'] ?? null );
	}

	/**
	 * Chosen pages render in the editor preview, where the REST block renderer
	 * casts `number` attributes to floats.
	 */
	public function test_chosen_pages_render_from_rest_float_ids(): void {
		self::site_with_categories();

		$html = $this->render(
			[
				'source'  => 'chosen',
				'pageIds' => [ 41.0, 31.0 ],
			],
			self::on_page( 50 )
		);

		self::assertSame( 2, substr_count( $html, '<li' ) );
		self::assertLessThan( strpos( $html, 'Rewires' ), strpos( $html, 'Fit-outs' ) );
	}

	/**
	 * Choosing pages replaces the children of a page that has them.
	 */
	public function test_chosen_source_lists_chosen_pages_over_children(): void {
		self::site_with_categories();

		$html = $this->render(
			[
				'source'  => 'chosen',
				'pageIds' => [ 41 ],
			],
			self::on_page( 30 )
		);

		self::assertStringContainsString( 'Fit-outs', $html );
		self::assertStringNotContainsString( 'Rewires', $html );
	}

	/**
	 * Children win while the source is children, keeping chosen pages for later.
	 *
	 * @dataProvider children_sources
	 *
	 * @param mixed $source Stored source.
	 */
	public function test_children_source_ignores_chosen_pages( mixed $source ): void {
		self::site_with_categories();

		$html = $this->render(
			[
				'source'  => $source,
				'pageIds' => [ 41 ],
			],
			self::on_page( 30 )
		);

		self::assertStringContainsString( 'Rewires', $html );
		self::assertStringNotContainsString( 'Fit-outs', $html );
	}

	/**
	 * Sources that resolve to children.
	 *
	 * @return array<string, array{0:mixed}>
	 */
	public static function children_sources(): array {
		return [
			'children'  => [ 'children' ],
			'omitted'   => [ null ],
			'malformed' => [ 'everything' ],
		];
	}

	/**
	 * Malformed or empty choices list nothing on a page without children.
	 *
	 * @dataProvider empty_choices
	 *
	 * @param mixed $page_ids Stored page IDs.
	 */
	public function test_empty_choices_render_nothing( mixed $page_ids ): void {
		self::site_with_categories();

		self::assertSame( '', trim( $this->render( [ 'pageIds' => $page_ids ], self::on_page( 50 ) ) ) );
	}

	/**
	 * Choices that yield no pages.
	 *
	 * @return array<string, array{0:mixed}>
	 */
	public static function empty_choices(): array {
		return [
			'omitted' => [ null ],
			'empty'   => [ [] ],
			'string'  => [ '41' ],
			'invalid' => [ [ 0, -1, 'x', 4.5 ] ],
			'missing' => [ [ 999 ] ],
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
