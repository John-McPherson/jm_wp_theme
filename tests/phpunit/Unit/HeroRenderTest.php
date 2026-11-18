<?php
/**
 * Hero render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/doubles/blocks.php';

/**
 * Test Hero block palette and breadcrumb rendering.
 */
final class HeroRenderTest extends TestCase {

	/**
	 * Breadcrumb markup returned by the core block double.
	 */
	private const TRAIL = '<nav class="wp-block-breadcrumbs jmc-hero__breadcrumbs" aria-label="Breadcrumbs"><ol><li><a href="/">Home</a></li><li><span aria-current="page">Domestic</span></li></ol></nav>';

	/**
	 * Child page ID used by the breadcrumb fixtures.
	 */
	private const CHILD_ID = 11;

	/**
	 * Set up page ancestry and the breadcrumb trail.
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['jmc_test_post_parents']         = [
			10             => 0,
			self::CHILD_ID => 10,
		];
		$GLOBALS['jmc_test_breadcrumbs']          = self::TRAIL;
		$GLOBALS['jmc_test_breadcrumbs_rendered'] = [];
	}

	/**
	 * Clear the fixtures.
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['jmc_test_post_parents'],
			$GLOBALS['jmc_test_breadcrumbs'],
			$GLOBALS['jmc_test_breadcrumbs_rendered']
		);

		parent::tearDown();
	}

	/**
	 * Verify supported and malformed palette values.
	 *
	 * PHP notices and warnings are converted into exceptions so an undefined
	 * palette-class key causes the test to fail.
	 *
	 * @dataProvider palette_provider
	 *
	 * @param array<string, mixed> $attributes     Block attributes to render.
	 * @param string|null          $expected_class Expected palette class, or null.
	 *
	 * @return void
	 */
	public function test_palette_rendering(
		array $attributes,
		?string $expected_class
	): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function (
				int $severity,
				string $message,
				string $file,
				int $line
			): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException(
					$message,               // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					0,
					$severity,              // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					$file,              // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
					$line               // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				);
			}
		);

		try {
			$html = $this->render_hero( $attributes );
		} finally {
			restore_error_handler();
		}

		self::assertStringContainsString(
			'jmc-hero',
			$html
		);

		self::assertStringNotContainsString( '<nav', $html, 'No trail without a page.' );

		if ( null === $expected_class ) {
			self::assertStringNotContainsString(
				'jmc-palette--',
				$html
			);

			return;
		}

		self::assertStringContainsString(
			$expected_class,
			$html
		);
	}

	/**
	 * Provide supported and malformed palette test cases.
	 *
	 * @return array<string, array<mixed>> Palette test cases.
	 */
	public static function palette_provider(): array {
		return [
			'omitted palette'   => [
				[],
				null,
			],
			'declared default'  => [
				[ 'palette' => 'default' ],
				null,
			],
			'secondary palette' => [
				[ 'palette' => 'secondary' ],
				'jmc-palette--secondary',
			],
			'inverse palette'   => [
				[ 'palette' => 'inverse' ],
				'jmc-palette--inverse',
			],
			'unexpected string' => [
				[ 'palette' => 'unknown' ],
				null,
			],
			'unexpected type'   => [
				[ 'palette' => [ 'invalid' ] ],
				null,
			],
		];
	}

	/**
	 * A child page shows the trail first, in place of the label, and keeps the
	 * rest of the hero content in order.
	 */
	public function test_child_page_trail_replaces_the_label(): void {
		$html = $this->render_hero( [], self::CHILD_ID );

		self::assertStringContainsString( self::TRAIL, $html );
		self::assertStringNotContainsString( 'data-variant="label"', $html );
		self::assertStringNotContainsString( 'Saved content', $html, 'Inner blocks render without the label.' );
		self::assertMatchesRegularExpression(
			'/<div class="jmc-hero__text ">\s*<nav\b.*<\/nav>'
			. '<p data-block="jmc\/heading" data-variant=""><\/p>'
			. '<p data-block="jmc\/paragraph" data-variant=""><\/p>'
			. '<p data-block="jmc\/button" data-variant=""><\/p>/s',
			$html
		);
		self::assertSame( 1, substr_count( $html, '<nav' ), 'One breadcrumb landmark.' );
	}

	/**
	 * Core Breadcrumbs receives the page from the hero's context.
	 */
	public function test_trail_is_built_from_the_page_context(): void {
		$this->render_hero( [], self::CHILD_ID, 'page' );

		self::assertSame(
			[
				[
					'attributes' => [
						'className'    => 'jmc-hero__breadcrumbs',
						'showHomeItem' => false,
					],
					'context'    => [
						'postId'   => self::CHILD_ID,
						'postType' => 'page',
					],
				],
			],
			$GLOBALS['jmc_test_breadcrumbs_rendered']
		);
	}

	/**
	 * Only the first label gives way to the trail.
	 */
	public function test_only_the_first_label_is_replaced(): void {
		$html = $this->render_hero(
			[],
			self::CHILD_ID,
			'page',
			[
				[ 'jmc/paragraph', [ 'variant' => 'label' ] ],
				[ 'jmc/heading', [] ],
				[ 'jmc/paragraph', [ 'variant' => 'label' ] ],
			]
		);

		self::assertSame( 1, substr_count( $html, 'data-variant="label"' ) );
		self::assertMatchesRegularExpression( '/<\/nav><p data-block="jmc\/heading"/', $html );
	}

	/**
	 * Cases where the hero keeps its saved content and shows no trail.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: int, 2: string}>
	 */
	public static function no_trail_provider(): array {
		return [
			'breadcrumbs turned off' => [ [ 'showBreadcrumbs' => false ], self::CHILD_ID, self::TRAIL ],
			'top-level page'         => [ [], 10, self::TRAIL ],
			'unknown page'           => [ [], 99, self::TRAIL ],
			'no page context'        => [ [], 0, self::TRAIL ],
			'core renders no trail'  => [ [], self::CHILD_ID, " \n" ],
		];
	}

	/**
	 * Without a trail the saved content, label included, renders unchanged.
	 *
	 * @dataProvider no_trail_provider
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param int                  $post_id    Page ID in context.
	 * @param string               $trail      Markup core Breadcrumbs returns.
	 */
	public function test_no_trail_keeps_the_saved_content( array $attributes, int $post_id, string $trail ): void {
		$GLOBALS['jmc_test_breadcrumbs'] = $trail;

		$html = $this->render_hero( $attributes, $post_id );

		self::assertStringNotContainsString( '<nav', $html );
		self::assertStringContainsString( '<p>Saved content</p>', $html );
	}

	/**
	 * Breadcrumbs stay on unless explicitly turned off.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function malformed_toggle_provider(): array {
		return [
			'string'  => [ 'no' ],
			'integer' => [ 0 ],
			'null'    => [ null ],
		];
	}

	/**
	 * A malformed toggle falls back to the default and shows the trail.
	 *
	 * @dataProvider malformed_toggle_provider
	 *
	 * @param mixed $value Malformed `showBreadcrumbs` value.
	 */
	public function test_malformed_toggle_shows_the_trail( mixed $value ): void {
		$html = $this->render_hero( [ 'showBreadcrumbs' => $value ], self::CHILD_ID );

		self::assertStringContainsString( self::TRAIL, $html );
	}

	/**
	 * Execute the real Hero render callback.
	 *
	 * @param array<string, mixed>                           $attributes Block attributes to render.
	 * @param int                                            $post_id    Page ID in the block context, or 0 for none.
	 * @param string                                         $post_type  Post type in the block context.
	 * @param array<int, array{0: string, 1: array<string, mixed>}>|null $inner Inner blocks as name and attributes.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered Hero markup.
	 */
	private function render_hero(
		array $attributes,
		int $post_id = 0,
		string $post_type = 'page',
		?array $inner = null
	): string {
		$content = '<p>Saved content</p>';
		$inner ??= [
			[ 'jmc/paragraph', [ 'variant' => 'label' ] ],
			[ 'jmc/heading', [] ],
			[ 'jmc/paragraph', [] ],
			[ 'jmc/button', [] ],
		];
		$context = 0 < $post_id
			? [
				'postId'   => $post_id,
				'postType' => $post_type,
			]
			: [];

		$block = new WP_Block(
			[
				'blockName'   => 'jmc/hero',
				'attrs'       => $attributes,
				'innerBlocks' => array_map(
					static fn ( array $inner_block ): array => [
						'blockName' => $inner_block[0],
						'attrs'     => $inner_block[1],
					],
					$inner
				),
			],
			$context
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 )
				. '/src/blocks/layouts/hero/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		}
	}
}
