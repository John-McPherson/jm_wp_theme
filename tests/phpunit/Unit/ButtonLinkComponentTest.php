<?php
/**
 * Button link component regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the button link component's new-tab handling.
 */
final class ButtonLinkComponentTest extends TestCase {

	/**
	 * Links that open in a new tab get a `_blank` target, safe rel tokens and a hint.
	 *
	 * @dataProvider new_tab_provider
	 *
	 * @param array<string, mixed> $link Link argument to render.
	 */
	public function test_opens_in_new_tab( array $link ): void {
		$html = $this->render_component( $link );

		self::assertStringContainsString( 'target="_blank"', $html );
		self::assertStringContainsString( 'rel="noopener noreferrer"', $html );
		self::assertStringContainsString( '(opens in a new tab)', $html );
	}

	/**
	 * Links that open in a new tab.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function new_tab_provider(): array {
		$url = 'https://example.com/';

		return [
			'opensInNewTab true'    => [
				[
					'url'           => $url,
					'opensInNewTab' => true,
				],
			],
			'legacy blank target'   => [
				[
					'url'    => $url,
					'target' => '_blank',
				],
			],
			'site-relative new tab' => [
				[
					'url'           => '/contact/',
					'opensInNewTab' => true,
				],
			],
			'new tab, empty target' => [
				[
					'url'           => $url,
					'opensInNewTab' => true,
					'target'        => '',
				],
			],
		];
	}

	/**
	 * Links that stay in the same tab render no target, rel or hint.
	 *
	 * @dataProvider same_tab_provider
	 *
	 * @param array<string, mixed> $link Link argument to render.
	 */
	public function test_stays_in_same_tab( array $link ): void {
		$html = $this->render_component( $link );

		self::assertStringContainsString( 'href=', $html );
		self::assertStringNotContainsString( 'target=', $html );
		self::assertStringNotContainsString( 'rel=', $html );
		self::assertStringNotContainsString( '(opens in a new tab)', $html );
		self::assertStringNotContainsString( 'onclick', $html );
	}

	/**
	 * Links that stay in the same tab.
	 *
	 * @return array<string, array{array<string, mixed>}>
	 */
	public function same_tab_provider(): array {
		$url = 'https://example.com/';

		return [
			'opensInNewTab missing'     => [ [ 'url' => $url ] ],
			'opensInNewTab false'       => [
				[
					'url'           => $url,
					'opensInNewTab' => false,
				],
			],
			'opensInNewTab string'      => [
				[
					'url'           => $url,
					'opensInNewTab' => 'true',
				],
			],
			'opensInNewTab integer'     => [
				[
					'url'           => $url,
					'opensInNewTab' => 1,
				],
			],
			'opensInNewTab array'       => [
				[
					'url'           => $url,
					'opensInNewTab' => [ true ],
				],
			],
			'empty legacy target'       => [
				[
					'url'    => $url,
					'target' => '',
				],
			],
			'unsupported target'        => [
				[
					'url'    => $url,
					'target' => '_top" onclick="alert(1)',
				],
			],
			'telephone with new tab'    => [
				[
					'url'           => 'tel:01234567890',
					'opensInNewTab' => true,
				],
			],
			'mailto with legacy target' => [
				[
					'url'    => 'mailto:hello@example.com',
					'target' => '_blank',
				],
			],
		];
	}

	/**
	 * Render the real button link component.
	 *
	 * PHP notices and warnings are converted into exceptions so malformed
	 * arguments that trigger them fail the test.
	 *
	 * @param array<string, mixed> $link Link argument to render.
	 *
	 * @throws \Throwable If the component throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_component( array $link ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			jmc_component(
				name: 'button-link',
				args: [
					'text' => 'Get a quote',
					'link' => $link,
				]
			);

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
