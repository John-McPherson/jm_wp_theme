<?php
/**
 * Footer contact details migration tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/inc/contact.php';

/**
 * Verify the footer's old contact attributes are carried into the site-wide settings.
 */
final class FooterContactMigrationTest extends TestCase {

	/**
	 * Start with no settings and no template parts.
	 */
	protected function setUp(): void {
		$GLOBALS['jmc_test_options'] = [];
		$GLOBALS['jmc_test_posts']   = [];
	}

	/**
	 * Reset fixtures shared through globals.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_options'],
			$GLOBALS['jmc_test_posts'],
			$GLOBALS['jmc_test_get_posts_queries']
		);
	}

	/**
	 * The migration is hooked to run on every request until it has completed.
	 */
	public function test_hooks_migration(): void {
		self::assertContains(
			[ 'jmc_migrate_footer_contact_details', 20 ],
			$GLOBALS['jmc_test_actions']['init'] ?? []
		);
	}

	/**
	 * Footer values fill empty settings, and the run is recorded.
	 */
	public function test_copies_footer_values_into_empty_settings(): void {
		$this->add_footer_part(
			[
				'companyName' => 'Example Electrical Ltd',
				'phone'       => ' 01234   567890 ',
				'email'       => 'hello@example.com',
				'serviceArea' => 'Pudsey, Leeds &amp; Bradford',
			]
		);

		jmc_migrate_footer_contact_details();

		self::assertSame( '01234 567890', get_option( JMC_BUSINESS_PHONE_OPTION ) );
		self::assertSame( 'hello@example.com', get_option( JMC_BUSINESS_EMAIL_OPTION ) );
		self::assertSame( [ 'Pudsey', 'Leeds', 'Bradford' ], get_option( JMC_BUSINESS_SERVICE_AREA_OPTION ) );
		self::assertSame( '1', get_option( JMC_FOOTER_CONTACT_MIGRATED_OPTION ) );
	}

	/**
	 * Only the active theme's published footer part is read.
	 */
	public function test_queries_the_active_themes_footer_part(): void {
		jmc_migrate_footer_contact_details();

		$query = $GLOBALS['jmc_test_get_posts_queries'][0];

		self::assertSame( 'wp_template_part', $query['post_type'] );
		self::assertSame( 'footer', $query['name'] );
		self::assertSame( [ 'publish' ], $query['post_status'] );
		self::assertSame( 'amc_electrical', $query['tax_query'][0]['terms'] );
	}

	/**
	 * Settings that already have a valid value are never overwritten.
	 */
	public function test_keeps_existing_settings(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION        => '0113 496 0000',
			JMC_BUSINESS_EMAIL_OPTION        => 'office@example.com',
			JMC_BUSINESS_SERVICE_AREA_OPTION => [ 'Glasgow' ],
		];

		$this->add_footer_part(
			[
				'phone'       => '01234567890',
				'email'       => 'hello@example.com',
				'serviceArea' => 'Pudsey',
			]
		);

		jmc_migrate_footer_contact_details();

		self::assertSame( '0113 496 0000', get_option( JMC_BUSINESS_PHONE_OPTION ) );
		self::assertSame( 'office@example.com', get_option( JMC_BUSINESS_EMAIL_OPTION ) );
		self::assertSame( [ 'Glasgow' ], get_option( JMC_BUSINESS_SERVICE_AREA_OPTION ) );
	}

	/**
	 * An invalid stored setting counts as empty, so a valid footer value replaces it.
	 */
	public function test_replaces_invalid_settings(): void {
		$GLOBALS['jmc_test_options'] = [
			JMC_BUSINESS_PHONE_OPTION => 'Ask for Dave',
			JMC_BUSINESS_EMAIL_OPTION => 'not-an-email',
		];

		$this->add_footer_part(
			[
				'phone' => '01234567890',
				'email' => 'hello@example.com',
			]
		);

		jmc_migrate_footer_contact_details();

		self::assertSame( '01234567890', get_option( JMC_BUSINESS_PHONE_OPTION ) );
		self::assertSame( 'hello@example.com', get_option( JMC_BUSINESS_EMAIL_OPTION ) );
	}

	/**
	 * Footer values the settings would reject are not copied, and nothing else changes.
	 */
	public function test_skips_invalid_footer_values(): void {
		$this->add_footer_part(
			[
				'phone'       => 'Ask for Dave',
				'email'       => 'javascript:alert(1)',
				'serviceArea' => '<br>',
			]
		);

		jmc_migrate_footer_contact_details();

		self::assertSame(
			[ JMC_FOOTER_CONTACT_MIGRATED_OPTION => '1' ],
			$GLOBALS['jmc_test_options']
		);
	}

	/**
	 * Rich-text markup and entities saved by the old fields are converted to plain text.
	 */
	public function test_converts_rich_text_values(): void {
		$this->add_footer_part(
			[
				'phone'       => '<strong>01234</strong>&nbsp;567890',
				'email'       => ' hello@example.com<br>',
				'serviceArea' => 'Pudsey<br>Leeds and surrounding areas',
			]
		);

		jmc_migrate_footer_contact_details();

		self::assertSame( '01234 567890', get_option( JMC_BUSINESS_PHONE_OPTION ) );
		self::assertSame( 'hello@example.com', get_option( JMC_BUSINESS_EMAIL_OPTION ) );
		self::assertSame( [ 'Pudsey', 'Leeds', 'surrounding areas' ], get_option( JMC_BUSINESS_SERVICE_AREA_OPTION ) );
	}

	/**
	 * Without a customised footer part (or its block), only the run is recorded.
	 *
	 * @dataProvider missing_block_provider
	 *
	 * @param array<int,array<string,mixed>>|null $blocks Footer part blocks, or null for no part.
	 */
	public function test_records_run_without_footer_block( ?array $blocks ): void {
		if ( null !== $blocks ) {
			$this->add_footer_blocks( $blocks );
		}

		jmc_migrate_footer_contact_details();

		self::assertSame(
			[ JMC_FOOTER_CONTACT_MIGRATED_OPTION => '1' ],
			$GLOBALS['jmc_test_options']
		);
	}

	/**
	 * Footer parts that hold no business details block.
	 *
	 * @return array<string,array{0:array<int,array<string,mixed>>|null}>
	 */
	public function missing_block_provider(): array {
		return [
			'no footer part'       => [ null ],
			'empty footer part'    => [ [] ],
			'other blocks only'    => [ [ $this->block( 'jmc/footer-legal', [ 'copyrightHolder' => 'x' ] ) ] ],
			'malformed block list' => [ [ 'not a block', [ 'innerBlocks' => 'nope' ] ] ],
		];
	}

	/**
	 * Once recorded, the migration does nothing, even if the footer still holds values.
	 */
	public function test_runs_only_once(): void {
		$GLOBALS['jmc_test_options'][ JMC_FOOTER_CONTACT_MIGRATED_OPTION ] = '1';
		$this->add_footer_part( [ 'phone' => '01234567890' ] );

		jmc_migrate_footer_contact_details();

		self::assertArrayNotHasKey( JMC_BUSINESS_PHONE_OPTION, $GLOBALS['jmc_test_options'] );
		self::assertArrayNotHasKey( 'jmc_test_get_posts_queries', $GLOBALS );
	}

	/**
	 * Old service-area text splits into places.
	 *
	 * @dataProvider service_area_text_provider
	 *
	 * @param string   $text     Plain-text service area.
	 * @param string[] $expected Places.
	 */
	public function test_split_service_area_text( string $text, array $expected ): void {
		self::assertSame( $expected, jmc_split_service_area_text( $text ) );
	}

	/**
	 * Service-area text and the places it splits into.
	 *
	 * @return array<string,array{0:string,1:string[]}>
	 */
	public function service_area_text_provider(): array {
		return [
			'empty'          => [ '', [] ],
			'one place'      => [ 'Pudsey', [ 'Pudsey' ] ],
			'list with and'  => [ 'Pudsey, Leeds and Bradford', [ 'Pudsey', 'Leeds', 'Bradford' ] ],
			'ampersand'      => [ 'Pudsey & Leeds', [ 'Pudsey', 'Leeds' ] ],
			'lines'          => [ "Pudsey\nLeeds; Bradford", [ 'Pudsey', 'Leeds', 'Bradford' ] ],
			'and in a word'  => [ 'Sandal, Andover', [ 'Sandal', 'Andover' ] ],
			'duplicates'     => [ 'Leeds, leeds and LEEDS', [ 'Leeds' ] ],
			'trailing comma' => [ 'Pudsey, Leeds,', [ 'Pudsey', 'Leeds' ] ],
		];
	}

	/**
	 * Store a customised footer part whose business details block has the given attributes.
	 *
	 * @param array<string,mixed> $attributes Footer business block attributes.
	 */
	private function add_footer_part( array $attributes ): void {
		$this->add_footer_blocks(
			[
				$this->block(
					'jmc/footer',
					[],
					[
						$this->block( 'jmc/trust-credentials' ),
						$this->block( 'jmc/footer-business', $attributes ),
					]
				),
			]
		);
	}

	/**
	 * Store a customised footer part holding the given parsed blocks.
	 *
	 * @param array<int,mixed> $blocks Parsed blocks.
	 */
	private function add_footer_blocks( array $blocks ): void {
		$GLOBALS['jmc_test_posts'][1] = (object) [
			'post_type'    => 'wp_template_part',
			'post_name'    => 'footer',
			'post_status'  => 'publish',
			'post_content' => json_encode( $blocks, JSON_THROW_ON_ERROR ), // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The parse_blocks() test double decodes plain JSON.
		];
	}

	/**
	 * Build a parsed block.
	 *
	 * @param string                         $name        Block name.
	 * @param array<string,mixed>            $attrs       Attributes.
	 * @param array<int,array<string,mixed>> $inner_blocks Inner blocks.
	 *
	 * @return array<string,mixed> Parsed block.
	 */
	private function block( string $name, array $attrs = [], array $inner_blocks = [] ): array {
		return [
			'blockName'   => $name,
			'attrs'       => $attrs,
			'innerBlocks' => $inner_blocks,
		];
	}
}
