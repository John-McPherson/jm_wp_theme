<?php
/**
 * Services helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/services.php';

/**
 * Verify resolution of the canonical Services parent page.
 */
final class ServicesHelperTest extends TestCase {

	/**
	 * Build a page-like record.
	 *
	 * @param int    $id     Page ID.
	 * @param string $status Post status.
	 * @param string $type   Post type.
	 *
	 * @return object Page record.
	 */
	private static function page(
		int $id,
		string $status = 'publish',
		string $type = 'page'
	): object {
		return (object) [
			'ID'          => $id,
			'post_status' => $status,
			'post_type'   => $type,
		];
	}

	/**
	 * A valid configured page takes precedence over the slug fallback.
	 */
	public function test_valid_configured_page_wins(): void {
		self::assertSame(
			[
				'id'     => 12,
				'status' => 'configured',
			],
			jmc_resolve_services_page( 12, self::page( 12 ), self::page( 40 ) )
		);
	}

	/**
	 * Without configuration the published page at the canonical slug is used.
	 */
	public function test_resolves_canonical_slug_when_unconfigured(): void {
		self::assertSame(
			[
				'id'     => 40,
				'status' => 'resolved',
			],
			jmc_resolve_services_page( 0, null, self::page( 40 ) )
		);
	}

	/**
	 * Invalid configured pages are reported instead of falling back.
	 *
	 * @dataProvider invalid_configured_pages
	 *
	 * @param object|null $configured_page Configured page record.
	 */
	public function test_invalid_configuration_does_not_fall_back( ?object $configured_page ): void {
		self::assertSame(
			[
				'id'     => 0,
				'status' => 'invalid',
			],
			jmc_resolve_services_page( 12, $configured_page, self::page( 40 ) )
		);
	}

	/**
	 * Configured page records that must not act as the Services parent.
	 *
	 * @return array<string, array{0:object|null}>
	 */
	public function invalid_configured_pages(): array {
		return [
			'deleted'     => [ null ],
			'draft'       => [ self::page( 12, 'draft' ) ],
			'private'     => [ self::page( 12, 'private' ) ],
			'trashed'     => [ self::page( 12, 'trash' ) ],
			'post'        => [ self::page( 12, 'publish', 'post' ) ],
			'mismatch id' => [ self::page( 99 ) ],
		];
	}

	/**
	 * Unpublished or missing slug pages leave the parent unresolved.
	 *
	 * @dataProvider missing_fallback_pages
	 *
	 * @param object|null $fallback_page Page found at the canonical slug.
	 */
	public function test_missing_parent( ?object $fallback_page ): void {
		self::assertSame(
			[
				'id'     => 0,
				'status' => 'missing',
			],
			jmc_resolve_services_page( 0, null, $fallback_page )
		);
	}

	/**
	 * Slug lookups that must not resolve a Services parent.
	 *
	 * @return array<string, array{0:object|null}>
	 */
	public function missing_fallback_pages(): array {
		return [
			'no page'      => [ null ],
			'draft page'   => [ self::page( 40, 'draft' ) ],
			'malformed id' => [
				(object) [
					'ID'          => '40',
					'post_status' => 'publish',
					'post_type'   => 'page',
				],
			],
			'empty record' => [ (object) [] ],
		];
	}

	/**
	 * The listing queries direct published, unprotected child pages in
	 * menu order then title order, with no item limit.
	 */
	public function test_query_args_enforce_retrieval_contract(): void {
		self::assertSame(
			[
				'post_type'           => 'page',
				'post_status'         => 'publish',
				'post_parent'         => 12,
				'has_password'        => false,
				'orderby'             => [
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				],
				'posts_per_page'      => -1,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			],
			jmc_services_query_args( 12 )
		);
	}

	/**
	 * An unresolved parent never queries (and so never lists top-level pages).
	 *
	 * @dataProvider unresolved_parent_ids
	 *
	 * @param int $parent_id Unresolved parent ID.
	 */
	public function test_query_args_empty_without_parent( int $parent_id ): void {
		self::assertSame( [], jmc_services_query_args( $parent_id ) );
		self::assertSame( [], jmc_get_services( $parent_id ) );
	}

	/**
	 * Parent IDs that must not produce a query.
	 *
	 * @return array<string, array{0:int}>
	 */
	public function unresolved_parent_ids(): array {
		return [
			'zero'     => [ 0 ],
			'negative' => [ -5 ],
		];
	}

	/**
	 * Icon names follow the core "collection/icon-name" format.
	 *
	 * @dataProvider icon_names
	 *
	 * @param mixed $value    Candidate icon name.
	 * @param bool  $expected Whether the name is well formed.
	 */
	public function test_icon_name_format( mixed $value, bool $expected ): void {
		self::assertSame( $expected, jmc_is_icon_name( $value ) );
	}

	/**
	 * Candidate icon names.
	 *
	 * @return array<string, array{0:mixed,1:bool}>
	 */
	public function icon_names(): array {
		return [
			'core icon'         => [ 'core/plug', true ],
			'custom collection' => [ 'jmc/fuse_box-2', true ],
			'empty'             => [ '', false ],
			'not namespaced'    => [ 'plug', false ],
			'uppercase'         => [ 'core/Plug', false ],
			'nested'            => [ 'core/plug/extra', false ],
			'trailing hyphen'   => [ 'core/plug-', false ],
			'markup'            => [ 'core/<svg>', false ],
			'non-string'        => [ 42, false ],
			'null'              => [ null, false ],
		];
	}

	/**
	 * Complete records map to card data from native page fields.
	 */
	public function test_card_data_from_page_fields(): void {
		self::assertSame(
			[
				'id'      => 21,
				'title'   => 'Rewiring',
				'url'     => 'https://example.test/services/rewiring/',
				'summary' => 'Full and partial rewires.',
				'icon'    => 'core/plug',
			],
			jmc_service_card_data(
				(object) [
					'ID'           => 21,
					'post_title'   => ' Rewiring ',
					'post_excerpt' => " Full and partial rewires.\n",
				],
				'https://example.test/services/rewiring/',
				'core/plug'
			)
		);
	}

	/**
	 * Legacy records missing an excerpt or a valid icon still produce a card.
	 */
	public function test_card_data_tolerates_missing_excerpt_and_icon(): void {
		$card = jmc_service_card_data(
			(object) [
				'ID'         => 22,
				'post_title' => 'EV Chargers',
			],
			'https://example.test/services/ev-chargers/',
			'not-an-icon'
		);

		self::assertSame( '', $card['summary'] ?? null );
		self::assertSame( '', $card['icon'] ?? null );
	}

	/**
	 * Records that cannot produce a meaningful link produce no card.
	 *
	 * @dataProvider unusable_records
	 *
	 * @param object $page Page record.
	 * @param string $url  Permalink.
	 */
	public function test_card_data_rejects_unusable_records( object $page, string $url ): void {
		self::assertNull( jmc_service_card_data( $page, $url, '' ) );
	}

	/**
	 * Unusable service records.
	 *
	 * @return array<string, array{0:object,1:string}>
	 */
	public function unusable_records(): array {
		$url = 'https://example.test/services/x/';

		return [
			'blank title'   => [
				(object) [
					'ID'         => 1,
					'post_title' => '  ',
				],
				$url,
			],
			'missing title' => [ (object) [ 'ID' => 1 ], $url ],
			'missing url'   => [
				(object) [
					'ID'         => 1,
					'post_title' => 'X',
				],
				'',
			],
			'missing id'    => [ (object) [ 'post_title' => 'X' ], $url ],
			'string id'     => [
				(object) [
					'ID'         => '1',
					'post_title' => 'X',
				],
				$url,
			],
		];
	}
}
