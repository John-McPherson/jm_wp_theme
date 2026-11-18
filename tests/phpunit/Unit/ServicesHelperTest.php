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

	/**
	 * Chosen page IDs keep positive integers once each, in the order given.
	 *
	 * @dataProvider chosen_page_ids
	 *
	 * @param mixed           $value    Stored page IDs.
	 * @param array<int, int> $expected Normalised IDs.
	 */
	public function test_chosen_page_ids( mixed $value, array $expected ): void {
		self::assertSame( $expected, jmc_service_page_ids( $value ) );
	}

	/**
	 * Chosen page ID cases.
	 *
	 * @return array<string, array{0:mixed,1:array<int, int>}>
	 */
	public static function chosen_page_ids(): array {
		return [
			'omitted'         => [ null, [] ],
			'not a list'      => [ '12,13', [] ],
			'ordered'         => [ [ 13, 12, 14 ], [ 13, 12, 14 ] ],
			'duplicates'      => [ [ 12, 13, 12 ], [ 12, 13 ] ],
			'numeric strings' => [ [ '12', 13 ], [ 12, 13 ] ],
			'whole floats'    => [ [ 12.0, 13.0 ], [ 12, 13 ] ],
			'invalid dropped' => [ [ 0, -1, 1.5, '1e2', 'x', null, [ 12 ], 14 ], [ 14 ] ],
		];
	}

	/**
	 * Chosen pages query published, unprotected pages in the chosen order.
	 */
	public function test_chosen_query_args_enforce_retrieval_contract(): void {
		self::assertSame(
			[
				'post_type'           => 'page',
				'post_status'         => 'publish',
				'post__in'            => [ 13, 12 ],
				'has_password'        => false,
				'orderby'             => 'post__in',
				'posts_per_page'      => -1,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			],
			jmc_chosen_services_query_args( [ 13, 12 ] )
		);
	}

	/**
	 * Nothing chosen never queries, so it never lists every page.
	 */
	public function test_chosen_query_args_empty_without_pages(): void {
		self::assertSame( [], jmc_chosen_services_query_args( [] ) );
		self::assertSame( [], jmc_get_chosen_services( [] ) );
	}

	/**
	 * A list's parent is the chosen parent, else the current page, else the
	 * Services page; an invalid chosen parent does not fall back.
	 *
	 * @dataProvider service_list_parents
	 *
	 * @param int                         $parent_id    Chosen parent ID.
	 * @param object|null                 $parent_page  Chosen parent record.
	 * @param object|null                 $current_page Current page record.
	 * @param array{id:int,status:string} $expected Expected resolution.
	 */
	public function test_service_list_parent(
		int $parent_id,
		?object $parent_page,
		?object $current_page,
		array $expected
	): void {
		self::assertSame(
			$expected,
			jmc_resolve_service_list_parent(
				parent_id: $parent_id,
				parent_page: $parent_page,
				current_page: $current_page,
				services_page: [
					'id'     => 10,
					'status' => 'resolved',
				]
			)
		);
	}

	/**
	 * Service list parent cases.
	 *
	 * @return array<string, array{0:int,1:object|null,2:object|null,3:array{id:int,status:string}}>
	 */
	public static function service_list_parents(): array {
		$services = [
			'id'     => 10,
			'status' => 'resolved',
		];
		$invalid  = [
			'id'     => 0,
			'status' => 'invalid-parent',
		];

		return [
			'chosen parent wins'        => [
				40,
				self::page( 40 ),
				self::page( 30 ),
				[
					'id'     => 40,
					'status' => 'parent',
				],
			],
			'chosen parent missing'     => [ 40, null, self::page( 30 ), $invalid ],
			'chosen parent draft'       => [ 40, self::page( 40, 'draft' ), self::page( 30 ), $invalid ],
			'chosen parent not a page'  => [ 40, self::page( 40, 'publish', 'post' ), null, $invalid ],
			'chosen parent ID mismatch' => [ 40, self::page( 41 ), null, $invalid ],
			'current page'              => [
				0,
				null,
				self::page( 30 ),
				[
					'id'     => 30,
					'status' => 'current',
				],
			],
			'current draft page'        => [
				0,
				null,
				self::page( 30, 'draft' ),
				[
					'id'     => 30,
					'status' => 'current',
				],
			],
			'current post falls back'   => [ 0, null, self::page( 30, 'publish', 'post' ), $services ],
			'no context falls back'     => [ 0, null, null, $services ],
		];
	}
}
