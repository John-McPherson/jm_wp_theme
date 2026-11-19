<?php
/**
 * Site search tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/search.php';
require_once dirname( __DIR__, 3 ) . '/inc/search.php';

/**
 * Verify front-end search requests are answered as not found.
 */
final class SearchTest extends TestCase {

	/**
	 * A stand-in main query that records `set_404()`.
	 *
	 * @var object
	 */
	private object $query;

	/**
	 * Start with a fresh main query and no headers.
	 */
	protected function setUp(): void {
		$this->query = new class() {
			/**
			 * Whether the query was set to a 404.
			 *
			 * @var bool
			 */
			public bool $is_404 = false;

			/**
			 * Mark the query as not found.
			 */
			public function set_404(): void {
				$this->is_404 = true;
			}
		};

		$GLOBALS['wp_query']         = $this->query;
		$GLOBALS['jmc_test_headers'] = [];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wp_query'], $GLOBALS['jmc_test_headers'], $GLOBALS['jmc_test_is_search'] );
	}

	/**
	 * The check runs before the template is chosen.
	 */
	public function test_hooks_template_redirect(): void {
		$this->assertContains(
			[ 'jmc_disable_search', 10 ],
			$GLOBALS['jmc_test_actions']['template_redirect'] ?? []
		);
	}

	/**
	 * A search request is a 404 that is not cached.
	 */
	public function test_search_is_not_found(): void {
		$GLOBALS['jmc_test_is_search'] = true;

		jmc_disable_search();

		$this->assertTrue( $this->query->is_404 );
		$this->assertSame( [ 'status 404', 'nocache' ], $GLOBALS['jmc_test_headers'] );
	}

	/**
	 * Other requests are left alone.
	 */
	public function test_other_requests_are_unchanged(): void {
		$GLOBALS['jmc_test_is_search'] = false;

		jmc_disable_search();

		$this->assertFalse( $this->query->is_404 );
		$this->assertSame( [], $GLOBALS['jmc_test_headers'] );
	}

	/**
	 * Without a main query there is nothing to change.
	 */
	public function test_missing_query_is_ignored(): void {
		$GLOBALS['jmc_test_is_search'] = true;
		$GLOBALS['wp_query']           = null;

		jmc_disable_search();

		$this->assertSame( [], $GLOBALS['jmc_test_headers'] );
	}
}
