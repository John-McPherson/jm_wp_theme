<?php
/**
 * Footer business details render regression tests.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Test the footer business details block render callback.
 */
final class FooterBusinessRenderTest extends TestCase {

	/**
	 * Fully configured details render every field in order.
	 */
	public function test_renders_all_fields(): void {
		$html = $this->render_business(
			[
				'companyName' => 'Example Electrical Ltd',
				'address'     => '1 High Street<br>Example Town',
				'phone'       => '01234 567 890',
				'email'       => 'hello@example.com',
				'serviceArea' => 'Serving Example Town.',
				'className'   => 'is-custom',
			]
		);

		self::assertStringContainsString( 'class="jmc-footer-business is-custom"', $html );
		self::assertStringContainsString( '<p class="jmc-footer-business__name">Example Electrical Ltd</p>', $html );
		self::assertStringContainsString( '<address class="jmc-footer-business__contact">', $html );
		self::assertStringContainsString( '1 High Street<br>Example Town', $html );
		self::assertStringContainsString( '<a href="tel:01234567890">01234 567 890</a>', $html );
		self::assertStringContainsString( '<a href="mailto:hello@example.com">hello@example.com</a>', $html );
		self::assertStringContainsString( 'Serving Example Town.', $html );

		self::assertMatchesRegularExpression(
			'/__name.*__address.*__phone.*__email.*__service-area/s',
			$html
		);
	}

	/**
	 * Nothing is rendered when every field is omitted, empty or malformed.
	 *
	 * @dataProvider empty_attributes_provider
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function test_renders_nothing_without_details( array $attributes ): void {
		self::assertSame( '', trim( $this->render_business( $attributes ) ) );
	}

	/**
	 * Attribute sets that contain no usable details.
	 *
	 * @return array<string,array{0:array<string,mixed>}>
	 */
	public function empty_attributes_provider(): array {
		return [
			'omitted'            => [ [] ],
			'defaults'           => [
				[
					'companyName' => '',
					'address'     => '',
					'phone'       => '',
					'email'       => '',
					'serviceArea' => '',
				],
			],
			'whitespace/markup'  => [
				[
					'companyName' => '  ',
					'address'     => '<br><br>',
					'phone'       => '&nbsp;',
				],
			],
			'wrong types'        => [
				[
					'companyName' => [ 'x' ],
					'phone'       => 12345,
					'email'       => null,
				],
			],
			'invalid email only' => [ [ 'email' => 'not-an-email' ] ],
		];
	}

	/**
	 * The address wrapper is omitted when there are no contact details.
	 */
	public function test_omits_contact_wrapper_without_contact_details(): void {
		$html = $this->render_business(
			[
				'companyName' => 'Example Electrical Ltd',
				'serviceArea' => 'Serving Example Town.',
			]
		);

		self::assertStringContainsString( 'Example Electrical Ltd', $html );
		self::assertStringNotContainsString( '<address', $html );
		self::assertStringNotContainsString( '__phone', $html );
		self::assertStringNotContainsString( '__email', $html );
	}

	/**
	 * A telephone number the dialler cannot use is shown without a link.
	 */
	public function test_unlinkable_phone_is_plain_text(): void {
		$html = $this->render_business( [ 'phone' => 'Ask for Dave' ] );

		self::assertStringContainsString( 'Ask for Dave', $html );
		self::assertStringNotContainsString( '<a ', $html );
	}

	/**
	 * An invalid email address is dropped but other details remain.
	 */
	public function test_invalid_email_is_omitted(): void {
		$html = $this->render_business(
			[
				'phone' => '01234567890',
				'email' => 'javascript:alert(1)',
			]
		);

		self::assertStringNotContainsString( '__email', $html );
		self::assertStringNotContainsString( 'mailto:', $html );
		self::assertStringContainsString( 'tel:01234567890', $html );
	}

	/**
	 * Rich-text markup is stripped, entities decoded once and output escaped.
	 */
	public function test_values_are_plain_text_and_escaped(): void {
		$html = $this->render_business(
			[
				'companyName' => 'Smith &amp; Sons <script>alert(1)</script>',
				'address'     => '<strong>1</strong> &lt;High&gt; St<br />  <br>Town',
			]
		);

		self::assertStringContainsString( 'Smith &amp; Sons alert(1)', $html );
		self::assertStringNotContainsString( '<script', $html );
		self::assertStringNotContainsString( '<strong', $html );
		self::assertStringContainsString( '1 &lt;High&gt; St<br>Town', $html );
	}

	/**
	 * Render the block template with the supplied attributes.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 *
	 * @throws \Throwable If the render callback throws while generating markup.
	 *
	 * @return string Rendered markup.
	 */
	private function render_business( array $attributes ): string {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Converts PHP warnings into PHPUnit failures.
		set_error_handler(
			static function ( int $severity, string $message, string $file, int $line ): bool {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve the original PHP warning context.
				throw new \ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		ob_start();

		try {
			require dirname( __DIR__, 3 ) . '/src/blocks/site/footer-business/render.php';

			return (string) ob_get_clean();
		} catch ( \Throwable $error ) {
			ob_end_clean();

			throw $error;
		} finally {
			restore_error_handler();
		}
	}
}
