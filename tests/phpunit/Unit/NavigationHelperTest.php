<?php
/**
 * Navigation helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/navigation.php';
require_once dirname( __DIR__ ) . '/doubles/navigation.php';

/**
 * Verify the supported Navigation-block data contract.
 */
final class NavigationHelperTest extends TestCase {

	/**
	 * Reset recorded actions.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_fired_actions'] = [];
	}

	/**
	 * Build a parsed Navigation block.
	 *
	 * @param string                           $block_name   Block name.
	 * @param array<string, mixed>             $attrs        Block attributes.
	 * @param array<int, array<string, mixed>> $inner_blocks Inner blocks.
	 *
	 * @return array<string, mixed>
	 */
	private static function block( string $block_name, array $attrs, array $inner_blocks = [] ): array {
		return [
			'blockName'   => $block_name,
			'attrs'       => $attrs,
			'innerBlocks' => $inner_blocks,
		];
	}

	/**
	 * Primary links and one submenu level keep their structure and order.
	 */
	public function test_tree_nests_one_submenu_level(): void {
		$tree = jmc_get_navigation_tree(
			[
				self::block(
					'core/navigation-link',
					[
						'id'    => 12,
						'label' => 'Home',
						'url'   => '/',
					]
				),
				self::block(
					'core/navigation-submenu',
					[
						'id'    => 24,
						'label' => ' Services ',
						'url'   => '/services/',
					],
					[
						self::block(
							'core/navigation-link',
							[
								'id'    => 25,
								'label' => 'Domestic',
								'url'   => '/services/domestic/',
							]
						),
						self::block(
							'core/navigation-link',
							[
								'label' => 'Commercial',
								'url'   => '/services/commercial/',
							]
						),
					]
				),
			]
		);

		self::assertSame(
			[
				[
					'id'       => 12,
					'title'    => 'Home',
					'url'      => '/',
					'children' => [],
				],
				[
					'id'       => 24,
					'title'    => 'Services',
					'url'      => '/services/',
					'children' => [
						[
							'id'    => 25,
							'title' => 'Domestic',
							'url'   => '/services/domestic/',
						],
						[
							'id'    => 0,
							'title' => 'Commercial',
							'url'   => '/services/commercial/',
						],
					],
				],
			],
			$tree
		);
		self::assertSame( [], $GLOBALS['jmc_test_fired_actions'] );
	}

	/**
	 * A submenu parent without a URL is kept as a toggle-only item.
	 */
	public function test_tree_keeps_submenu_parent_without_url(): void {
		$tree = jmc_get_navigation_tree(
			[
				self::block(
					'core/navigation-submenu',
					[ 'label' => 'About' ],
					[
						self::block(
							'core/navigation-link',
							[
								'label' => 'Team',
								'url'   => '/team/',
							]
						),
					]
				),
			]
		);

		self::assertSame( '', $tree[0]['url'] );
		self::assertSame( 'Team', $tree[0]['children'][0]['title'] );
	}

	/**
	 * Items that cannot become a link or a toggle are dropped.
	 */
	public function test_tree_skips_unsupported_or_incomplete_items(): void {
		$tree = jmc_get_navigation_tree(
			[
				self::block(
					'core/paragraph',
					[
						'label' => 'Not a link',
						'url'   => '/',
					]
				),
				self::block( 'core/navigation-link', [ 'label' => 'Missing URL' ] ),
				self::block(
					'core/navigation-link',
					[
						'label' => '   ',
						'url'   => '/blank-label/',
					]
				),
				self::block(
					'core/navigation-link',
					[
						'label' => [ 'bad' ],
						'url'   => '/array-label/',
					]
				),
				self::block( 'core/navigation-submenu', [ 'label' => 'Empty submenu' ] ),
				self::block(
					'core/navigation-submenu',
					[ 'label' => 'Children without URLs' ],
					[ self::block( 'core/navigation-link', [ 'label' => 'No URL' ] ) ]
				),
				[
					'blockName' => 'core/navigation-link',
					'attrs'     => 'malformed',
				],
				'not-a-block',
			]
		);

		self::assertSame( [], $tree );
	}

	/**
	 * A submenu with a URL but no valid children falls back to a plain link.
	 */
	public function test_tree_treats_childless_submenu_with_url_as_link(): void {
		$tree = jmc_get_navigation_tree(
			[
				self::block(
					'core/navigation-submenu',
					[
						'label' => 'Contact',
						'url'   => '/contact/',
					],
					[ self::block( 'core/paragraph', [] ) ]
				),
			]
		);

		self::assertSame(
			[
				[
					'id'       => 0,
					'title'    => 'Contact',
					'url'      => '/contact/',
					'children' => [],
				],
			],
			$tree
		);
	}

	/**
	 * Deeper branches are flattened into the submenu and reported via a hook.
	 */
	public function test_tree_flattens_unsupported_depth_and_fires_hook(): void {
		$too_deep = self::block(
			'core/navigation-submenu',
			[
				'label' => 'Domestic',
				'url'   => '/services/domestic/',
			],
			[
				self::block(
					'core/navigation-submenu',
					[
						'label' => 'Rewiring',
						'url'   => '/services/domestic/rewiring/',
					],
					[
						self::block(
							'core/navigation-link',
							[
								'label' => 'Full rewire',
								'url'   => '/services/domestic/rewiring/full/',
							]
						),
					]
				),
			]
		);

		$tree = jmc_get_navigation_tree(
			[
				self::block(
					'core/navigation-submenu',
					[
						'label' => 'Services',
						'url'   => '/services/',
					],
					[
						$too_deep,
						self::block(
							'core/navigation-link',
							[
								'label' => 'Commercial',
								'url'   => '/services/commercial/',
							]
						),
					]
				),
			]
		);

		self::assertSame(
			[ 'Domestic', 'Rewiring', 'Full rewire', 'Commercial' ],
			array_column( $tree[0]['children'], 'title' )
		);
		self::assertArrayNotHasKey( 'children', $tree[0]['children'][0] );
		self::assertSame(
			[ [ 'jmc_navigation_unsupported_depth', [ $too_deep ] ] ],
			$GLOBALS['jmc_test_fired_actions']
		);
	}


	/**
	 * Links and submenu descendants are flattened in menu order.
	 */
	public function test_flattens_supported_links_and_submenu_descendants(): void {
		$links = get_navigation_links(
			[
				[
					'blockName' => 'core/navigation-link',
					'attrs'     => [
						'id'    => 12,
						'label' => 'Home',
						'url'   => '/',
					],
				],
				[
					'blockName' => 'core/navigation-submenu',
					'attrs'     => [
						'id'    => 24,
						'label' => 'Services',
						'url'   => '/services/',
					],
					'innerBlocks' => [
						[
							'blockName' => 'core/navigation-link',
							'attrs'     => [
								'id'    => 25,
								'label' => 'Domestic',
								'url'   => '/services/domestic/',
							],
						],
					],
				],
			]
		);

		self::assertSame(
			[
				[ 'id' => 12, 'title' => 'Home', 'url' => '/' ],
				[ 'id' => 24, 'title' => 'Services', 'url' => '/services/' ],
				[ 'id' => 25, 'title' => 'Domestic', 'url' => '/services/domestic/' ],
			],
			$links
		);
	}

	/**
	 * Malformed and unsupported blocks cannot produce broken links.
	 */
	public function test_skips_unsupported_or_incomplete_blocks(): void {
		$links = get_navigation_links(
			[
				[ 'blockName' => 'core/paragraph', 'attrs' => [] ],
				[
					'blockName' => 'core/navigation-link',
					'attrs'     => [ 'label' => 'Missing URL' ],
				],
				[
					'blockName' => 'core/navigation-link',
					'attrs'     => [ 'url' => '/missing-label/' ],
				],
			]
		);

		self::assertSame( [], $links );
	}
}
