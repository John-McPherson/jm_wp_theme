<?php
/**
 * Legal pages settings page tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/settings.php';
require_once dirname( __DIR__ ) . '/doubles/legal-pages.php';
require_once dirname( __DIR__, 3 ) . '/inc/legal-pages.php';
require_once dirname( __DIR__, 3 ) . '/inc/legal-pages-settings.php';

/**
 * Verify the Settings → Legal pages page and its save handler.
 */
final class LegalPagesSettingsTest extends TestCase {

	/**
	 * Start as an administrator with a valid nonce and an empty Legal menu.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_wp']['can_edit'] = true;
		$GLOBALS['jmc_test_nonce_valid']    = true;
		$GLOBALS['jmc_test_options']        = [];
		$GLOBALS['jmc_test_posts']          = [
			10 => (object) [
				'ID'           => 10,
				'post_type'    => 'wp_navigation',
				'post_name'    => 'legal',
				'post_status'  => 'publish',
				'post_title'   => 'Legal',
				'post_content' => '[]',
			],
		];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_wp'],
			$GLOBALS['jmc_test_nonce_valid'],
			$GLOBALS['jmc_test_nonce_checks'],
			$GLOBALS['jmc_test_options'],
			$GLOBALS['jmc_test_posts'],
			$GLOBALS['jmc_test_settings'],
			$GLOBALS['jmc_test_inserted_posts'],
			$GLOBALS['jmc_test_updated_posts'],
			$GLOBALS['jmc_test_get_posts_queries']
		);
	}

	/**
	 * The page sits under Settings, needs `manage_options`, and saves
	 * through `admin-post.php`.
	 */
	public function test_adds_settings_page_for_administrators(): void {
		self::assertContains( [ 'jmc_add_legal_pages_settings_page', 10 ], $GLOBALS['jmc_test_actions']['admin_menu'] ?? [] );
		self::assertContains( [ 'jmc_handle_legal_pages_settings', 10 ], $GLOBALS['jmc_test_actions']['admin_post_jmc_save_legal_pages'] ?? [] );

		jmc_add_legal_pages_settings_page();

		$page = $GLOBALS['jmc_test_settings']['pages']['jmc-legal-pages'];

		self::assertSame( 'Legal pages', $page['page_title'] );
		self::assertSame( 'Legal pages', $page['menu_title'] );
		self::assertSame( 'manage_options', $page['capability'] );
		self::assertSame( 'jmc_legal_pages_settings_page', $page['callback'] );
	}

	/**
	 * Every page is listed, ticked by default, with its status and an edit
	 * link, in a nonce-protected form posting to `admin-post.php`.
	 */
	public function test_renders_ticked_pages_with_status(): void {
		$GLOBALS['jmc_test_options']['wp_page_for_privacy_policy'] = 3;
		$GLOBALS['jmc_test_posts']                                += [
			3 => $this->page( id: 3, slug: 'privacy-policy', status: 'publish' ),
			4 => $this->page( id: 4, slug: 'cookie-policy', status: 'draft' ),
			5 => $this->page( id: 5, slug: 'terms-and-conditions__trashed', status: 'trash' ),
		];

		$html = $this->render_page();

		self::assertStringContainsString( '<form action="https://example.com/wp-admin/admin-post.php" method="post">', $html );
		self::assertStringContainsString( '<input type="hidden" name="action" value="jmc_save_legal_pages" />', $html );
		self::assertStringContainsString( 'name="_wpnonce" value="nonce-jmc_save_legal_pages"', $html );
		self::assertSame( 4, substr_count( $html, ' checked />' ) );
		self::assertStringContainsString( '<input type="checkbox" name="jmc_legal_pages[]" id="jmc-legal-page-cookie-policy" value="cookie-policy" checked />', $html );
		self::assertStringContainsString( '<label for="jmc-legal-page-cookie-policy">Cookie Policy</label>', $html );
		self::assertMatchesRegularExpression( '#Privacy Policy</label>\s*— Published\s*— <a href="https://example.com/wp-admin/post.php\?post=3&action=edit">Edit<span class="screen-reader-text"> Privacy Policy</span></a>#', $html );
		self::assertMatchesRegularExpression( '#Cookie Policy</label>\s*— Draft\s*— <a href="https://example.com/wp-admin/post.php\?post=4&action=edit">#', $html );
		self::assertMatchesRegularExpression( '#Terms and Conditions</label>\s*— Trashed\s*— <a href="https://example.com/wp-admin/edit.php\?post_status=trash&post_type=page">View Trash#', $html );
		self::assertMatchesRegularExpression( '#Accessibility Statement</label>\s*— Not created\s*</p>#', $html );
		self::assertStringContainsString( 'reviewed by someone qualified before a page is published', $html );
		self::assertStringContainsString( 'type="submit"', $html );
	}

	/**
	 * Unticked pages render unticked; rendering creates nothing.
	 */
	public function test_renders_unticked_pages(): void {
		$GLOBALS['jmc_test_options']['jmc_legal_pages_disabled'] = [ 'cookie-policy' ];

		$html = $this->render_page();

		self::assertStringContainsString( 'value="cookie-policy" />', $html );
		self::assertSame( 3, substr_count( $html, ' checked />' ) );
		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
	}

	/**
	 * The status label covers every page state.
	 *
	 * @dataProvider status_provider
	 *
	 * @param string|null $status   Page status, or null for a missing page.
	 * @param string      $expected Expected label.
	 */
	public function test_status_label( ?string $status, string $expected ): void {
		$page = null === $status ? null : $this->page( id: 4, slug: 'cookie-policy', status: $status );

		self::assertSame( $expected, jmc_legal_page_status_label( page: $page ) );
	}

	/**
	 * Page states and their labels.
	 *
	 * @return array<string,array{0:string|null,1:string}>
	 */
	public function status_provider(): array {
		return [
			'missing'   => [ null, 'Not created' ],
			'published' => [ 'publish', 'Published' ],
			'draft'     => [ 'draft', 'Draft' ],
			'pending'   => [ 'pending', 'Pending review' ],
			'private'   => [ 'private', 'Private' ],
			'scheduled' => [ 'future', 'Scheduled' ],
			'trashed'   => [ 'trash', 'Trashed' ],
			'unknown'   => [ 'archived', 'archived' ],
		];
	}

	/**
	 * A valid save checks the nonce, applies the choice and returns to the
	 * settings page with WordPress's "Settings saved" notice.
	 */
	public function test_saves_valid_request(): void {
		$url = jmc_save_legal_pages_request( request: [ 'jmc_legal_pages' => [ 'cookie-policy' ] ] );

		self::assertSame( 'https://example.com/wp-admin/options-general.php?page=jmc-legal-pages&settings-updated=true', $url );
		self::assertSame( [ 'jmc_save_legal_pages' ], $GLOBALS['jmc_test_nonce_checks'] );
		self::assertSame( [ 'cookie-policy' ], array_column( $GLOBALS['jmc_test_inserted_posts'], 'post_name' ) );
		self::assertSame(
			[ 'privacy-policy', 'terms-and-conditions', 'accessibility-statement' ],
			$GLOBALS['jmc_test_options']['jmc_legal_pages_disabled']
		);
	}

	/**
	 * A form with nothing ticked, or a malformed field, unticks every page.
	 *
	 * @dataProvider empty_request_provider
	 *
	 * @param array<string,mixed> $request Form fields.
	 */
	public function test_saves_empty_request( array $request ): void {
		jmc_save_legal_pages_request( request: $request );

		self::assertSame( array_keys( jmc_legal_pages() ), $GLOBALS['jmc_test_options']['jmc_legal_pages_disabled'] );
		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
	}

	/**
	 * Forms without a usable list of ticked pages.
	 *
	 * @return array<string,array{0:array<string,mixed>}>
	 */
	public function empty_request_provider(): array {
		return [
			'nothing ticked' => [ [] ],
			'string'         => [ [ 'jmc_legal_pages' => 'cookie-policy' ] ],
		];
	}

	/**
	 * Users without `manage_options` are stopped before anything is saved.
	 */
	public function test_rejects_users_without_capability(): void {
		$GLOBALS['jmc_test_wp']['can_edit'] = false;

		$this->assert_rejected();
		self::assertArrayNotHasKey( 'jmc_test_nonce_checks', $GLOBALS );
	}

	/**
	 * Requests without a valid nonce are stopped before anything is saved.
	 */
	public function test_rejects_invalid_nonce(): void {
		$GLOBALS['jmc_test_nonce_valid'] = false;

		$this->assert_rejected();
	}

	/**
	 * Assert a save request stops without changing anything.
	 */
	private function assert_rejected(): void {
		try {
			jmc_save_legal_pages_request( request: [ 'jmc_legal_pages' => [ 'cookie-policy' ] ] );
			self::fail( 'The request was not stopped.' );
		} catch ( RuntimeException $error ) {
			self::assertNotSame( '', $error->getMessage() );
		}

		self::assertArrayNotHasKey( 'jmc_legal_pages_disabled', $GLOBALS['jmc_test_options'] );
		self::assertArrayNotHasKey( 'jmc_test_inserted_posts', $GLOBALS );
		self::assertArrayNotHasKey( 'jmc_test_updated_posts', $GLOBALS );
	}

	/**
	 * Build a page fixture.
	 *
	 * @param int    $id     Page ID.
	 * @param string $slug   Page slug.
	 * @param string $status Post status.
	 *
	 * @return object Post fixture.
	 */
	private function page( int $id, string $slug, string $status ): object {
		return (object) [
			'ID'          => $id,
			'post_type'   => 'page',
			'post_name'   => $slug,
			'post_status' => $status,
			'post_title'  => 'Page',
		];
	}

	/**
	 * Render the settings page.
	 *
	 * @return string Page markup.
	 */
	private function render_page(): string {
		ob_start();
		jmc_legal_pages_settings_page();

		return (string) ob_get_clean();
	}
}
