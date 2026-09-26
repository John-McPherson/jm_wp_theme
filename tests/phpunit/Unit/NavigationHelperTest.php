<?php
/**
 * Navigation helper tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/helpers/navigation.php';

/**
 * Verify the supported Navigation-block data contract.
 */
final class NavigationHelperTest extends TestCase {

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
